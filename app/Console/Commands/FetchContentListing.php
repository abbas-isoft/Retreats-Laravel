<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Models\ContentListing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Jobs\FetchContentListingJob;

class FetchContentListing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:content-listing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch FetchContentListingJob to fetch and update Content Listing from SuperControl API';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {

        FetchContentListingJob::dispatch();
        $this->info('FetchContentListingJob dispatched.');
    }
}