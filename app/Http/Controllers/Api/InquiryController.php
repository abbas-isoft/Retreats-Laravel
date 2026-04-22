<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\PropertyContent;

class InquiryController extends Controller
{
    /**
     * Send Inquiry to Hostaway
     */
    public function store(Request $request)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'listing_id' => 'required', 
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'phone'      => 'required|string|max:50',
            'check_in'   => 'required|date',
            'check_out'  => 'required|date|after:check_in',
            'guests'     => 'required|integer|min:1',
            'message'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Map local listing_id to Hostaway property_id
        $property = PropertyContent::where('id', $request->listing_id)
            ->orWhere('property_id', $request->listing_id)
            ->first();

        $hostawayId = $property ? $property->property_id : $request->listing_id;

        // 3. Send Inquiry to Hostaway using Reservations endpoint with status 'inquiry'
        try {
            $token = config('services.hostaway.token');
            Log::info('Sending Inquiry to Hostaway as Reservation', ['listing_id' => $hostawayId]);

            $url = 'https://api.hostaway.com/v1/reservations';
            $payload = [
                'listingId'      => (int) $hostawayId,
                'listingMapId'   => (int) $hostawayId, // Some Hostaway versions use this
                'arrivalDate'    => $request->check_in,
                'departureDate'  => $request->check_out,
                'guestFirstName' => $request->first_name,
                'guestLastName'  => $request->last_name,
                'guestName'      => trim($request->first_name . ' ' . $request->last_name),
                'guestEmail'     => $request->email,
                'guestPhone'     => $request->phone,
                'numberOfGuests' => (int) $request->guests,
                'status'         => 'inquiry',
                'hostNote'       => $request->message ?? 'New inquiry from website',
                'channelId'      => 2018, // Partners channel
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->retry(2, 100)->post($url, $payload);

            if ($response->successful()) {
                Log::info('Inquiry (Reservation) sent successfully');
                
                $reservationData = $response->json()['result'] ?? [];
                $reservationId = $reservationData['id'] ?? null;

                // 4. Send Message to Hostaway Inbox (Unified Inbox)
                if ($reservationId && $request->filled('message')) {
                    try {
                        Log::info('Sending message to Hostaway Inbox for Reservation: ' . $reservationId);
                        Http::withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                            'Content-Type'  => 'application/json',
                        ])->post('https://api.hostaway.com/v1/messages', [
                            'reservationId'     => $reservationId,
                            'body'              => $request->message,
                            'communicationType' => 'message'
                        ]);
                    } catch (\Exception $me) {
                        Log::error('Failed to send message to Hostaway Inbox: ' . $me->getMessage());
                    }
                }

                return response()->json([
                    'status'  => true,
                    'message' => 'Inquiry sent successfully to Hostaway as a Reservation (Status: inquiry)',
                    'data'    => $reservationData
                ]);
            }

            // If 403 Forbidden with channel ID, try without channelId or with 0
            if ($response->status() == 403) {
                $payload['channelId'] = 0; // Fallback to Direct/Manual
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ])->timeout(30)->post($url, $payload);

                if ($response->successful()) {
                    $reservationData = $response->json()['result'] ?? [];
                    $reservationId = $reservationData['id'] ?? null;

                    // Send Message to Inbox even in fallback
                    if ($reservationId && $request->filled('message')) {
                        Http::withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                            'Content-Type'  => 'application/json',
                        ])->post('https://api.hostaway.com/v1/messages', [
                            'reservationId'     => $reservationId,
                            'body'              => $request->message,
                            'communicationType' => 'message'
                        ]);
                    }

                    return response()->json([
                        'status'  => true,
                        'message' => 'Inquiry sent successfully (Fallback Channel 0)',
                        'data'    => $reservationData
                    ]);
                }
            }

            // Error Response
            return response()->json([
                'status'  => false,
                'message' => 'Failed to send inquiry to Hostaway (Reservation API)',
                'listing_sent' => $hostawayId,
                'error' => $response->json()['message'] ?? 'Resource not found',
                'hostaway_response' => $response->json()
            ], 400);

        } catch (\Exception $e) {
            Log::error('Inquiry Controller Exception: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while processing your inquiry',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
