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
            // Extract values from new Hostaway structure
            $currency = $listing['currency'] ?? 'EUR';
            $balanceDueDays = $listing['billing']['balance']['due_days'] ?? 0;

            // Deposit info: now using 'billing.deposit' structure
            $depositType = $listing['billing']['deposit']['type'] ?? 'percentage';
            $depositValue = $listing['billing']['deposit']['amount'] ?? 0;

            // Security deposit: first check billing, then custom field override
            $securityDeposit = $listing['billing']['security_deposit'] ?? 0;
            foreach ($listing['customFieldValues'] ?? [] as $customField) {
                $name = strtolower($customField['customField']['name'] ?? '');
                if ($name === 'security deposit') {
                    $securityDeposit = $customField['value'] ?? $securityDeposit;
                    break;
                }
            }

            // Save or update PriceConfiguration
            PriceConfiguration::updateOrCreate(
                ['property_id' => $propertyId],
                [
                    'currency' => $currency,
                    'deposit_type' => $depositType,
                    'deposit_value' => $depositValue,
                    'security_deposit' => $securityDeposit,
                    'balance_due_days' => $balanceDueDays,
                ]
            );

            Log::info("✅ Price configuration saved for property ID: {$propertyId}");
        } catch (\Exception $e) {
            Log::error("❌ Exception saving price for property ID {$propertyId}: " . $e->getMessage());
        }
    }
}
