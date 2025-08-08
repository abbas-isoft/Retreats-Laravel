<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TestBooking;

class TestBookingController extends Controller
{
    public function bookTestProperty(Request $request)
    {
        $data = $request->validate([
            
            'start_date' => 'required|date',
            'nights' => 'required|integer',
            'guest_firstname' => 'required|string',
            'guest_lastname' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'value' => 'required|numeric',

            
            'address1' => 'nullable|string',
            'town' => 'nullable|string',
            'postcode' => 'nullable|string',
            'country' => 'nullable|string',

            
            'adults' => 'nullable|integer',
            'children' => 'nullable|integer',
            'infants' => 'nullable|integer',

            
            'dog_selected' => 'required|boolean',
            'dog_count' => 'nullable|integer',

            
            'hamper' => 'nullable|boolean',
            'firewood' => 'nullable|boolean',
            'decoration' => 'nullable|boolean',

            
            'special_requests' => 'nullable|string',

            
            'accepted_terms' => 'required|boolean|in:1',
            'accepted_rules' => 'required|boolean|in:1',
        ]);

       
        $testPropertyId = 999999;
        $yourRef = strtoupper(uniqid('TEST-'));
        $bookingID = rand(100000, 999999);
        $paymentURL = 'https://dummy.supercontrol.co.uk/payment/success';

        $booking = TestBooking::create([
            'property_id' => $testPropertyId,
            'start_date' => $data['start_date'],
            'nights' => $data['nights'],
            'guest_firstname' => $data['guest_firstname'],
            'guest_lastname' => $data['guest_lastname'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'value' => $data['value'],
            'booking_reference' => $yourRef,
            'booking_id' => $bookingID,
            'supercontrol_response' => json_encode(['dummy' => 'This is a test booking, no real API hit']),

            
            'address1' => $data['address1'] ?? null,
            'town' => $data['town'] ?? null,
            'postcode' => $data['postcode'] ?? null,
            'country' => $data['country'] ?? null,
            'adults' => $data['adults'] ?? 0,
            'children' => $data['children'] ?? 0,
            'infants' => $data['infants'] ?? 0,
            'special_requests' => $data['special_requests'] ?? null,

            
            'dog_selected' => $data['dog_selected'],
            'dog_count' => $data['dog_count'] ?? 0,

            
            'hamper' => $data['hamper'] ?? false,
            'firewood' => $data['firewood'] ?? false,
            'decoration' => $data['decoration'] ?? false,

           
            'accepted_terms' => $data['accepted_terms'],
            'accepted_rules' => $data['accepted_rules'],
        ]);

        return response()->json([
            'status' => 'success',
            'booking_ref' => $yourRef,
            'booking_id' => $bookingID,
            'payment_url' => $paymentURL,
            'message' => 'Test property booking created (all data saved)',
            'data' => $booking,
        ]);
    }
}
