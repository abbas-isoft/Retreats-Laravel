<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TestBooking;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\SetupIntent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Stripe\Checkout\Session as CheckoutSession;
use App\Mail\BookingConfirmed;
use Illuminate\Support\Facades\Mail;
use Stripe\Webhook;
use App\Jobs\FetchReceiptUrlJob;
use Illuminate\Support\Facades\DB;

class TestBookingController extends Controller
{
    //    public function bookTestProperty(Request $request)
    // {
    //     $data = $request->validate([
    //         'start_date'       => 'required|date',
    //         'nights'           => 'required|integer',
    //         'guest_firstname'  => 'required|string',
    //         'guest_lastname'   => 'required|string',
    //         'email'            => 'required|email',
    //         'phone'            => 'required|string',
    //         'total_price'      => 'required|numeric',
    //         'accepted_terms'   => 'required|boolean',
    //         'accepted_rules'   => 'required|boolean',
    //         'stripe_payment_method_id' => 'nullable|string',
    //     ]);

    //     $testPropertyId = 659508; // ✅ Static for now

    //     // 🧩 Check overlapping bookings dynamically (no end_date column)
    // $startDate = Carbon::parse($data['start_date']);
    // $endDate = $startDate->copy()->addDays($data['nights']); // calculate end date

    // $existingBooking = \App\Models\TestBooking::where('property_id', 659508)
    //     ->get()
    //     ->filter(function ($booking) {
    //         // Calculate each booking's end date dynamically
    //         $bookingStart = Carbon::parse($booking->start_date);
    //         $bookingEnd = $bookingStart->copy()->addDays($booking->nights);

    //         return $bookingEnd->greaterThan(Carbon::parse(request('start_date'))) &&
    //                $bookingStart->lessThan(Carbon::parse(request('start_date'))->copy()->addDays(request('nights')));
    //     })
    //     ->isNotEmpty();

    // if ($existingBooking) {
    //     return response()->json([
    //         'status' => false,
    //         'message' => 'Property is already booked for the selected dates.'
    //     ], 409);
    // }

    //     // ✅ Step 2: Mark old bookings as expired (freeing up property)
    //     TestBooking::where('property_id', $testPropertyId)
    //         ->where('payment_status', 'paid')
    //         ->whereRaw('DATE_ADD(start_date, INTERVAL nights DAY) < NOW()')
    //         ->update(['payment_status' => 'expired']);

    //     // ✅ Step 3: Continue with your booking creation
    //     $bookingRef = strtoupper(uniqid('TEST-'));
    //     $bookingID  = rand(100000, 999999);

    //     $totalCents = (int) round($data['total_price'] * 100);
    //     $daysUntilStart = Carbon::now()->diffInDays($startDate, false);

    //     if ($daysUntilStart <= 60) {
    //         $upfrontCents   = $totalCents;
    //         $remainingCents = 0;
    //         $remainingPaymentScheduledAt = null;
    //         $isFullPayment = true;
    //     } else {
    //         $upfrontCents   = (int) floor($totalCents * 0.25);
    //         $remainingCents = $totalCents - $upfrontCents;
    //         $remainingPaymentScheduledAt = (clone $startDate)->subDays(60);
    //         $isFullPayment = false;
    //     }

    //     // ✅ Fetch SuperControl property details
    //     $supercontrolUrl = "https://secure.supercontrol.co.uk/api/property/{$testPropertyId}";
    //     $response = Http::withHeaders([
    //         'Authorization' => 'Bearer ' . env('SUPERCONTROL_API_KEY'),
    //         'Accept'        => 'application/json',
    //     ])->get($supercontrolUrl);

    //     $property = $response->ok() ? $response->json() : [];

    //     // ✅ Fetch security deposit from local DB
    //     $priceConfig = DB::table('price_configurations')
    //         ->where('property_id', $testPropertyId)
    //         ->first();

