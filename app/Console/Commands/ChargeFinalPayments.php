<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TestBooking;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Carbon\Carbon;

class ChargeFinalPayments extends Command
{
    protected $signature = 'payments:charge-final';
    protected $description = 'Charge remaining 75% balance for due bookings';

   // Console/Commands/ChargeFinalPayment.php
public function handle()
{
    Stripe::setApiKey(config('services.stripe.secret'));

   $bookings = TestBooking::whereNull('remaining_payment_intent_id')->get();

    foreach ($bookings as $booking) {
        $intent = PaymentIntent::create([
            'amount' => $booking->remaining_cents,
            'currency' => 'gbp',
            'customer' => $booking->stripe_customer_id,
            'payment_method' => $booking->stripe_payment_method_id,
            'off_session' => true,
            'confirm' => true,
            'metadata' => [
                'local_booking_id' => $booking->id,
                'type' => 'final',
            ],
        ]);

        $booking->update([
            'remaining_payment_intent_id' => $intent->id,
            'payment_status' => 'final_charged',
        ]);
    }
}

    }

