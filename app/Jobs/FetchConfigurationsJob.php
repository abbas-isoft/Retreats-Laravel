<?php

namespace App\Jobs;

use App\Models\PropertyConfiguration;
use App\Models\AccountConfiguration;
use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchConfigurationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        foreach (Account::all() as $account) {
            try {
                $response = Http::withHeaders([
                    'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
                ])->get("https://api.supercontrol.co.uk/v3/properties/ConfigurationIndex/{$account->id}");

                if ($response->failed()) {
                    Log::error("Failed to fetch configurations for account {$account->id}", [
                        'status' => $response->status(),
                        'body'   => $response->body(),
                    ]);
                    continue;
                }

                $properties = $response->json();

                // Account-level defaults
                $defaults = $properties['configurationDefaults'] ?? [];
                AccountConfiguration::updateOrCreate(
                    ['account_id' => $account->id],
                    [
                        'card_payment_forms' => json_encode($defaults['acceptedPaymentForms']['cardPaymentForms'] ?? []),
                        'cancellation_policy' => $defaults['cancellationPolicy']['policy'] ?? null,
                        'last_updated' => $defaults['lastUpdated'] ?? now(),
                        'merchant_name' => $defaults['merchantName'] ?? null,
                        'minimum_age' => $defaults['minimumAge'] ?? null,
                        'rental_agreement_url' => $defaults['rentalAgreement']['rentalAgreementPdfUrl'] ?? null,
                    ]
                );

                // Property-level configs (safe check)
                if (!empty($properties['properties']) && is_array($properties['properties'])) {
                    foreach ($properties['properties'] as $property) {
                        PropertyConfiguration::updateOrCreate(
                            [
                                'property_id' => $property['propertyId'] ?? null,
                            ],
                            [
                                'configurationContentUrl' => $property['configurationContentUrl'] ?? null,
                                'last_updated' => $property['lastUpdated'] ?? null,
                            ]
                        );
                    }
                }

                Log::info("Configurations updated successfully for account {$account->id}");
            } catch (\Throwable $e) {
                Log::error("Error processing account {$account->id}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }
}