    //     $securityDeposit = $priceConfig ? $priceConfig->security_deposit : 0;
    //     $damageDepositCents = intval($securityDeposit * 100);
    //     Log::info("💰 Security deposit fetched for property {$testPropertyId}: £{$securityDeposit}");

    //     $depositHoldScheduledAt = (clone $startDate)->subDays(7);

    //     // ✅ Create local booking
    //     $booking = TestBooking::create([
    //         'property_id'                   => $testPropertyId,
    //         'start_date'                    => $data['start_date'],
    //         'nights'                        => $data['nights'],
    //         'guest_firstname'               => $data['guest_firstname'],
    //         'guest_lastname'                => $data['guest_lastname'],
    //         'email'                         => $data['email'],
    //         'phone'                         => $data['phone'],
    //         'value'                         => $data['total_price'],
    //         'booking_reference'             => $bookingRef,
    //         'booking_id'                    => $bookingID,
    //         'supercontrol_response'         => json_encode($property),
    //         'accepted_terms'                => $data['accepted_terms'],
    //         'accepted_rules'                => $data['accepted_rules'],
    //         'amount_cents'                  => $totalCents,
    //         'upfront_cents'                 => $upfrontCents,
    //         'remaining_cents'               => $remainingCents,
    //         'damage_deposit_cents'          => $damageDepositCents,
    //         'remaining_payment_scheduled_at'=> $remainingPaymentScheduledAt,
    //         'deposit_hold_scheduled_at'     => $depositHoldScheduledAt,
    //         'payment_status'                => 'pending',
    //         'stripe_payment_method_id'      => $data['stripe_payment_method_id'] ?? null,
    //     ]);

    //     // ✅ Stripe setup
    //     Stripe::setApiKey(config('services.stripe.secret'));

    //     $stripeCustomer = Customer::create([
    //         'name'  => $data['guest_firstname'] . ' ' . $data['guest_lastname'],
    //         'email' => $data['email'],
    //         'phone' => $data['phone'],
    //         'metadata' => [
    //             'local_booking_id' => $booking->id,
    //             'property_id'      => $testPropertyId,
    //             'booking_ref'      => $bookingRef,
    //         ],
    //     ]);

    //     if ($isFullPayment) {
    //         $paymentIntent = PaymentIntent::create([
    //             'amount'   => $totalCents,
    //             'currency' => 'gbp',
    //             'customer' => $stripeCustomer->id,
    //             'receipt_email' => $data['email'],
    //             'payment_method_types' => ['card'],
    //             'description' => "Full payment for Booking {$bookingRef}",
    //             'metadata' => [
    //                 'booking_id'  => $booking->booking_id,
    //                 'booking_ref' => $bookingRef,
    //                 'guest_name'  => $data['guest_firstname'].' '.$data['guest_lastname'],
    //                 'property_id' => $testPropertyId,
    //                 'type'        => 'full',
    //             ],
    //             'statement_descriptor_suffix' => 'GR BOOKING',
    //         ]);
    //         $setupIntent = null;
    //     } else {
    //         $paymentIntent = PaymentIntent::create([
    //             'amount'   => $upfrontCents,
    //             'currency' => 'gbp',
    //             'customer' => $stripeCustomer->id,
    //             'receipt_email' => $data['email'],
    //             'payment_method_types' => ['card'],
    //             'description' => "25% Deposit for Booking {$bookingRef}",
    //             'metadata' => [
    //                 'booking_id'  => $booking->booking_id,
    //                 'booking_ref' => $bookingRef,
    //                 'guest_name'  => $data['guest_firstname'].' '.$data['guest_lastname'],
    //                 'property_id' => $testPropertyId,
    //                 'type'        => 'upfront',
    //             ],
    //             'statement_descriptor_suffix' => 'GR DEPOSIT',
    //             'setup_future_usage' => 'off_session',
    //         ]);

    //         $setupIntent = SetupIntent::create([
    //             'customer' => $stripeCustomer->id,
    //             'payment_method_types' => ['card'],
    //             'usage' => 'off_session',
    //             'metadata' => [
    //                 'booking_id' => $booking->id,
    //                 'type'       => 'remaining',
    //             ],
    //         ]);
    //     }

