<?php

namespace App\Jobs;

use App\Models\Booking;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRemainingPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bookingId;

    /**
     * Create a new job instance.
     */
    public function __construct($bookingId)
    {
        $this->bookingId = $bookingId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $booking = Booking::find($this->bookingId);

        if (! $booking || ! $booking->stripe_customer_id || ! $booking->stripe_payment_method_id) {
            Log::error("Remaining payment failed: booking not valid for ID {$this->bookingId}");
            return;
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $intent = PaymentIntent::create([
                'amount'   => $booking->remaining_cents,
                'currency' => 'gbp',
                'customer' => $booking->stripe_customer_id,
                'payment_method' => $booking->stripe_payment_method_id,
                'off_session' => true,
                'confirm' => true,
                'metadata' => [
                    'local_booking_id' => $booking->id,
                    'type' => 'remaining',
                ],
            ]);

            Log::info("Remaining payment successful for booking {$booking->id}", [
                'payment_intent' => $intent->id
            ]);
        } catch (\Exception $e) {
            Log::error("Stripe error for booking {$booking->id}: " . $e->getMessage());
        }
    }
}
