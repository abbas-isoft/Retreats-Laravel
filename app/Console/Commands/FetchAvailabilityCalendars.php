<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Models\AvailabilityCalendar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchAvailabilityCalendars extends Command
{
    protected $signature = 'fetch:availability-calendars';
    protected $description = 'Fetch availability calendar data from API and update database';

    public function handle(): void
    {
        foreach (Property::all() as $property) {
            $propertyId = $property->id;

            $response = Http::withHeaders([
                'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
            ])->get("https://api.supercontrol.co.uk/v3/Properties/Availability/{$propertyId}");

            if ($response->failed()) {
                $this->error("Failed to fetch for Property ID: {$propertyId}");
                continue;
            }

            $data = $response->json();

            if (!isset($data['propertyAvailability'])) {
                $this->info("No availability data for Property ID: {$propertyId}");
                continue;
            }

            $availability = $data['propertyAvailability'];

            AvailabilityCalendar::updateOrCreate(
                ['property_id' => $propertyId],
                [
                    'start_date' => substr($availability['startDate'], 0, 10),
                    'end_date' => substr($availability['endDate'], 0, 10),
                    'availability' => $availability['availability'],
                    'change_over' => $availability['changeOver'],
                    'max_stay' => $availability['maxStay'],
                    'min_prior_notify' => $availability['minPriorNotify'],
                    'min_stay' => $availability['minStay'],
                ]
            );

            $this->info("✅ Updated availability for Property ID: {$propertyId}");
        }

        $this->info("🎉 All availability calendars fetched and saved.");
    }
}
