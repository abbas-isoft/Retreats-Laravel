<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BookingController extends Controller
{
//     public function bookProperty(Request $request)
//     {
//         $data = $request->validate([
//             'property_id' => 'required',
//             'start_date' => 'required|date',
//             'nights' => 'required|integer',
//             'guest_firstname' => 'required',
//             'guest_lastname' => 'required',
//             'email' => 'required|email',
//             'phone' => 'required',
//             'value' => 'required|numeric',
//         ]);

//         $yourRef = strtoupper(uniqid());

//         $xmlBody = <<<XML
// <scAPI>
//   <client>
//     <ID>22826</ID>
//     <key>cddc35d0-1491-4b04-969c-9f8419ef0e99</key>
//     <ref>{$yourRef}</ref>
//     <prop>
//       <propID>{$data['property_id']}</propID>
//       <reset>false</reset>
//       <dates>
//         <date status="b" start="{$data['start_date']}" nights="{$data['nights']}" closed="0">
//           <BookingType>API</BookingType>
//           <yourRef>{$yourRef}</yourRef>
//           <siteID>0</siteID>
//           <PaymentRedirect>
//             <success></success>
//             <fail></fail>
//           </PaymentRedirect>
//           <guest>
//             <title>Mr</title>
//             <firstname><![CDATA[{$data['guest_firstname']}]]></firstname>
//             <lastname><![CDATA[{$data['guest_lastname']}]]></lastname>
//             <address1><![CDATA[123 Main Street]]></address1>
//             <town><![CDATA[City Town]]></town>
//             <postcode><![CDATA[12345]]></postcode>
//             <country><![CDATA[GB]]></country>
//             <email><![CDATA[{$data['email']}]]></email>
//             <telephone><![CDATA[{$data['phone']}]]></telephone>
//             <mobile><![CDATA[{$data['phone']}]]></mobile>
//           </guest>
//           <booking>
//             <value>{$data['value']}</value>
//             <vatPercentage>0</vatPercentage>
//             <pax adults="2" children="0" infants="0" />
//             <commission>0</commission>
//           </booking>
//           <payments>
//             <payment>
//               <date>{$data['start_date']}</date>
//               <value>{$data['value']}</value>
//               <caption>Deposit payment</caption>
//               <method>Credit card</method>
//             </payment>
//           </payments>
//         </date>
//       </dates>
//     </prop>
//   </client>
// </scAPI>
// XML;

//         $response = Http::withHeaders([
//             'Content-Type' => 'application/xml',
//         ])->withBody($xmlBody, 'application/xml')
//             ->post('https://secure.supercontrol.co.uk/api/avail.asp');

//         $responseBody = str_replace('&nbsp;', ' ', $response->body());
//         libxml_use_internal_errors(true);
//         $responseXml = simplexml_load_string($responseBody);

//         if ($responseXml === false) {
//             $errors = libxml_get_errors();
//             libxml_clear_errors();
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Failed to parse XML response.',
//                 'errors' => array_map(fn($e) => trim($e->message), $errors),
//                 'raw_response' => $responseBody,
//             ], 500);
//         }

//         $status = (string) $responseXml->status ?? 'UNKNOWN';
//         $bookingRefNode = $responseXml->bookingRef->yourRef ?? null;
//         $bookingRef = $bookingRefNode ? (string) $bookingRefNode : null;
//         $bookingID = $bookingRefNode ? (string) $bookingRefNode['bookingID'] : null;
//         $paymentURL = $bookingRefNode ? (string) $bookingRefNode['paymentURL'] : null;

//         Booking::create([
//             'property_id' => $data['property_id'],
//             'start_date' => $data['start_date'],
//             'nights' => $data['nights'],
//             'guest_firstname' => $data['guest_firstname'],
//             'guest_lastname' => $data['guest_lastname'],
//             'email' => $data['email'],
//             'phone' => $data['phone'],
//             'value' => $data['value'],
//             'booking_reference' => $bookingRef,
//             'booking_id' => $bookingID,
//             'supercontrol_response' => $responseBody,
//         ]);

//         return response()->json([
//             'status' => $status,
//             'booking_ref' => $bookingRef,
//             'booking_id' => $bookingID,
//             'payment_url' => $paymentURL,
//             'supercontrol_raw' => $responseBody,
//         ]);
//     }

//     public function syncAvailability($propertyId)
//     {
//         $xml = <<<XML
// <scAPI>
//   <client>
//     <ID>22826</ID>
//     <key>cddc35d0-1491-4b04-969c-9f8419ef0e99</key>
//     <prop>
//       <propID>{$propertyId}</propID>
//       <reset>true</reset>
//     </prop>
//   </client>
// </scAPI>
// XML;

//         $response = Http::withHeaders([
//             'Content-Type' => 'application/xml',
//         ])->withBody($xml, 'application/xml')
//             ->post('https://secure.supercontrol.co.uk/api/avail.asp');

//         $body = str_replace('&nbsp;', ' ', $response->body());
//         libxml_use_internal_errors(true);
//         $xmlObj = simplexml_load_string($body);

//         if ($xmlObj === false) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Failed to parse XML response.',
//                 'errors' => array_map(fn($e) => trim($e->message), libxml_get_errors()),
//                 'raw_response' => $body,
//             ], 500);
//         }

//         return response()->json([
//             'status' => (string) $xmlObj->status,
//             'message' => (string) $xmlObj->msg ?? 'No message',
//             'supercontrol_raw' => $body,
//         ]);
//     }

//     public function cancelBooking(Request $request)
//     {
//         $data = $request->validate([
//             'booking_id' => 'required',
//         ]);


//         $booking = Booking::where('booking_id', $data['booking_id'])->first();

//         if (!$booking) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Booking not found',
//             ], 404);
//         }

//         $yourRef = $booking->booking_reference;

//         $xml = <<<XML
// <scAPI>
//   <client>
//     <ID>22826</ID>
//     <key>cddc35d0-1491-4b04-969c-9f8419ef0e99</key>
//     <ref>{$yourRef}</ref>
//     <prop>
//       <propID>{$booking->property_id}</propID>
//       <reset>false</reset>
//       <dates>
//        <date status="a" start="{$booking->start_date}" nights="{$booking->nights}" closed="0">
//           <BookingType>API</BookingType>
//           <yourRef>{$yourRef}</yourRef>
//           <siteID>0</siteID>
//         </date>
//       </dates>
//     </prop>
//   </client>
// </scAPI>
// XML;

//         $response = Http::withHeaders([
//             'Content-Type' => 'application/xml',
//         ])->withBody($xml, 'application/xml')
//             ->post('https://secure.supercontrol.co.uk/api/avail.asp');

//         $body = str_replace('&nbsp;', ' ', $response->body());
//         libxml_use_internal_errors(true);
//         $xmlObj = simplexml_load_string($body);

//         if ($xmlObj === false) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Failed to parse XML response.',
//                 'errors' => array_map(fn($e) => trim($e->message), libxml_get_errors()),
//                 'raw_response' => $body,
//             ], 500);
//         }

//         return response()->json([
//             'status' => (string) $xmlObj->status,
//             'message' => (string) ($xmlObj->msg ?? 'No message'),
//             'supercontrol_raw' => $body,
//         ]);
//     }




    public function bookProperty(Request $request)
    {
        return response()->json([
            'status' => 'disabled',
            'message' => 'Booking is temporarily disabled due to client issue. Please try later.'
        ], 503);
    }
}
