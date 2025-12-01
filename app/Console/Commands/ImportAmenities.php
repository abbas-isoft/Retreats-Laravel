<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PropertyContent;
use App\Models\Amenity;

class ImportAmenities extends Command
{
    protected $signature = 'import:amenities';
    protected $description = 'Import Hostaway amenities from property_contents JSON into amenities table & link';

    public function handle()
    {
        $properties = PropertyContent::all();

        foreach ($properties as $property) {

            $amenities = json_decode($property->amenities, true);

            if (!is_array($amenities)) {
                continue;
            }

            foreach ($amenities as $item) {

                // Hostaway format: { "id": 101, "name": "Wifi" }
                $name = is_array($item) ? ($item['name'] ?? null) : $item;

                if (!$name) continue;

                // Save or get existing amenity
                $amenity = Amenity::firstOrCreate(['name' => $name]);

                // Attach pivot without removing old links
                $property->amenities()->syncWithoutDetaching([$amenity->id]);
            }
        }

        $this->info('Hostaway amenities imported successfully.');
    }
}
