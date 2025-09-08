<?php

namespace App\Console\Commands;

use App\Jobs\FetchPriceListingJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\PriceConfiguration;
use App\Models\PriceFee;
use App\Models\PriceLosEntry;
use App\Models\Account;   // Use Account model to loop through accounts
use App\Models\Property;  // Use Property if needed
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Exception;
class FetchPriceListing extends Command
{
    protected $signature = 'fetch:prices-Listing';
    protected $description = 'Command to fetch property prices from SuperControl API and update the database';

    public function handle(): void
    {
        FetchPriceListingJob::dispatch();
        $this->info('FetchPriceListingJob dispatched.');
    }
}