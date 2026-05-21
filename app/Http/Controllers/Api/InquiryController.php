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

            /*
        |--------------------------------------------------------------------------
        | 7. Wait briefly for conversation creation
        |--------------------------------------------------------------------------
        */

            sleep(2);

            /*
        |--------------------------------------------------------------------------
        | 8. Fetch Conversation
        |--------------------------------------------------------------------------
        */

            $conversationResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ])->get(
                'https://api.hostaway.com/v1/conversations',
                [
                    'reservationId' => $reservationId
                ]
            );

            Log::info('Conversation response', [
                'response' => $conversationResponse->json()
            ]);

            if (!$conversationResponse->successful()) {

                return response()->json([
                    'status' => true,
                    'message' => 'Inquiry created but conversation not ready',
                    'data' => $reservationData
                ]);
            }

            $conversation = $conversationResponse->json()['result'][0] ?? null;

            if (!$conversation) {

                return response()->json([
                    'status' => true,
                    'message' => 'Inquiry created but no conversation found',
                    'data' => $reservationData
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | 9. IMPORTANT
        |--------------------------------------------------------------------------
        |
        | Inspect Hostaway conversation payload carefully.
        |
        | You need the relay/proxy email field.
        |
        */

            Log::info('Conversation payload', [
                'conversation' => $conversation
            ]);

            /*
        |--------------------------------------------------------------------------
        | 10. Extract Proxy Email
        |--------------------------------------------------------------------------
        */

            $proxyEmail =
                $conversation['proxyEmail']
                ?? $conversation['guestProxyEmail']
                ?? $conversation['email']
                ?? null;

            if (!$proxyEmail) {

                Log::warning('No proxy email found in conversation');

                return response()->json([
                    'status' => true,
                    'message' => 'Inquiry created but proxy email missing',
                    'data' => $reservationData
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | 11. Send REAL Email
        |--------------------------------------------------------------------------
        */

            Mail::raw($request->message, function ($message)
            use ($proxyEmail, $request) {

                $message->to($proxyEmail)

                    ->from(
                        env('MAIL_FROM_ADDRESS'),
                        'Group Retreats'
                    )

                    ->replyTo(
                        $request->email,
                        trim(
                            $request->first_name
                                . ' '
                                . $request->last_name
                        )
                    )

                    ->subject(
                        'Inquiry from '
                            . $request->first_name
                            . ' '
                            . $request->last_name
                    );
            });

            Log::info('Guest-originated email sent', [
                'proxy_email' => $proxyEmail
            ]);

            /*
        |--------------------------------------------------------------------------
        | 12. Done
        |--------------------------------------------------------------------------
        */

            return response()->json([
                'status' => true,
                'message' => 'Inquiry created successfully',
                'data' => $reservationData
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
