<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\HomepageContent;

class FetchHomepageContent extends Command
{
    protected $signature = 'fetch:homepage-content';
    protected $description = 'Fetch homepage content from SuperControl API and save to database';

    public function handle(): void
    {
        //  Replace this with the actual API URL for homepage content from SuperControl
        $response = Http::withHeader('SC-TOKEN', 'cddc35d0-1491-4b04-969c-9f8419ef0e99')
                        ->get('https://api.supercontrol.co.uk/v3/homepage/content');

        $data = $response->json();

        dd($data); // ADD THIS LINE
        if (!$data || !isset($data['title'])) {
            $this->error('Invalid or empty response from SuperControl API.');
            return;
        }

        HomepageContent::updateOrCreate(
            ['id' => 1], // assuming only one homepage content row
            [
                'title' => $data['title'] ?? null,
                'subtitle' => $data['subtitle'] ?? null,
                'background_image' => $data['background_image'] ?? null,
                // 'city_options' => json_encode($data['city_options'] ?? []),
            ]
        );

        $this->info('Homepage content fetched and saved successfully.');
    }
}
