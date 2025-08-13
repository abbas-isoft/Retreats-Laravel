<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PropertyContent;
use App\Models\Amenity;

class ImportAmenities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * This is the exact name you'll run in terminal.
     */
    protected $signature = 'import:amenities';

    /**
     * The console command description.
     */
    protected $description = 'Import amenities from property_contents JSON into amenities table and link them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $properties = PropertyContent::all();

        foreach ($properties as $property) {
            $amenities = json_decode($property->amenities, true);

            if (is_array($amenities)) {
                foreach ($amenities as $amenityName) {
                    // Insert into amenities table if it doesn't exist
                    $amenity = Amenity::firstOrCreate(['name' => $amenityName]);

                    // Attach pivot record
                    $property->amenities()->syncWithoutDetaching([$amenity->id]);
                }
            }
        }

        $this->info('Amenities imported successfully.');
    }
}
