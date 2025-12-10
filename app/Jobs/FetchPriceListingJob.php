<?php

namespace App\Jobs;

use App\Models\PriceConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchPriceListingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            $response = Http::timeout(600)->withHeaders([
                'Authorization' => 'Bearer ' . config('services.hostaway.token'),
                'Cache-Control' => 'no-cache',
            ])->get("https://api.hostaway.com/v1/listings");

            if ($response->failed()) {
                Log::error("❌ Failed to fetch listings", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return;
            }

            $listings = $response->json('result', []);

            if (empty($listings)) {
                Log::warning("⚠ No listings returned from Hostaway.");
                return;
            }

            foreach ($listings as $listing) {
                $propertyId = $listing['id'] ?? null;
                if (!$propertyId) continue;

                $this->savePriceConfiguration($propertyId, $listing);
            }

            Log::info("🎉 All Hostaway price configurations processed successfully.");
        } catch (\Exception $e) {
            Log::error("❌ Exception fetching listings: " . $e->getMessage());
        }
    }

   private function savePriceConfiguration($propertyId, $listing)
{
    try {
        // 🔹 Log the listing for debugging
        Log::info('Processing Listing Data for Property '.$propertyId, $listing);

        // Currency from Hostaway 'currencyCode'
        $currency = $listing['currencyCode'] ?? 'EUR';

        // Balance due days
        $balanceDueDays = $listing['billing']['balance']['due_days'] ?? 0;

        // Deposit info
        $depositType = $listing['billing']['deposit']['type'] ?? 'percentage';
        $depositValue = $listing['billing']['deposit']['amount'] ?? 0;

        // Security deposit: use refundableDamageDeposit only
        $securityDeposit = 0;
        if (isset($listing['refundableDamageDeposit'])) {
            $securityDeposit = floatval($listing['refundableDamageDeposit']);
        }

        // Save or update PriceConfiguration
        PriceConfiguration::updateOrCreate(
            ['property_id' => $propertyId],
            [
                'currency' => $currency,                     // ✅ from currencyCode
                'deposit_type' => $depositType,
                'deposit_value' => $depositValue,
                'security_deposit' => $securityDeposit,     // ✅ from refundableDamageDeposit
                'balance_due_days' => $balanceDueDays,
            ]
        );

        Log::info("✅ Price configuration saved for property ID: {$propertyId} with security deposit: {$securityDeposit} and currency: {$currency}");
    } catch (\Exception $e) {
        Log::error("❌ Exception saving price for property ID {$propertyId}: " . $e->getMessage());
    }
}


}
