<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Jobs\FetchSinglePropertyJob;

class FetchSingleProperty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:single-property';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = ' Command to fetch a single property from SuperControl API and update the database';

    /**
     * Execute the console command.
     */
    
    public function handle(): void
    {
        // Dispatch the job to fetch a single property
        FetchSinglePropertyJob::dispatch();
        $this->info('FetchSinglePropertyJob dispatched.');
    }
}