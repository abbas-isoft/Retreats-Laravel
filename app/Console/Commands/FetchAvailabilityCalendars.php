<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchAvailabilityCalendarsJob;

class FetchAvailabilityCalendars extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'fetch:availability-calendars';

    /**
     * The console command description.
     */
    protected $description = 'Dispatch FetchAvailabilityCalendarsJob to fetch and update property availability calendars';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        FetchAvailabilityCalendarsJob::dispatch();
        $this->info('FetchAvailabilityCalendarsJob dispatched.');
    }
}
