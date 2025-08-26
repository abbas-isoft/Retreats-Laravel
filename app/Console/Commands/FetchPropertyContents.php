<?php

namespace App\Console\Commands;

use App\Models\PropertyContent;
use App\Models\PropertyImage;
use App\Models\PropertyRoom;
use App\Models\RoomBed;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Jobs\FetchPropertyContentsJob;

class FetchPropertyContents extends Command
{
    protected $signature = 'fetch:property-contents';
    protected $description = 'Command to fetch property contents from SuperControl API and update the database';

    public function handle(): void
    {
        FetchPropertyContentsJob::dispatch();
        $this->info('FetchPropertyContentsJob dispatched.');
    }
}
