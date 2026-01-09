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
        $bookings = TestBooking::whereNotNull('damage_deposit_intent_id')
            ->get()
            ->filter(function ($booking) use ($today) {
                $departureDate = Carbon::parse($booking->start_date)->addDays($booking->nights);
                return $today->greaterThanOrEqualTo($departureDate->copy()->addDays(7));
            });

        foreach ($bookings as $booking) {
            try {
                $intent = PaymentIntent::retrieve($booking->damage_deposit_intent_id);
                if (in_array($intent->status, ['requires_capture', 'requires_payment_method', 'processing'])) {
                    $intent->cancel();
                }

                $booking->update([
                    'damage_deposit_intent_id'  => null,
                    'deposit_status'            => 'released',
                    'deposit_hold_scheduled_at' => null,
                ]);

                $this->info("Deposit released for booking {$booking->id}");
            } catch (\Exception $e) {
                $this->error("Failed for booking {$booking->id}: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