    //     $booking->update([
    //         'stripe_customer_id'        => $stripeCustomer->id,
    //         'upfront_payment_intent_id' => $paymentIntent->id,
    //         'setup_intent_id'           => $setupIntent?->id,
    //         'payment_status'            => 'pending',
    //     ]);

    //     // ✅ Email Confirmation
    //     // Mail::to($data['email'])->send(new BookingConfirmed($booking));

    //     // ✅ Final JSON Response
    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'Booking payment created successfully',
    //         'data'    => [
    //             'booking_reference'        => $bookingRef,
    //             'property_id'              => $testPropertyId,
    //             'booking_id'               => $bookingID,
    //             'database_id'              => $booking->id,
    //             'initial_payment'          => $upfrontCents / 100,
    //             'final_payment'            => $remainingCents / 100,
    //             'final_payment_due_date'   => $remainingPaymentScheduledAt?->toDateString(),
    //             'damage_deposit'           => $damageDepositCents / 100,
    //             'damage_deposit_hold_date' => $depositHoldScheduledAt->toDateString(),
    //             'client_secret'            => $paymentIntent->client_secret,
    //             'setup_client_secret'      => $setupIntent?->client_secret,
    //             'payment_status'           => $booking->payment_status,
    //         ]
    //     ]);
    // }


    //     // ✅ Webhook for payment_intent.succeeded to update booking + log receipt_url
    //    public function handleStripeWebhook(Request $request)
    // {
    //     // Debug: Log that webhook was called
    //     error_log("🎯 WEBHOOK CALLED: handleStripeWebhook method accessed");

    //     $payload = $request->getContent();
    //     $sigHeader = $request->header('Stripe-Signature');
    //     $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

    //     error_log("🔍 WEBHOOK PAYLOAD LENGTH: " . strlen($payload));
    //     error_log("🔍 STRIPE SIGNATURE PRESENT: " . ($sigHeader ? 'YES' : 'NO'));
    //     error_log("🔍 WEBHOOK SECRET SET: " . ($endpointSecret ? 'YES' : 'NO'));
    //     error_log("🔍 REQUEST METHOD: " . $request->method());
    //     error_log("🔍 REQUEST IP: " . $request->ip());

    //     // If payload is empty, this is likely a test call
    //     if (strlen($payload) === 0) {
    //         error_log("⚠️ Empty payload detected - this might be a test call");
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Webhook endpoint is working but payload is empty'
    //         ]);
    //     }

    //     // If no Stripe signature, skip signature verification for testing
    //     if (!$sigHeader) {
    //         error_log("⚠️ No Stripe signature - attempting to parse as JSON");
    //         try {
    //             $event = json_decode($payload, true);
    //             if (!$event) {
    //                 error_log("❌ Invalid JSON payload");
    //                 return response()->json(['error' => 'Invalid JSON payload'], 400);
    //             }
    //             error_log("🎉 JSON PARSED SUCCESSFULLY: " . ($event['type'] ?? 'no type'));
    //         } catch (\Exception $e) {
    //             error_log("❌ JSON PARSING FAILED: " . $e->getMessage());
    //             return response()->json(['error' => 'JSON parsing failed'], 400);
    //         }
    //     } else {
    //         // Normal Stripe webhook with signature verification
    //         try {
    //             $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
    //             error_log("🎉 WEBHOOK EVENT CONSTRUCTED: " . $event->type);
    //         } catch (\Exception $e) {
    //             error_log("❌ WEBHOOK CONSTRUCTION FAILED: " . $e->getMessage());
    //             return response()->json(['error' => $e->getMessage()], 400);
    //         }
    //     }

    //     // ✅ Only process payment success events
    //     if ($event->type === 'payment_intent.succeeded') {
    //         error_log("✅ PAYMENT INTENT SUCCEEDED EVENT DETECTED");

