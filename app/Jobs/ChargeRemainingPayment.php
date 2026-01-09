<?php

namespace App\Jobs;

use App\Models\TestBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;

class ChargeRemainingPayment implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $booking;

    public function __construct(TestBooking $booking)
    {
        $this->booking = $booking;
    }

    public function handle()
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $setupIntent = \Stripe\SetupIntent::retrieve($this->booking->setup_intent_id);
            $paymentMethod = $setupIntent->payment_method;

            $intent = PaymentIntent::create([
                'amount' => $this->booking->remaining_cents,
                'currency' => 'gbp',
                'customer' => $this->booking->stripe_customer_id,
                'payment_method' => $paymentMethod,
                'off_session' => true,
                'confirm' => true,
                'metadata' => ['local_booking_id' => $this->booking->id, 'type' => 'remaining'],
            ]);

            $this->booking->update([
                'remaining_payment_intent_id' => $intent->id,
                'payment_status' => 'fully_paid',
            ]);

            Log::info(" Remaining payment charged for booking {$this->booking->id}");
        } catch (\Exception $e) {
            // Log::error(" Remaining payment failed for booking {$this->booking->id}: ".$e->getMessage());
        }
    }
}
