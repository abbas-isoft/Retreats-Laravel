<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = Http::withHeader('SC-TOKEN', 'cddc35d0-1491-4b04-969c-9f8419ef0e99')
            ->get('https://api.supercontrol.co.uk/v3/properties/index')
            ->json();

        foreach ($accounts['accounts'] as $account) {
            Account::updateOrCreate(
                ['id' => $account['accountId']],
                [
                    'companyName' => $account['companyName'] ?? null,
                    'contentIndexUrl' => $account['contentIndexUrl'] ?? null,
                    'configurationIndexUrl' => $account['configurationIndexUrl'] ?? null,
                    'pricesIndexUrl' => $account['pricesIndexUrl'] ?? null,
                    'availabilityIndexUrl' => $account['availabilityIndexUrl'] ?? null,
                ]
            );
        }
    }
}
