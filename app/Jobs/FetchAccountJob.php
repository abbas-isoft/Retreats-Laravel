<?php

namespace App\Jobs;

use App\Models\Account;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class FetchAccountJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
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
