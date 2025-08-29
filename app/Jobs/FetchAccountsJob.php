<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FetchAccountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

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
         Log::info(' FetchAccountsJob started.');
        $accounts = Http::withHeader('SC-TOKEN', 'cddc35d0-1491-4b04-969c-9f8419ef0e99')
            ->get('https://api.supercontrol.co.uk/v3/properties/index')
            ->json();

        foreach ($accounts['accounts'] as $account) {
            \App\Models\Account::updateOrCreate(
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
         Log::info('✅ FetchAccountsJob completed.');
    }
}
