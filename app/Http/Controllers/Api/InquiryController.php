<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\PropertyContent;
use Illuminate\Support\Facades\Mail;

class InquiryController extends Controller
{
    /**
     * Send Inquiry to Hostaway
     */
    public function store(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | 1. Validation
    |--------------------------------------------------------------------------
    */

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

        /*
    |--------------------------------------------------------------------------
    | 2. Find Property
    |--------------------------------------------------------------------------
    */

        $property = PropertyContent::where('id', $request->listing_id)
            ->orWhere('property_id', $request->listing_id)
            ->first();

        $hostawayId = $property
            ? $property->property_id
            : $request->listing_id;

        /*
    |--------------------------------------------------------------------------
    | 3. Create Inquiry in Hostaway
    |--------------------------------------------------------------------------
    */

        try {

            $token = config('services.hostaway.token');

            $payload = [

                'listingId'      => (int) $hostawayId,
                'listingMapId'   => (int) $hostawayId,

                'arrivalDate'    => $request->check_in,
                'departureDate'  => $request->check_out,

                'source'         => 'Website Inquiry',

                'guestFirstName' => $request->first_name,
                'guestLastName'  => $request->last_name,

                'guestName'      => trim(
                    $request->first_name . ' ' . $request->last_name
                ),

                'guestEmail'     => $request->email,
                'guestPhone'     => $request->phone,

                'numberOfGuests' => (int) $request->guests,

                'status'         => 'inquiry',

                'channelId'      => 2000,
            ];

            Log::info('Creating Hostaway Inquiry', $payload);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ])
                ->timeout(30)
                ->retry(2, 100)
                ->post(
                    'https://api.hostaway.com/v1/reservations',
                    $payload
                );

            /*
        |--------------------------------------------------------------------------
        | 4. Failed?
        |--------------------------------------------------------------------------
        */

            if (!$response->successful()) {

                Log::error('Hostaway reservation failed', [
                    'status' => $response->status(),
                    'body'   => $response->body()
                ]);

                return response()->json([
                    'status'  => false,
                    'message' => 'Failed to create inquiry',
                    'error'   => $response->json()
                ], 400);
            }

            /*
        |--------------------------------------------------------------------------
        | 5. Reservation Created
        |--------------------------------------------------------------------------
        */

            $reservationData = $response->json()['result'] ?? [];

            $reservationId = $reservationData['id'] ?? null;

            Log::info('Hostaway reservation created', [
                'reservation_id' => $reservationId
            ]);

            /*
        |--------------------------------------------------------------------------
        | 6. NO MESSAGE? RETURN
        |--------------------------------------------------------------------------
        */

            if (!$request->filled('message')) {

                return response()->json([
                    'status' => true,
                    'message' => 'Inquiry created successfully',
                    'data' => $reservationData
                ]);
            }

            // hostEmail is the guest-to-host relay; guestEmail is the reverse relay.
            $conversation = null;
            try {
                if (!$reservationId) {
                    throw new \RuntimeException('Hostaway did not return a reservation ID');
                }

                for ($attempt = 0; $attempt < 4; $attempt++) {
                    if ($attempt > 0) {
                        sleep(2);
                    }

                    $conversationResponse = Http::withToken($token)
                        ->acceptJson()->timeout(10)
                        ->get('https://api.hostaway.com/v1/conversations', [
                            'reservationId' => $reservationId,
                        ]);

                    if (!$conversationResponse->successful()) {
                        Log::warning('Hostaway conversation lookup failed', [
                            'reservation_id' => $reservationId,
                            'status' => $conversationResponse->status(),
                        ]);
                        break;
                    }

                    $conversation = collect($conversationResponse->json('result', []))
                        ->first(function ($item) use ($reservationId) {
                            return is_array($item)
                                && (string) ($item['reservationId'] ?? '') === (string) $reservationId
                                && ($item['type'] ?? '') === 'host-guest-email'
                                && filter_var($item['hostEmail'] ?? '', FILTER_VALIDATE_EMAIL);
                        });

                    if ($conversation) {
                        break;
                    }
                }

                if (!$conversation) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Inquiry created, but message could not be forwarded: email conversation not ready',
                        'message_status' => 'not_sent',
                        'data' => $reservationData,
                    ]);
                }

                Mail::raw($request->message, function ($mail) use ($conversation, $request) {
                    $guestName = trim($request->first_name . ' ' . $request->last_name);
                    $mail->to($conversation['hostEmail'])
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->replyTo($request->email, $guestName)
                        ->subject('Website inquiry from ' . $guestName);
                });
            } catch (\Exception $e) {
                Log::error('Hostaway inquiry message forwarding failed', [
                    'reservation_id' => $reservationId,
                    'message' => $e->getMessage(),
                ]);

                // Creation succeeded: a retry of the whole request would duplicate it.
                return response()->json([
                    'status' => true,
                    'message' => 'Inquiry created, but message could not be forwarded',
                    'message_status' => 'not_sent',
                    'data' => $reservationData,
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Inquiry created successfully; message forwarded to Hostaway',
                'message_status' => 'forwarded',
                'conversation_id' => $conversation['id'],
                'data' => $reservationData,
            ]);
        } catch (\Exception $e) {

            Log::error('Inquiry Exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
