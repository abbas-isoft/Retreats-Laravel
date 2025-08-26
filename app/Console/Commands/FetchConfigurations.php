<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountConfiguration;
use App\Models\PropertyConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Jobs\FetchConfigurationsJob;

class FetchConfigurations  extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:configurations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch FetchConfigurationsJob to fetch and update Configurations from SuperControl API';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
       FetchConfigurationsJob::dispatch();
       $this->info('FetchConfigurationsJob dispatched.');
    }
}