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

class TestBookingController extends Controller
{
    public function bookTestProperty(Request $request)
    {
        //  Validate request
        $data = $request->validate([
            'start_date' => 'required|date',
            'nights' => 'required|integer',
            'guest_firstname' => 'required|string',
            'guest_lastname' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'total_price' => 'required|numeric',
            'accepted_terms' => 'required|boolean',
            'accepted_rules' => 'required|boolean',
        ]);

        // --- Test property setup ---
        $testPropertyId = 659508;
        $bookingRef = strtoupper(uniqid('TEST-'));
        $bookingID = rand(100000, 999999);

        // --- Cost calculations ---
        $totalCents = (int) round($data['total_price'] * 100);

        //  Force 25% upfront + 75% final (for testing)
        $upfrontCents = (int) floor($totalCents * 0.25);
        $remainingCents = $totalCents - $upfrontCents;

        $remainingPaymentScheduledAt = Carbon::parse($data['start_date'])->subDays(60);

        //  Damage deposit (hold 7 days before check-in)
        $damageDepositCents = intval(env('PAYMENTS_DAMAGE_DEPOSIT_CENTS', 5000)); // £50 default
        $depositHoldScheduledAt = Carbon::parse($data['start_date'])->subDays(7);

        // --- Save local booking ---
        $booking = TestBooking::create([
            'property_id' => $testPropertyId,
            'start_date' => $data['start_date'],
            'nights' => $data['nights'],
            'guest_firstname' => $data['guest_firstname'],
            'guest_lastname' => $data['guest_lastname'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'value' => $data['total_price'],
            'booking_reference' => $bookingRef,
            'booking_id' => $bookingID,
            'supercontrol_response' => json_encode(['dummy' => 'Test booking created (no real API hit)']),
            'accepted_terms' => $data['accepted_terms'],
            'accepted_rules' => $data['accepted_rules'],
            'amount_cents' => $totalCents,
            'upfront_cents' => $upfrontCents,
            'remaining_cents' => $remainingCents,
            'damage_deposit_cents' => $damageDepositCents,
            'remaining_payment_scheduled_at' => $remainingPaymentScheduledAt,
            'deposit_hold_scheduled_at' => $depositHoldScheduledAt,
            'payment_status' => 'pending',
        ]);

        // --- Stripe Integration ---
        Stripe::setApiKey(config('services.stripe.secret'));

        //  Create customer
        $stripeCustomer = Customer::create([
            'name' => $data['guest_firstname'] . ' ' . $data['guest_lastname'],
            'email' => $data['email'],
            'metadata' => [
                'local_booking_id' => $booking->id,
                'property_id' => $testPropertyId,
            ],
        ]);

        //  PaymentIntent for upfront payment
        $paymentIntent = PaymentIntent::create([
            'amount' => $upfrontCents,
            'currency' => 'gbp',
            'customer' => $stripeCustomer->id,
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'local_booking_id' => $booking->id,
                'type' => 'upfront',
            ],
            'description' => "Initial payment for booking {$booking->id}",
            'setup_future_usage' => 'off_session',
        ]);

        $booking->upfront_payment_intent_id = $paymentIntent->id;
        $booking->stripe_customer_id = $stripeCustomer->id;
        $booking->payment_status = 'pending';
        $booking->save();

        //  SetupIntent to save card for remaining 75% + damage deposit
        $setupIntent = SetupIntent::create([
            'customer' => $stripeCustomer->id,
            'metadata' => [
                'local_booking_id' => $booking->id,
            ],
        ]);

        // --- Response ---
        return response()->json([
            'status' => true,
            'message' => 'Booking payment created successfully',
            'data' => [
            'booking_reference' => $bookingRef,
            'property_id' => $testPropertyId,
            'booking_id' => $bookingID,
            'initial_payment' => $upfrontCents / 100,
            'final_payment' => $remainingCents / 100,
            'final_payment_due_date' => $remainingPaymentScheduledAt->toDateString(),
            'damage_deposit' => $damageDepositCents / 100,
            'damage_deposit_hold_date' => $depositHoldScheduledAt->toDateString(),
            'client_secret' => $paymentIntent->client_secret,
            'setup_client_secret' => $setupIntent->client_secret,
            'payment_status' => $booking->payment_status,
            ]
        ]);
    }
}
