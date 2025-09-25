<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TestBooking;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Carbon\Carbon;

class HoldDamageDeposit extends Command
{
    protected $signature = 'payments:hold-deposit';
    protected $description = 'Place a hold for damage deposit before check-in';

   // Console/Commands/HoldDamageDeposit.php
public function handle()
{
    Stripe::setApiKey(config('services.stripe.secret'));

    $bookings = TestBooking::whereNull('damage_deposit_intent_id')->get();

    foreach ($bookings as $booking) {
        $intent = PaymentIntent::create([
            'amount' => $booking->damage_deposit_cents,
            'currency' => 'gbp',
            'customer' => $booking->stripe_customer_id,
            'payment_method' => $booking->stripe_payment_method_id,
            'capture_method' => 'manual',
            'confirm' => true,
            'off_session' => true,
            'metadata' => [
                'local_booking_id' => $booking->id,
                'type' => 'damage_deposit',
            ],
        ]);

        $booking->update([
            'damage_deposit_intent_id' => $intent->id,
            'payment_status' => 'deposit_held',
        ]);
    }
}

    }

