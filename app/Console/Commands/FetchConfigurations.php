<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountConfiguration;
use App\Models\PropertyConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchConfigurations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:configurations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to fetch property configurations from SuperControl API and update the database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        foreach (Account::all() as $account) {
            $response = Http::withHeaders([
                'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
            ])->get('https://api.supercontrol.co.uk/v3/properties/ConfigurationIndex/'.$account->id);

            $properties = $response->json();

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

            foreach ($properties['properties'] as $property) {
                PropertyConfiguration::updateOrCreate(
                    [
                        'property_id' => $property['propertyId'],
                    ],
                    [
                        'configurationContentUrl' => $property['configurationContentUrl'] ?? null,
                        'last_updated' => $property['lastUpdated'] ?? null,
                    ]
                );
            }
        }
    }
}