    //         $intent = $event->data->object;
    //         $bookingId = $intent->metadata->booking_id ?? null;

    //         error_log("🆔 BOOKING ID FROM METADATA: " . ($bookingId ?? 'NULL'));

    //         Log::info("PaymentIntent succeeded webhook received", [
    //             'intent_id' => $intent->id,
    //             'booking_id' => $bookingId,
    //             'charges_count' => isset($intent->charges->data) ? count($intent->charges->data) : 0,
    //             'has_charges' => isset($intent->charges),
    //             'intent_structure' => json_encode($intent, JSON_PRETTY_PRINT)
    //         ]);

    //         error_log("🔍 WEBHOOK DEBUG: PaymentIntent succeeded for booking ID: " . $bookingId);
    //         file_put_contents(
    //             storage_path('logs/webhook_debug.log'),
    //             date('Y-m-d H:i:s') . " - PaymentIntent succeeded for booking: " . $bookingId . "\n",
    //             FILE_APPEND | LOCK_EX
    //         );

    //         if ($bookingId) {
    //             $booking = TestBooking::where('booking_id', $bookingId)->first();

    //             if ($booking) {
    //                 $receiptUrl = null;

    //                 // Method 1: Try to get receipt_url from webhook payload
    //                 if (isset($intent->charges) && isset($intent->charges->data) && count($intent->charges->data) > 0) {
    //                     $receiptUrl = $intent->charges->data[0]->receipt_url ?? null;
    //                     Log::info("Receipt URL from webhook payload: " . ($receiptUrl ?? 'null'));
    //                     error_log("🧾 Receipt URL from webhook: " . ($receiptUrl ?? 'NULL'));
    //                 }

    //                 // Method 2: Fetch fresh PaymentIntent from Stripe API if needed
    //                 if (!$receiptUrl) {
    //                     try {
    //                         Stripe::setApiKey(config('services.stripe.secret'));
    //                         $freshIntent = PaymentIntent::retrieve([
    //                             'id' => $intent->id,
    //                             'expand' => ['charges']
    //                         ]);

    //                         if (isset($freshIntent->charges->data[0]->receipt_url)) {
    //                             $receiptUrl = $freshIntent->charges->data[0]->receipt_url;
    //                             Log::info("Receipt URL from fresh API call: " . $receiptUrl);
    //                             error_log("🔄 Receipt URL from fresh API: " . $receiptUrl);
    //                         }
    //                     } catch (\Exception $e) {
    //                         Log::error("Error fetching fresh PaymentIntent: " . $e->getMessage());
    //                     }
    //                 }

    //                 // ✅ Update booking with payment status and receipt URL
    //                 $booking->update([
    //                     'payment_status' => 'paid',
    //                     'receipt_url' => $receiptUrl,
    //                 ]);

    //                 if ($receiptUrl) {
    //                     Log::info("✅ Booking {$bookingId} marked paid. Receipt: {$receiptUrl}");
    //                     error_log("✅ SUCCESS: Booking {$bookingId} marked paid with receipt: {$receiptUrl}");
    //                 } else {
    //                     Log::warning("⚠️ Booking {$bookingId} marked paid but receipt_url is still null");
    //                     error_log("⚠️ WARNING: Booking {$bookingId} marked paid but NO receipt_url found");
    //                 }

    //                 // ✅ Send confirmation email after successful payment
    //                 try {
    //                     Mail::to($booking->email)->send(new BookingConfirmed($booking));
    //                     Log::info("📩 Booking confirmation email sent to {$booking->email}");
    //                     error_log("📩 Email successfully sent to {$booking->email}");
    //                 } catch (\Exception $e) {
    //                     Log::error("❌ Failed to send booking confirmation email: " . $e->getMessage());
    //                     error_log("❌ EMAIL SEND FAILED: " . $e->getMessage());
    //                 }
    //             }
    //         }
    //     }

    //     return response()->json(['status' => 'success']);
    // }

