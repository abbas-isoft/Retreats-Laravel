<?php

namespace App\Jobs;

use App\Models\PropertyContent;
use App\Models\AvailabilityCalendar;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchAvailabilityCalendarsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('Hostaway Calendar Sync Started');

        foreach (PropertyContent::all() as $propertyContent) {


            $listingId  = !empty($propertyContent->hostaway_id)
                ? $propertyContent->hostaway_id
                : $propertyContent->property_id;

            $propertyId = $propertyContent->property_id;

            if (!$listingId) {
                Log::warning("Both Hostaway ID and Property ID missing for PropertyContent ID: {$propertyContent->id}");
                continue;
            }


            $startDate = now();
            $endDate   = now()->addDays(730);


            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.hostaway.token'),
                'Content-Type'  => 'application/json',
            ])->timeout(60)->retry(3, 2000)
                ->get("https://api.hostaway.com/v1/listings/$listingId/calendar", [
                    'startDate' => $startDate->format('Y-m-d'),
                    'endDate'   => $endDate->format('Y-m-d'),
                ]);

            $data = $response->json();

            if ($response->failed() || !isset($data['result'])) {
                Log::warning("Hostaway: Failed or empty calendar for listing ID: {$listingId}", ['response' => $data]);
                continue;
            }

            $hostawayCalendar = [];
            foreach ($data['result'] as $d) {
                $hostawayCalendar[$d['date']] = $d;
            }

            if (empty($hostawayCalendar)) {
                Log::warning("⚠ No Hostaway calendar data for listing: $listingId");
                continue;
            }

            // Build 2-year availability string
            $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate);

            $availabilityString = "";
            $lastDayData = null;

            foreach ($period as $date) {
                $d = $date->format('Y-m-d');
                $day = $hostawayCalendar[$d] ?? null;

                if ($day) {
                    $lastDayData = $day;
                }

                $availabilityString .= ($day['isAvailable'] ?? false) ? "Y" : "N";
            }

            
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            AvailabilityCalendar::updateOrCreate(
                [
                    'property_id' => $propertyId,   
                ],
                [
                    'start_date'       => $startDate->format('Y-m-d'),
                    'end_date'         => $endDate->format('Y-m-d'),
                    'availability'     => $availabilityString,
                    'change_over'      => $lastDayData['status'] ?? null,
                    'max_stay'         => $lastDayData['maximumStay'] ?? null,
                    'min_stay'         => $lastDayData['minimumStay'] ?? null,
                    'min_prior_notify' => 0,
                ]
            );
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            Log::info("Hostaway calendar updateOrCreate completed for PropertyContent ID: {$propertyContent->id}");
        }

        Log::info('Hostaway Calendar Sync Completed');
    }
}
