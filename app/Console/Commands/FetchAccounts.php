<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchAccountsJob;

class FetchAccounts extends Command
{
    protected $signature = 'fetch:accounts';
    protected $description = 'Dispatch FetchAccountsJob to fetch accounts from SuperControl API';

    public function handle(): void
    {
        FetchAccountsJob::dispatch();
        $this->info('FetchAccountsJob dispatched.');
    }
}
      