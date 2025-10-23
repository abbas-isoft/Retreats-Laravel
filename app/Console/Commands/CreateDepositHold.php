<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TestBooking;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class CreateDepositHold extends Command
{
    protected $signature = 'deposit:hold';
    protected $description = 'Place pre-authorised hold on damage deposits 7 days before arrival';

    public function handle()
{
    if (!$this->lock()) {
        $this->info("Deposit hold job already running, skipping...");
        return Command::SUCCESS;
    }

    Stripe::setApiKey(config('services.stripe.secret'));

    $today = Carbon::today();
    $targetDate = $today->copy()->addDays(7);

    $bookings = TestBooking::whereDate('start_date', $targetDate)
        ->whereNull('damage_deposit_intent_id')
        ->get();

    foreach ($bookings as $booking) {
        try {
            // Double-check to avoid race condition
            if (!is_null($booking->damage_deposit_intent_id)) {
                $this->warn("Booking {$booking->id} already has a hold, skipping...");
                continue;
            }

            $paymentIntent = PaymentIntent::create([
                'amount' => $booking->damage_deposit_cents,
                'currency' => 'gbp',
                'customer' => $booking->stripe_customer_id,
                'payment_method' => $booking->stripe_payment_method_id,
                'off_session' => true,
                'confirm' => true,
                'capture_method' => 'manual',
                'metadata' => [
                    'local_booking_id' => $booking->id,
                    'type' => 'damage_deposit',
                ],
            ]);

            $booking->update([
                'damage_deposit_intent_id'  => $paymentIntent->id,
                'deposit_hold_scheduled_at' => now(),
            ]);

            $this->info("✅ Deposit hold placed for booking {$booking->id}");
        } catch (\Exception $e) {
            $this->error("❌ Failed for booking {$booking->id}: " . $e->getMessage());
        }
    }

    $this->releaseLock();

    return Command::SUCCESS;
}

private function lock()
{
    $lockFile = storage_path('app/deposit_hold.lock');
    if (file_exists($lockFile)) {
        $lastRun = filemtime($lockFile);
        if (time() - $lastRun < 300) { // 5 minutes
            return false;
        }
    }
    file_put_contents($lockFile, time());
    return true;
}

private function releaseLock()
{
    $lockFile = storage_path('app/deposit_hold.lock');
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}
}