<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TestBooking;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;
use App\Mail\RemainingPaymentMail;
use Illuminate\Support\Facades\Mail;

class ChargeRemainingPayments extends Command
{
    protected $signature = 'payments:charge-remaining';
    protected $description = 'Charge remaining 75% of bookings on scheduled date';

    public function handle()
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        // Pehle wo bookings nikalte hain jinki payment due hai
        $bookings = TestBooking::whereNotNull('stripe_customer_id')->where('payment_status', 'upfront_paid')->whereDate('remaining_payment_scheduled_at', '<=', Carbon::today())->get();
Log::info("Payment method bookings".$bookings);
        foreach ($bookings as $booking) {
            try {
                // agar payment method missing hai toh upfront intent se nikal lo
                if (empty($booking->stripe_payment_method_id) && !empty($booking->upfront_payment_intent_id)) {
                    $upfrontIntent = PaymentIntent::retrieve($booking->upfront_payment_intent_id);
                    if (!empty($upfrontIntent->payment_method)) {
                        $booking->update([
                            'stripe_payment_method_id' => $upfrontIntent->payment_method,
                        ]);
                        Log::info("Payment method {$upfrontIntent->payment_method} saved for booking {$booking->id}");
                    }
                }

                // agar abhi bhi payment method missing hai toh skip
                if (empty($booking->stripe_payment_method_id)) {
                    // Log::warning("No payment method found for booking {$booking->id}, skipping charge.");
                    continue;
                }

                // ab remaining 75% charge karo
                $intent = PaymentIntent::create([
                    'amount'        => $booking->remaining_cents,
                    'currency'      => 'gbp',
                    'customer'      => $booking->stripe_customer_id,
                    'payment_method'=> $booking->stripe_payment_method_id,
                    'off_session'   => true,
                    'confirm'       => true,
                    'metadata'      => [
                        'local_booking_id' => $booking->id,
                        'type' => 'remaining',
                    ],
                ]);

                $booking->update([
                    'remaining_payment_intent_id' => $intent->id,
                    'payment_status' => 'fully_paid',
                ]);

                Log::info("Remaining 75% charged successfully for booking {$booking->id}");
                $this->info("Booking {$booking->id} charged successfully.");
                Mail::to($booking->email)->send(new RemainingPaymentMail($booking));
            } catch (\Exception $e) {
                Log::error("Remaining payment failed for booking {$booking->id}: " . $e->getMessage());
                $this->error("Booking {$booking->id} failed: " . $e->getMessage());
            }
        }
    }
}
