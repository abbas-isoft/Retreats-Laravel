<?php

namespace App\Jobs;

use App\Models\PropertyCalendar;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class FetchCalendarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $listingId, $startDate, $endDate;

    public function __construct($listingId, $startDate, $endDate)
    {
        $this->listingId = $listingId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function handle(): void
    {
        $url = "https://api.hostaway.com/v1/listings/{$this->listingId}/calendar?startDate={$this->startDate}&endDate={$this->endDate}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('HOSTAWAY_API_TOKEN'),
        ])->get($url);

        if ($response->failed()) {
            Log::error('Failed fetching calendar', $response->json());
            return;
        }

        $days = $response->json()['result'] ?? [];

        foreach ($days as $day) {
            PropertyCalendar::updateOrCreate(
                [
                    'listing_id' => $this->listingId,
                    'date'       => $day['date']
                ],
                [
                    'calendar_day_id'        => $day['id'],
                    'is_available'           => $day['isAvailable'],
                    'is_processed'           => $day['isProcessed'],
                    'status'                 => $day['status'],
                    'price'                  => $day['price'],
                    'minimum_stay'           => $day['minimumStay'],
                    'maximum_stay'           => $day['maximumStay'],
                    'closed_on_arrival'      => $day['closedOnArrival'],
                    'closed_on_departure'    => $day['closedOnDeparture'],
                    'note'                   => $day['note'],
                    'count_available_units'  => $day['countAvailableUnits'],
                    'available_units_to_sell'=> $day['availableUnitsToSell'],
                    'count_pending_units'    => $day['countPendingUnits'],
                    'count_blocking_reservations' => $day['countBlockingReservations'],
                    'count_blocked_units'    => $day['countBlockedUnits'],
                    'count_reserved_units'   => $day['countReservedUnits'],
                    'desired_units_to_sell'  => $day['desiredUnitsToSell'],
                    'reservations'           => $day['reservations'],
                ]
            );
        }
    }
}
