<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchPropertyExtrasJob;

class FetchPropertyExtras extends Command
{
    protected $signature = 'fetch:extras';
    protected $description = 'Fetch property extras for all properties and update database';

    public function handle(): void
    {
        FetchPropertyExtrasJob::dispatch();
        $this->info("🚀 FetchPropertyExtrasJob dispatched for all properties");
    }
}
