<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SearchImageDomain extends Command
{
    protected $signature = 'app:search-image-domain';
    protected $description = 'Search, show and replace URL safely with accurate counts';

    public function handle()
    {
        $search = $this->ask('Enter URL/keyword to search');

        if (!$search) {
            $this->error('Search value required!');
            return;
        }

        $search = trim($search);

        // Find matching records
        $matches = DB::table('property_content_images')
            ->where('url', 'LIKE', "%{$search}%")
            ->get();

        $total = $matches->count();

        $this->info("TOTAL MATCHES FOUND: {$total}");

        if ($total === 0) {
            $this->warn("No records found.");
            return;
        }

        $this->info("\nMATCHED RECORDS:");

        foreach ($matches as $match) {
            $this->line("ID: {$match->id} | URL: {$match->url}");
        }

        // Confirm replacement
        if (!$this->confirm("\nDo you want to continue to replace?")) {
            return;
        }

        $replace = $this->ask('Enter NEW URL');

        if (!$replace) {
            $this->error('Replace URL required!');
            return;
        }

        // Update URLs
        $updated = DB::table('property_content_images')
            ->where('url', 'LIKE', "%{$search}%")
            ->update([
                'url' => DB::raw("REPLACE(url, '{$search}', '{$replace}')")
            ]);

        $this->info("\nDONE. Rows updated: {$updated}");
    }
}
