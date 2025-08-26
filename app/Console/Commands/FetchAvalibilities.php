<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\PropertyAvailability;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Jobs\FetchAvailabilitiesJob;

class FetchAvalibilities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:avalibilities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch FetchAvailabilityJob to fetch and update property availability calendars';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $accounts = Account::all();

    foreach ($accounts as $account) {
        FetchAvailabilitiesJob::dispatch($account);
    }
}
}