    //     // 🧪 Test method to check if logging works
    //     public function testLogging()
    //     {
    //         Log::emergency('🚨 EMERGENCY log test');
    //         Log::alert('🔔 ALERT log test');
    //         Log::critical('💥 CRITICAL log test');
    //         Log::error('❌ ERROR log test');
    //         Log::warning('⚠️ WARNING log test');
    //         Log::notice('📢 NOTICE log test');
    //         Log::info('ℹ️ INFO log test');
    //         Log::debug('🐛 DEBUG log test');

    //         return response()->json(['message' => 'Log tests written - check your log file']);
    //     }

    //     // 📋 Get booking details including receipt URL (after payment)
    //     public function getBookingDetails($bookingId)
    //     {
    //         $booking = TestBooking::where('booking_id', $bookingId)->first();

    //         if (!$booking) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Booking not found'
    //             ], 404);
    //         }

    //         // If payment is still pending, check Stripe for latest status
    //         if ($booking->payment_status === 'pending' && $booking->upfront_payment_intent_id) {
    //             try {
    //                 Stripe::setApiKey(config('services.stripe.secret'));
    //                 $paymentIntent = PaymentIntent::retrieve([
    //                     'id' => $booking->upfront_payment_intent_id,
    //                     'expand' => ['charges']
    //                 ]);

    //                 if ($paymentIntent->status === 'succeeded') {
    //                     $receiptUrl = $paymentIntent->charges->data[0]->receipt_url ?? null;

    //                     $booking->update([
    //                         'payment_status' => 'paid',
    //                         'receipt_url' => $receiptUrl,
    //                     ]);

