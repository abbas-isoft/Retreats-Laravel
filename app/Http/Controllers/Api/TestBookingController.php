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
use Stripe\Webhook;

class TestBookingController extends Controller
{
    public function bookTestProperty(Request $request)
    {
        // 1. Validate request
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

        // --- Test property setup (static for now) ---
        $testPropertyId = 659508;
        $bookingRef = strtoupper(uniqid('TEST-'));
        $bookingID  = rand(100000, 999999);


        // --- Cost calculations ---
        // --- Cost calculations ---
$totalCents = (int) round($data['total_price'] * 100);

// Check if within 60 days from start_date
$daysUntilStart = Carbon::now()->diffInDays(Carbon::parse($data['start_date']), false);

if ($daysUntilStart <= 60) {
    // Case 1: Start date is within 60 days → charge full upfront
    $upfrontCents   = $totalCents;
    $remainingCents = 0;
    $remainingPaymentScheduledAt = null; // no scheduled payment
    $isFullPayment = true;
} else {
    // Case 2: More than 60 days → split into 25% + 75%
    $upfrontCents   = (int) floor($totalCents * 0.25);
    $remainingCents = $totalCents - $upfrontCents;
    $remainingPaymentScheduledAt = Carbon::parse($data['start_date'])->subDays(60);
    $isFullPayment = false;
}
        $upfrontCents   = (int) floor($totalCents * 0.25);
        $remainingCents = $totalCents - $upfrontCents;

        $remainingPaymentScheduledAt = Carbon::parse($data['start_date'])->subDays(60);

        //  Fetch property details from SuperControl API
        $supercontrolUrl = "https://secure.supercontrol.co.uk/api/property/{$testPropertyId}";
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('SUPERCONTROL_API_KEY'),
            'Accept'        => 'application/json',
        ])->get($supercontrolUrl);

        $property = $response->ok() ? $response->json() : [];

        //  Damage/Security deposit
        $securityDeposit = $property['security_deposit'] ?? 0;
        $damageDepositCents = intval($securityDeposit * 100);
        if ($damageDepositCents < 50) {
            $damageDepositCents = 5000;
        }

        $depositHoldScheduledAt = Carbon::parse($data['start_date'])->subDays(7);

        // 2. Save local booking
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

        // 3. Stripe Integration
        Stripe::setApiKey(config('services.stripe.secret'));

        $checkoutSession = CheckoutSession::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'gbp',
                    'unit_amount' => $upfrontCents,
                    'product_data' => [
                        'name' => "Booking #{$bookingID} - {$data['guest_firstname']} {$data['guest_lastname']}",
                        'description' => "Booking reference: {$bookingRef}",
                    ],
                ],
                'quantity' => 1,
            ]],
            'customer_email' => $data['email'],
            'metadata' => [
                'local_booking_id' => $booking->id,
                'property_id'      => $testPropertyId,
            ],
            'success_url' => 'http://192.168.0.134:3000/success',
            'cancel_url'  => 'http://192.168.0.134:3000/error',
        ]);

        $booking->update([
            'stripe_checkout_session_id' => $checkoutSession->id,
        ]);

        // Create Stripe Customer
        $stripeCustomer = Customer::create([
            'name'  => $data['guest_firstname'] . ' ' . $data['guest_lastname'],
            'email' => $data['email'],
            'metadata' => [
                'local_booking_id' => $booking->id,
                'property_id'      => $testPropertyId,
            ],
        ]);

        if ($isFullPayment) {
            
            //  One-shot 100% PaymentIntent
            $paymentIntent = PaymentIntent::create([
                'amount'   => $totalCents,
                'currency' => 'gbp',
                'customer' => $stripeCustomer->id,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'local_booking_id' => $booking->id,
                    'type'             => 'full',
                ],
                'description' => "Full upfront payment for booking {$booking->id}",
            ]);

            $setupIntent = null; // no need to save card for later
        } else {
            //  Split Payment: First 25% now, rest scheduled
            $paymentIntent = PaymentIntent::create([
                'amount'   => $upfrontCents,
                'currency' => 'gbp',
                'customer' => $stripeCustomer->id,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'local_booking_id' => $booking->id,
                    'type'             => 'upfront',
                ],
                'description' => "Upfront 25% for booking {$booking->id}",
                'setup_future_usage' => 'off_session',
            ]);

            // Save card for remaining 75%
            // $setupIntent = SetupIntent::create([
            //     'customer' => $stripeCustomer->id,
            //     'payment_method_types' => ['card'],
            //     'usage' => 'off_session',
            //     'metadata' => [
            //         'local_booking_id' => $booking->id,
            //         'type'         => 'remaining',
            //     ],
            // ]);
        }

        $booking->update([
            'stripe_customer_id'        => $stripeCustomer->id,
            'upfront_payment_intent_id' => $paymentIntent->id,
            'setup_intent_id'           => $setupIntent ? $setupIntent->id : null,
            'payment_status'            => 'pending',
            'upfront_cents'             => $upfrontCents,
            'remaining_cents'           => $remainingCents,
            'remaining_payment_scheduled_at' => $remainingPaymentScheduledAt,
        ]);

        //  Schedule Remaining 75% after 3 minutes
        // if ($booking->stripe_customer_id && $booking->stripe_payment_method_id) {
        //     Queue::later(now()->addMinutes(3), function () use ($booking) {
        //         \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        //         try {
        //             $intent = PaymentIntent::create([
        //                 'amount'   => $booking->remaining_cents,
        //                 'currency' => 'gbp',
        //                 'customer' => $booking->stripe_customer_id,
        //                 'payment_method' => $booking->stripe_payment_method_id,
        //                 'off_session' => true,
        //                 // 'confirm' => true,
        //                 'metadata' => [
        //                     'local_booking_id' => $booking->id,
        //                     'type' => 'remaining',
        //                 ],
        //             ]);

        //             $booking->update([
        //                 'remaining_payment_intent_id' => $intent->id,
        //                 'payment_status' => 'fully_paid',
        //             ]);

        //             Log::info("Remaining payment auto-charged for booking {$booking->id}");
        //         } catch (\Exception $e) {
        //             Log::error("Remaining payment failed for booking {$booking->id}: " . $e->getMessage());
        //         }
        //     });
        // }

        // 4. Response
        return response()->json([
            'status'  => true,
            'message' => 'Booking payment created successfully',
            'data'    => [
                'booking_reference'        => $bookingRef,
                'property_id'              => $testPropertyId,
                'booking_id'               => $bookingID,
                'initial_payment'          => $upfrontCents / 100,
                'final_payment'            => $remainingCents / 100,
                'final_payment_due_date'   => $remainingPaymentScheduledAt ? $remainingPaymentScheduledAt->toDateString() : null,
                'damage_deposit'           => $damageDepositCents / 100,
                'damage_deposit_hold_date' => $depositHoldScheduledAt->toDateString(),
                'client_secret'            => $paymentIntent->client_secret,
                'setup_client_secret'      => $setupIntent?->client_secret,
                'payment_status'           => $booking->payment_status,
            ]
        ]);
    }

    /**
     * Release the deposit hold
     */
    public function releaseDeposit(TestBooking $booking)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            if (empty($booking->damage_deposit_intent_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No damage deposit intent found for this booking',
                ], 400);
            }

            $paymentIntent = PaymentIntent::retrieve($booking->damage_deposit_intent_id);

            // Logging intent details for debug
            Log::info('ReleaseDeposit - PaymentIntent details', [
                'id'     => $paymentIntent->id,
                'amount' => $paymentIntent->amount,
                'status' => $paymentIntent->status,
            ]);

            if ($paymentIntent->amount < 50) {
                return response()->json([
                    'status' => false,
                    'message' => 'Deposit amount too small to release (must be >= 50 cents)',
                ], 400);
            }

            $paymentIntent->cancel(); // release hold

            $booking->update(['deposit_status' => 'released']);

            return response()->json([
                'status' => true,
                'message' => 'Deposit released successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error releasing deposit: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error releasing deposit: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Capture the deposit if needed
     */
    public function captureDeposit(TestBooking $booking)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            if (empty($booking->damage_deposit_intent_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No damage deposit intent found for this booking',
                ], 400);
            }

            $paymentIntent = PaymentIntent::retrieve($booking->damage_deposit_intent_id);

            // Logging intent details for debug
            Log::info('CaptureDeposit - PaymentIntent details', [
                'id'     => $paymentIntent->id,
                'amount' => $paymentIntent->amount,
                'status' => $paymentIntent->status,
            ]);

            if ($paymentIntent->amount < 50) {
                return response()->json([
                    'status' => false,
                    'message' => 'Deposit amount too small to capture (must be >= 50 cents)',
                ], 400);
            }

            $paymentIntent->capture(); // charge the hold

            $booking->update(['deposit_status' => 'captured']);

            return response()->json([
                'status' => true,
                'message' => 'Deposit captured successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error capturing deposit: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error capturing deposit: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function checkoutSuccess($bookingId)
    {
        $booking = TestBooking::findOrFail($bookingId);
        $booking->update(['payment_status' => 'paid']); //  fix quotes

        return redirect('http://192.168.0.134:3000/home');
    }

    public function checkoutCancel($bookingId)
    {
        $booking = TestBooking::findOrFail($bookingId);

        return redirect('http://192.168.0.134:3000/home?payment=cancelled');
    }

    public function handleStripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET'); // from Stripe dashboard

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            // Update booking status
            $bookingId = $session->metadata->local_booking_id ?? null;
            if ($bookingId) {
                $booking = TestBooking::find($bookingId);
                if ($booking) {
                    $booking->update(['payment_status' => 'paid']);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    public function getClientSecret(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|integer',
        ]);

        // yaha custom booking_id se search karo
        $booking = TestBooking::where('booking_id', $request->booking_id)->first();

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        if (!$booking->upfront_payment_intent_id) {
            return response()->json([
                'status' => false,
                'message' => 'No upfront payment intent found for this booking',
            ], 400);
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($booking->upfront_payment_intent_id);

            return response()->json([
                'status' => true,
                'client_secret' => $paymentIntent->client_secret,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching client_secret: ' . $e->getMessage(),
            ], 500);
        }
    }
}
