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

class PlaceDepositHold implements ShouldQueue
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
                'amount' => $this->booking->damage_deposit_cents,
                'currency' => 'gbp',
                'customer' => $this->booking->stripe_customer_id,
                'payment_method' => $paymentMethod,
                'off_session' => true,
                'confirm' => true,
                'capture_method' => 'manual',
                'metadata' => ['local_booking_id' => $this->booking->id, 'type' => 'damage_deposit'],
            ]);

            $this->booking->update(['damage_deposit_intent_id' => $intent->id]);
            Log::info("Deposit hold placed for booking {$this->booking->id}");
        } catch (\Exception $e) {
            Log::error("Deposit hold failed for booking {$this->booking->id}: " . $e->getMessage());
        }
    }
}
