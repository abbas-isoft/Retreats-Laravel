<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TestBooking;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\SetupIntent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Stripe\Checkout\Session as CheckoutSession;
use App\Mail\BookingConfirmed;
use Illuminate\Support\Facades\Mail;
use Stripe\Webhook;

class TestBookingController extends Controller
{
    public function bookTestProperty(Request $request)
    {
        $data = $request->validate([
            'start_date'       => 'required|date',
            'nights'           => 'required|integer',
            'guest_firstname'  => 'required|string',
            'guest_lastname'   => 'required|string',
            'email'            => 'required|email',
            'phone'            => 'required|string',
            'total_price'      => 'required|numeric',
            'accepted_terms'   => 'required|boolean',
            'accepted_rules'   => 'required|boolean',
            'stripe_payment_method_id' => 'nullable|string',
        ]);

        $testPropertyId = 659508;
        $bookingRef = strtoupper(uniqid('TEST-'));
        $bookingID  = rand(100000, 999999);

        $totalCents = (int) round($data['total_price'] * 100);
        $daysUntilStart = Carbon::now()->diffInDays(Carbon::parse($data['start_date']), false);

        if ($daysUntilStart <= 60) {
            $upfrontCents   = $totalCents;
            $remainingCents = 0;
            $remainingPaymentScheduledAt = null;
            $isFullPayment = true;
        } else {
            $upfrontCents   = (int) floor($totalCents * 0.25);
            $remainingCents = $totalCents - $upfrontCents;
            $remainingPaymentScheduledAt = Carbon::parse($data['start_date'])->subDays(60);
            $isFullPayment = false;
        }

        $supercontrolUrl = "https://secure.supercontrol.co.uk/api/property/{$testPropertyId}";
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('SUPERCONTROL_API_KEY'),
            'Accept'        => 'application/json',
        ])->get($supercontrolUrl);

        $property = $response->ok() ? $response->json() : [];

        $securityDeposit = $property['security_deposit'] ?? 0;
        $damageDepositCents = intval($securityDeposit * 100);
        if ($damageDepositCents < 5000) {
            $damageDepositCents = 5000; // default £50
        }

        $depositHoldScheduledAt = Carbon::parse($data['start_date'])->subDays(7);

        $booking = TestBooking::create([
            'property_id'                   => $testPropertyId,
            'start_date'                    => $data['start_date'],
            'nights'                        => $data['nights'],
            'guest_firstname'               => $data['guest_firstname'],
            'guest_lastname'                => $data['guest_lastname'],
            'email'                         => $data['email'],
            'phone'                         => $data['phone'],
            'value'                         => $data['total_price'],
            'booking_reference'             => $bookingRef,
            'booking_id'                    => $bookingID,
            'supercontrol_response'         => json_encode($property),
            'accepted_terms'                => $data['accepted_terms'],
            'accepted_rules'                => $data['accepted_rules'],
            'amount_cents'                  => $totalCents,
            'upfront_cents'                 => $upfrontCents,
            'remaining_cents'               => $remainingCents,
            'damage_deposit_cents'          => $damageDepositCents,
            'remaining_payment_scheduled_at' => $remainingPaymentScheduledAt,
            'deposit_hold_scheduled_at'     => $depositHoldScheduledAt,
            'payment_status'                => 'pending',
            'stripe_payment_method_id'      => $data['stripe_payment_method_id'] ?? null,
        ]);

        Stripe::setApiKey(config('services.stripe.secret'));

        $stripeCustomer = Customer::create([
            'name'  => $data['guest_firstname'] . ' ' . $data['guest_lastname'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'metadata' => [
                'local_booking_id' => $booking->id,
                'property_id'      => $testPropertyId,
                'booking_ref'      => $bookingRef,
            ],
        ]);

        if ($isFullPayment) {
            // 💳 One-shot full payment
            $paymentIntent = PaymentIntent::create([
                'amount'   => $totalCents,
                'currency' => 'gbp',
                'customer' => $stripeCustomer->id,
                'receipt_email' => $data['email'], // ✅ sends receipt to customer
                'automatic_payment_methods' => ['enabled' => true],
                'description' => "Full payment for Booking {$bookingRef}",
                'metadata' => [
                    'booking_id'      => $booking->id,
                    'booking_ref'     => $bookingRef,
                    'guest_name'      => $data['guest_firstname'].' '.$data['guest_lastname'],
                    'property_id'     => $testPropertyId,
                    'type'            => 'full',
                ],
                'statement_descriptor_suffix' => 'GR BOOKING',
            ]);
            
            $setupIntent = null;
        } else {
            // 💰 Split payment: 25% now, 75% later
            $paymentIntent = PaymentIntent::create([
                'amount'   => $upfrontCents,
                'currency' => 'gbp',
                'customer' => $stripeCustomer->id,
                'receipt_email' => $data['email'],
                'automatic_payment_methods' => ['enabled' => true],
                'description' => "25% Deposit for Booking {$bookingRef}",
                'metadata' => [
                    'booking_id'  => $booking->id,
                    'booking_ref' => $bookingRef,
                    'guest_name'  => $data['guest_firstname'].' '.$data['guest_lastname'],
                    'property_id' => $testPropertyId,
                    'type'        => 'upfront',
                ],
                'statement_descriptor_suffix' => 'GR DEPOSIT',
                'setup_future_usage' => 'off_session',
            ]);

            $setupIntent = SetupIntent::create([
                'customer' => $stripeCustomer->id,
                'payment_method_types' => ['card'],
                'usage' => 'off_session',
                'metadata' => [
                    'booking_id' => $booking->id,
                    'type'       => 'remaining',
                ],
            ]);
        }

        $booking->update([
            'stripe_customer_id'        => $stripeCustomer->id,
            'upfront_payment_intent_id' => $paymentIntent->id,
            'setup_intent_id'           => $setupIntent?->id,
            'payment_status'            => 'pending',
        ]);
        Mail::to($data['email'])->send(new BookingConfirmed($booking));
        return response()->json([
            'status'  => true,
            'message' => 'Booking payment created successfully',
            'data'    => [
                'booking_reference'        => $bookingRef,
                'property_id'              => $testPropertyId,
                'booking_id'               => $bookingID,
                'initial_payment'          => $upfrontCents / 100,
                'final_payment'            => $remainingCents / 100,
                'final_payment_due_date'   => $remainingPaymentScheduledAt?->toDateString(),
                'damage_deposit'           => $damageDepositCents / 100,
                'damage_deposit_hold_date' => $depositHoldScheduledAt->toDateString(),
                'client_secret'            => $paymentIntent->client_secret,
                'setup_client_secret'      => $setupIntent?->client_secret,
                'payment_status'           => $booking->payment_status,
            ]
        ]);
    }

    // ✅ Webhook for payment_intent.succeeded to update booking + log receipt_url
    public function handleStripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $intent = $event->data->object;
            $bookingId = $intent->metadata->booking_id ?? null;

            if ($bookingId) {
                $booking = TestBooking::find($bookingId);
                if ($booking) {
                    $receiptUrl = $intent->charges->data[0]->receipt_url ?? null;
                    $booking->update([
                        'payment_status' => 'paid',
                        'receipt_url' => $receiptUrl,
                    ]);
                    Log::info("✅ Booking {$bookingId} marked paid. Receipt: {$receiptUrl}");
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
