<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to fetch accounts from SuperControl API and update the database';

    /**
     * Execute the console command.
     */
    public function handle(): void
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
