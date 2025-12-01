<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchHostawayReviewsJob;

class FetchReviews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetchReviews';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Hostaway reviews and save them to the database';

    public function handle()
    {
        $this->info('Dispatching Hostaway reviews fetch job...');
        FetchHostawayReviewsJob::dispatch();
        $this->info('Job dispatched! Run php artisan queue:work to process it.');
    }
}
