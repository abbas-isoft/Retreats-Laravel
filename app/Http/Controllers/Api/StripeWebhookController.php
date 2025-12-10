<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\TestBooking;
use Carbon\Carbon;
use Stripe\Stripe;
use App\Http\Controllers\Controller;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

            if ($event->type == 'payment_intent.succeeded') {
                $paymentIntent = $event->data->object;

                $booking = TestBooking::where('stripe_payment_intent_id', $paymentIntent->id)->first();
                if ($booking) {
                    $checkIn = Carbon::parse($booking->start_date);
                    $checkOut = $checkIn->copy()->addDays($booking->nights);

                    $payload = [
                        "listingMapId" => $booking->property_id,
                        "arrivalDate" => $checkIn->format('Y-m-d'),
                        "departureDate" => $checkOut->format('Y-m-d'),
                        "numberOfGuests" => $booking->guests ?? 1,
                        "guestFirstName" => $booking->guest_firstname,
                        "guestLastName" => $booking->guest_lastname,
                        "guestEmail" => $booking->email,
                        "guestPhone" => $booking->phone,
                        "channelId" => 2000,
                        "comment" => "Created from website",
                        "paymentMethod" => $paymentIntent->id
                    ];

                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . env('HOSTAWAY_API_KEY'),
                        'Accept' => 'application/json',
                    ])->post('https://api.hostaway.com/v1/reservations', $payload);

                    $booking->update([
                        'payment_status' => 'paid',
                        'supercontrol_response' => json_encode($response->json())
                    ]);
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
