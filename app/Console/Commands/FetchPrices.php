<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\PropertyPrice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Jobs\FetchPricesJob;

class FetchPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to fetch property prices from SuperControl API and update the database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
         FetchPricesJob::dispatch();
         $this->info('FetchPricesJob dispatched.');
    }
}