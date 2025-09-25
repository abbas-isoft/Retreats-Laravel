<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TestBooking;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class ReleaseDepositHold extends Command
{
    protected $signature = 'deposit:release';
    protected $description = 'Release deposit holds 7 days after departure if not captured';

    public function handle()
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $today = Carbon::today();
        $targetDate = $today->copy()->subDays(7); 
        // Matlab jinki departure date se 7 din beet gaye

        // Sirf un bookings ko uthao jinke paas damage deposit intent bana tha
        $bookings = TestBooking::whereDate('start_date', $targetDate)
            ->whereNotNull('damage_deposit_intent_id')
            ->get();

        foreach ($bookings as $booking) {
            try {
                $intent = PaymentIntent::retrieve($booking->stripe_payment_method_id);

                if ($intent->status === 'requires_capture' || $intent->status === 'requires_payment_method' || $intent->status === 'processing') {
                    $intent->cancel();
                }

                $booking->update([
                    'stripe_payment_method_id' => null, // clear hold intent
                    'deposit_hold_scheduled_at' => null, // reset field
                ]);

                $this->info("✅ Deposit released for booking {$booking->id}");
            } catch (\Exception $e) {
                $this->error("❌ Failed for booking {$booking->id}: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
