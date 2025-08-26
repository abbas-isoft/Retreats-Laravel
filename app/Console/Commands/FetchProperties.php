<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Jobs\FetchPropertiesJob;

class FetchProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:properties';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to fetch properties from SuperControl API and update the database';

    /**
     * Execute the console command.
     */
   
    public function handle(): void
      {
          FetchPropertiesJob::dispatch();
          $this->info('FetchPropertiesJob dispatched.');
      }
}