    //                     error_log("📋 Booking {$booking->id} status updated to paid with receipt: " . ($receiptUrl ?? 'none'));
    //                 }
    //             } catch (\Exception $e) {
    //                 error_log("❌ Error checking payment status: " . $e->getMessage());
    //             }
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'data' => [
    //                 'booking_reference' => $booking->booking_reference,
    //                 'payment_status' => $booking->payment_status,
    //                 'receipt_url' => $booking->receipt_url,
    //                 'guest_name' => $booking->guest_firstname . ' ' . $booking->guest_lastname,
    //                 'email' => $booking->email,
    //                 'total_amount' => $booking->value,
    //                 'start_date' => $booking->start_date,
    //                 'nights' => $booking->nights,
    //             ]
    //         ]);
    //     }

    //     // 🧪 Simple test webhook (no Stripe signature verification)
    //     public function testWebhookSimple(Request $request)
    //     {
    //         error_log("🧪 SIMPLE WEBHOOK TEST CALLED at " . date('Y-m-d H:i:s'));
    //         error_log("🧪 REQUEST METHOD: " . $request->method());
    //         error_log("🧪 REQUEST HEADERS: " . json_encode($request->headers->all()));

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Simple webhook test successful',
    //             'timestamp' => date('Y-m-d H:i:s'),
    //             'method' => $request->method()
    //         ]);
    //     }

    //     // 🧪 Webhook WITHOUT signature verification for testing
    //     public function handleStripeWebhookTest(Request $request)
    //     {
    //         error_log("🎯 TEST WEBHOOK CALLED: handleStripeWebhookTest method accessed");

    //         $payload = $request->getContent();
    //         error_log("🔍 TEST WEBHOOK PAYLOAD LENGTH: " . strlen($payload));

    //         // Parse JSON without signature verification
    //         $event = json_decode($payload, true);

    //         if ($event && isset($event['type'])) {
    //             error_log("🎉 TEST WEBHOOK EVENT TYPE: " . $event['type']);

    //             if ($event['type'] === 'payment_intent.succeeded') {
    //                 error_log("✅ TEST PAYMENT INTENT SUCCEEDED EVENT DETECTED");

    //                 $intent = $event['data']['object'];
    //                 $bookingId = $intent['metadata']['booking_id'] ?? null;

    //                 error_log("🆔 TEST BOOKING ID FROM METADATA: " . ($bookingId ?? 'NULL'));

    //                 if ($bookingId) {
    //                     $booking = TestBooking::where('booking_id', $bookingId)->first();
    //                     if ($booking) {
    //                         error_log("📋 TEST BOOKING FOUND: " . $booking->booking_reference);

    //                         // Try to get receipt URL
    //                         $receiptUrl = null;
    //                         if (isset($intent['charges']['data'][0]['receipt_url'])) {
    //                             $receiptUrl = $intent['charges']['data'][0]['receipt_url'];
    //                             error_log("🧾 TEST Receipt URL found: " . $receiptUrl);
    //                         }

    //                         // Update booking
    //                         $booking->update([
    //                             'payment_status' => 'paid',
    //                             'receipt_url' => $receiptUrl,
    //                         ]);
    //                         try {
    //     Mail::to($booking->email)->send(new \App\Mail\RemainingPaymentMail($booking));
    // Log::info("📩 Booking confirmation email sent to {$booking->email}");
    // } catch (\Exception $e) {
    //     Log::error("❌ Failed to send booking confirmation email: " . $e->getMessage());
    // }

    //                         error_log("✅ TEST BOOKING UPDATED SUCCESSFULLY");
    //                     }
    //                 }
    //             }
    //         }

    //         return response()->json(['status' => 'success']);
    //     }

    //     // 🎯 Handle payment success immediately (called from frontend after payment confirmation)
    //     public function handlePaymentSuccess(Request $request)
    // {
    //     error_log("🎯 PAYMENT SUCCESS HANDLER CALLED");

    //     try {
    //         $data = $request->validate([
    //             'payment_intent_id' => 'required|string',
    //             'booking_id' => 'required|integer'
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Validation failed: ' . $e->getMessage()], 400);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         // Lock the booking row to prevent race conditions
    //         $booking = TestBooking::where('booking_id', $data['booking_id'])
    //             ->lockForUpdate()
    //             ->first();

    //         if (!$booking) {
    //             DB::rollBack();
    //             return response()->json(['error' => 'Booking not found'], 404);
    //         }

    //         // If already paid, no need to process further
    //         if ($booking->payment_status === 'paid') {
    //             DB::rollBack();
    //             return response()->json([
    //                 'status' => true,
    //                 'message' => 'This booking has already been confirmed and paid for.'
    //             ], 409);
    //         }

    //         $startDate = Carbon::parse($booking->start_date);
    //         $endDate = $startDate->copy()->addDays($booking->nights);

    //         // Check for overlapping paid bookings
    //         $overlappingBooking = TestBooking::where('property_id', $booking->property_id)
    //             ->where('payment_status', 'paid')
    //             ->lockForUpdate()
    //             ->get()
    //             ->filter(function ($existing) use ($startDate, $endDate) {
    //                 $existingStart = Carbon::parse($existing->start_date);
    //                 $existingEnd = $existingStart->copy()->addDays($existing->nights);
    //                 return $existingEnd->greaterThan($startDate) && $existingStart->lessThan($endDate);
    //             })
    //             ->isNotEmpty();

    //         if ($overlappingBooking) {
    //             DB::rollBack();
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Sorry, this property has just been booked by another user for your selected dates.'
    //             ], 409);
    //         }

    //         // Retrieve payment details from Stripe
    //         \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
    //         $paymentIntent = \Stripe\PaymentIntent::retrieve($data['payment_intent_id']);
    //         $receiptUrl = null;

    //         if (!empty($paymentIntent->latest_charge)) {
    //             $charge = \Stripe\Charge::retrieve($paymentIntent->latest_charge);
    //             $receiptUrl = $charge->receipt_url ?? null;
    //         }

    //         // Update the booking as paid
    //         $booking->update([
    //             'payment_status' => 'paid',
    //             'receipt_url'    => $receiptUrl,
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Booking confirmed successfully!',
    //             'data' => [
    //                 'booking_reference' => $booking->booking_reference,
    //                 'receipt_url' => $receiptUrl,
    //                 'payment_status' => 'paid'
    //             ]
    //         ]);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         error_log("❌ FAILED TO CONFIRM PAYMENT: " . $e->getMessage());
    //         return response()->json(['error' => 'Payment processing failed: ' . $e->getMessage()], 500);
    //     }
    // }



    public function bookTestProperty(Request $request)
{
    $propertyId = $request->propertyId;
    if (!$propertyId) {
        return response()->json([
            'status' => false,
            'message' => 'propertyId missing'
        ], 400);
    }

    $startDate = $request->start_date;
    $nights    = $request->nights ?? 1;
    $guests    = $request->guests ?? 1;
    $firstName = $request->guest_firstname;
    $lastName  = $request->guest_lastname;
    $email     = $request->email;
    $phone     = $request->phone;

    $checkIn  = Carbon::parse($startDate);
    $checkOut = $checkIn->copy()->addDays($nights);

    $payload = [
        "listingMapId"   => (int) $propertyId,
        "arrivalDate"    => $checkIn->format('Y-m-d'),
        "departureDate"  => $checkOut->format('Y-m-d'),
        "numberOfGuests" => $guests,
        "guestFirstName" => $firstName,
        "guestLastName"  => $lastName,
        "guestEmail"     => $email,
        "guestPhone"     => $phone,
        "guestName"      => trim($firstName . ' ' . $lastName),
        "channelId"      => 2000,
        "comment"        => "Created from website"
    ];

    try {
        /*
        |-------------------------------------------------------------------------- 
        | STEP 1 → CREATE RESERVATION ON HOSTAWAY
        |-------------------------------------------------------------------------- 
        */
        $reservationResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.hostaway.token'),
            'Accept'        => 'application/json',
        ])->post('https://api.hostaway.com/v1/reservations', $payload);

        $reservation = $reservationResponse->json();

        if (!isset($reservation['result']['id'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Hostaway reservation failed',
                'data'    => $reservation
            ], 500);
        }

        $reservationId = $reservation['result']['id'];

        /*
        |-------------------------------------------------------------------------- 
        | STEP 2 → SAVE PENDING BOOKING IN DB (with default amounts)
        |-------------------------------------------------------------------------- 
        */
        $booking = \App\Models\TestBooking::create([
            'property_id' => $propertyId,
            'start_date' => $startDate,
            'nights' => $nights,
            'guest_firstname' => $firstName,
            'guest_lastname' => $lastName,
            'email' => $email,
            'phone' => $phone,
            
            // 🔥 Default amounts for pending bookings
            'value' => 0,
            'amount_cents' => 0,
            'upfront_cents' => 0,
            'remaining_cents' => 0,
            'damage_deposit_cents' => 0,

            'payment_status' => 'pending',
            'booking_reference' => 'BR-' . strtoupper(uniqid()),
            'booking_id' => $reservationId,
            'supercontrol_response' => json_encode($reservation),
            'adults' => $request->adults ?? $guests,
            'children' => $request->children ?? 0,
            'infants' => $request->infants ?? 0,
            'address1' => $request->address1 ?? null,
            'town' => $request->town ?? null,
            'postcode' => $request->postcode ?? null,
            'country' => $request->country ?? null,
            'special_requests' => $request->special_requests ?? null,
        ]);

        /*
        |-------------------------------------------------------------------------- 
        | STEP 3 → RETURN HOSTAWAY GUEST PORTAL URL
        |-------------------------------------------------------------------------- 
        */
        $guestPortalUrl = $reservation['result']['guestPortalUrl'] 
            ?? "https://guest-portal.hostaway.com/{$reservationId}";

        return response()->json([
            'status' => true,
            'message' => 'Reservation created, redirect guest to Hostaway for payment',
            'data' => [
                'reservation_id' => $reservationId,
                'booking_db_id' => $booking->id,
                'guest_portal_url' => $guestPortalUrl
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

}