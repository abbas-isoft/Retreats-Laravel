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
 
class TestBookingController extends Controller
{
    public function bookTestProperty(Request $request)
    {
        $data = $request->validate([
            'start_date'       => 'required|date',
            'nights'           => 'required|integer',
            'guest_firstname'  => 'required|string',
            'guest_lastname'   => 'required|string',
            'email'            => 'required|email',
            'phone'            => 'required|string',
            'total_price'      => 'required|numeric',
            'accepted_terms'   => 'required|boolean',
            'accepted_rules'   => 'required|boolean',
            'stripe_payment_method_id' => 'nullable|string',
        ]);
 
        $testPropertyId = 659508;
        $bookingRef = strtoupper(uniqid('TEST-'));
        $bookingID  = rand(100000, 999999);
 
        $totalCents = (int) round($data['total_price'] * 100);
        $daysUntilStart = Carbon::now()->diffInDays(Carbon::parse($data['start_date']), false);
 
        if ($daysUntilStart <= 60) {
            $upfrontCents   = $totalCents;
            $remainingCents = 0;
            $remainingPaymentScheduledAt = null;
            $isFullPayment = true;
        } else {
            $upfrontCents   = (int) floor($totalCents * 0.25);
            $remainingCents = $totalCents - $upfrontCents;
            $remainingPaymentScheduledAt = Carbon::parse($data['start_date'])->subDays(60);
            $isFullPayment = false;
        }
 
        $supercontrolUrl = "https://secure.supercontrol.co.uk/api/property/{$testPropertyId}";
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('SUPERCONTROL_API_KEY'),
            'Accept'        => 'application/json',
        ])->get($supercontrolUrl);
 
        $property = $response->ok() ? $response->json() : [];
 
        $securityDeposit = $property['security_deposit'] ?? 0;
        $damageDepositCents = intval($securityDeposit * 100);
        if ($damageDepositCents < 5000) {
            $damageDepositCents = 5000; // default £50
        }
 
        $depositHoldScheduledAt = Carbon::parse($data['start_date'])->subDays(7);
 
        $booking = TestBooking::create([
            'property_id'                   => $testPropertyId,
            'start_date'                    => $data['start_date'],
            'nights'                        => $data['nights'],
            'guest_firstname'               => $data['guest_firstname'],
            'guest_lastname'                => $data['guest_lastname'],
            'email'                         => $data['email'],
            'phone'                         => $data['phone'],
            'value'                         => $data['total_price'],
            'booking_reference'             => $bookingRef,
            'booking_id'                    => $bookingID,
            'supercontrol_response'         => json_encode($property),
            'accepted_terms'                => $data['accepted_terms'],
            'accepted_rules'                => $data['accepted_rules'],
            'amount_cents'                  => $totalCents,
            'upfront_cents'                 => $upfrontCents,
            'remaining_cents'               => $remainingCents,
            'damage_deposit_cents'          => $damageDepositCents,
            'remaining_payment_scheduled_at' => $remainingPaymentScheduledAt,
            'deposit_hold_scheduled_at'     => $depositHoldScheduledAt,
            'payment_status'                => 'pending',
            'stripe_payment_method_id'      => $data['stripe_payment_method_id'] ?? null,
        ]);
 
        Stripe::setApiKey(config('services.stripe.secret'));
 
        $stripeCustomer = Customer::create([
            'name'  => $data['guest_firstname'] . ' ' . $data['guest_lastname'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'metadata' => [
                'local_booking_id' => $booking->id,
                'property_id'      => $testPropertyId,
                'booking_ref'      => $bookingRef,
            ],
        ]);
 
        if ($isFullPayment) {
            // 💳 One-shot full payment
            $paymentIntent = PaymentIntent::create([
                'amount'   => $totalCents,
                'currency' => 'gbp',
                'customer' => $stripeCustomer->id,
                'receipt_email' => $data['email'], // ✅ sends receipt to customer
                'payment_method_types' => ['card'],
                'description' => "Full payment for Booking {$bookingRef}",
                'metadata' => [
                    'booking_id'      => $booking->id,
                    'booking_ref'     => $bookingRef,
                    'guest_name'      => $data['guest_firstname'].' '.$data['guest_lastname'],
                    'property_id'     => $testPropertyId,
                    'type'            => 'full',
                ],
                'statement_descriptor_suffix' => 'GR BOOKING',
            ]);
           
            $setupIntent = null;
        } else {
            // 💰 Split payment: 25% now, 75% later
            $paymentIntent = PaymentIntent::create([
                'amount'   => $upfrontCents,
                'currency' => 'gbp',
                'customer' => $stripeCustomer->id,
                'receipt_email' => $data['email'],
                'payment_method_types' => ['card'],
                'description' => "25% Deposit for Booking {$bookingRef}",
                'metadata' => [
                    'booking_id'  => $booking->id,
                    'booking_ref' => $bookingRef,
                    'guest_name'  => $data['guest_firstname'].' '.$data['guest_lastname'],
                    'property_id' => $testPropertyId,
                    'type'        => 'upfront',
                ],
                'statement_descriptor_suffix' => 'GR DEPOSIT',
                'setup_future_usage' => 'off_session',
            ]);
 
            $setupIntent = SetupIntent::create([
                'customer' => $stripeCustomer->id,
                'payment_method_types' => ['card'],
                'usage' => 'off_session',
                'metadata' => [
                    'booking_id' => $booking->id,
                    'type'       => 'remaining',
                ],
            ]);
        }
 
        $booking->update([
            'stripe_customer_id'        => $stripeCustomer->id,
            'upfront_payment_intent_id' => $paymentIntent->id,
            'setup_intent_id'           => $setupIntent?->id,
            'payment_status'            => 'pending',
        ]);
        Mail::to($data['email'])->send(new BookingConfirmed($booking));
        return response()->json([
            'status'  => true,
            'message' => 'Booking payment created successfully',
            'data'    => [
                'booking_reference'        => $bookingRef,
                'property_id'              => $testPropertyId,
                'booking_id'               => $bookingID,
                'initial_payment'          => $upfrontCents / 100,
                'final_payment'            => $remainingCents / 100,
                'final_payment_due_date'   => $remainingPaymentScheduledAt?->toDateString(),
                'damage_deposit'           => $damageDepositCents / 100,
                'damage_deposit_hold_date' => $depositHoldScheduledAt->toDateString(),
                'client_secret'            => $paymentIntent->client_secret,
                'setup_client_secret'      => $setupIntent?->client_secret,
                'payment_status'           => $booking->payment_status,
                // receipt_url will be available after payment success via webhook
            ]
        ]);
    }
 
    // ✅ Webhook for payment_intent.succeeded to update booking + log receipt_url
    public function handleStripeWebhook(Request $request)
    {
        // Debug: Log that webhook was called
        error_log("🎯 WEBHOOK CALLED: handleStripeWebhook method accessed");
        
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');
        
        error_log("🔍 WEBHOOK PAYLOAD LENGTH: " . strlen($payload));
        error_log("🔍 STRIPE SIGNATURE PRESENT: " . ($sigHeader ? 'YES' : 'NO'));
        error_log("🔍 WEBHOOK SECRET SET: " . ($endpointSecret ? 'YES' : 'NO'));
 
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            error_log("🎉 WEBHOOK EVENT CONSTRUCTED: " . $event->type);
        } catch (\Exception $e) {
            error_log("❌ WEBHOOK CONSTRUCTION FAILED: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
 
        if ($event->type === 'payment_intent.succeeded') {
            error_log("✅ PAYMENT INTENT SUCCEEDED EVENT DETECTED");
            $intent = $event->data->object;
            $bookingId = $intent->metadata->booking_id ?? null;
            
            error_log("🆔 BOOKING ID FROM METADATA: " . ($bookingId ?? 'NULL'));
            
            // Debug: Log the entire intent object structure
            Log::info("PaymentIntent succeeded webhook received", [
                'intent_id' => $intent->id,
                'booking_id' => $bookingId,
                'charges_count' => isset($intent->charges->data) ? count($intent->charges->data) : 0,
                'has_charges' => isset($intent->charges),
                'intent_structure' => json_encode($intent, JSON_PRETTY_PRINT)
            ]);

            // Alternative logging methods for debugging
            error_log("🔍 WEBHOOK DEBUG: PaymentIntent succeeded for booking ID: " . $bookingId);
            file_put_contents(storage_path('logs/webhook_debug.log'), 
                date('Y-m-d H:i:s') . " - PaymentIntent succeeded for booking: " . $bookingId . "\n", 
                FILE_APPEND | LOCK_EX
            );
 
            if ($bookingId) {
                $booking = TestBooking::find($bookingId);
                if ($booking) {
                    $receiptUrl = null;
                    
                    // Method 1: Try to get receipt_url from charges in the webhook payload
                    if (isset($intent->charges) && isset($intent->charges->data) && count($intent->charges->data) > 0) {
                        $receiptUrl = $intent->charges->data[0]->receipt_url ?? null;
                        Log::info("Receipt URL from webhook payload: " . ($receiptUrl ?? 'null'));
                        error_log("🧾 Receipt URL from webhook: " . ($receiptUrl ?? 'NULL'));
                    }
                    
                    // Method 2: If receipt_url is still null, fetch fresh PaymentIntent from Stripe API
                    if (!$receiptUrl) {
                        try {
                            Stripe::setApiKey(config('services.stripe.secret'));
                            $freshIntent = PaymentIntent::retrieve([
                                'id' => $intent->id,
                                'expand' => ['charges']
                            ]);
                            
                            if (isset($freshIntent->charges->data[0]->receipt_url)) {
                                $receiptUrl = $freshIntent->charges->data[0]->receipt_url;
                                Log::info("Receipt URL from fresh API call: " . $receiptUrl);
                                error_log("🔄 Receipt URL from fresh API: " . $receiptUrl);
                            }
                        } catch (\Exception $e) {
                            Log::error("Error fetching fresh PaymentIntent: " . $e->getMessage());
                        }
                    }
                    
                    // Update booking with payment status and receipt URL
                    $booking->update([
                        'payment_status' => 'paid',
                        'receipt_url' => $receiptUrl,
                    ]);
                    
                    if ($receiptUrl) {
                        Log::info("✅ Booking {$bookingId} marked paid. Receipt: {$receiptUrl}");
                        error_log("✅ SUCCESS: Booking {$bookingId} marked paid with receipt: {$receiptUrl}");
                    } else {
                        Log::warning("⚠️ Booking {$bookingId} marked paid but receipt_url is still null");
                        error_log("⚠️ WARNING: Booking {$bookingId} marked paid but NO receipt_url found");
                    }
                }
            }
        }
 
        return response()->json(['status' => 'success']);
    }

    // 🧪 Test method to check if logging works
    public function testLogging()
    {
        Log::emergency('🚨 EMERGENCY log test');
        Log::alert('🔔 ALERT log test');
        Log::critical('💥 CRITICAL log test');
        Log::error('❌ ERROR log test');
        Log::warning('⚠️ WARNING log test');
        Log::notice('📢 NOTICE log test');
        Log::info('ℹ️ INFO log test');
        Log::debug('🐛 DEBUG log test');
        
        return response()->json(['message' => 'Log tests written - check your log file']);
    }

    // 📋 Get booking details including receipt URL (after payment)
    public function getBookingDetails($bookingId)
    {
        $booking = TestBooking::find($bookingId);
        
        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        // If payment is still pending, check Stripe for latest status
        if ($booking->payment_status === 'pending' && $booking->upfront_payment_intent_id) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $paymentIntent = PaymentIntent::retrieve([
                    'id' => $booking->upfront_payment_intent_id,
                    'expand' => ['charges']
                ]);

                if ($paymentIntent->status === 'succeeded') {
                    $receiptUrl = $paymentIntent->charges->data[0]->receipt_url ?? null;
                    
                    $booking->update([
                        'payment_status' => 'paid',
                        'receipt_url' => $receiptUrl,
                    ]);
                    
                    error_log("📋 Booking {$booking->id} status updated to paid with receipt: " . ($receiptUrl ?? 'none'));
                }
            } catch (\Exception $e) {
                error_log("❌ Error checking payment status: " . $e->getMessage());
            }
        }

        return response()->json([
            'status' => true,
            'data' => [
                'booking_reference' => $booking->booking_reference,
                'payment_status' => $booking->payment_status,
                'receipt_url' => $booking->receipt_url,
                'guest_name' => $booking->guest_firstname . ' ' . $booking->guest_lastname,
                'email' => $booking->email,
                'total_amount' => $booking->value,
                'start_date' => $booking->start_date,
                'nights' => $booking->nights,
            ]
        ]);
    }

    // 🧪 Simple test webhook (no Stripe signature verification)
    public function testWebhookSimple(Request $request)
    {
        error_log("🧪 SIMPLE WEBHOOK TEST CALLED at " . date('Y-m-d H:i:s'));
        error_log("🧪 REQUEST METHOD: " . $request->method());
        error_log("🧪 REQUEST HEADERS: " . json_encode($request->headers->all()));
        
        return response()->json([
            'status' => 'success',
            'message' => 'Simple webhook test successful',
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $request->method()
        ]);
    }

    // 🧪 Webhook WITHOUT signature verification for testing
    public function handleStripeWebhookTest(Request $request)
    {
        error_log("🎯 TEST WEBHOOK CALLED: handleStripeWebhookTest method accessed");
        
        $payload = $request->getContent();
        error_log("🔍 TEST WEBHOOK PAYLOAD LENGTH: " . strlen($payload));
        
        // Parse JSON without signature verification
        $event = json_decode($payload, true);
        
        if ($event && isset($event['type'])) {
            error_log("🎉 TEST WEBHOOK EVENT TYPE: " . $event['type']);
            
            if ($event['type'] === 'payment_intent.succeeded') {
                error_log("✅ TEST PAYMENT INTENT SUCCEEDED EVENT DETECTED");
                
                $intent = $event['data']['object'];
                $bookingId = $intent['metadata']['booking_id'] ?? null;
                
                error_log("🆔 TEST BOOKING ID FROM METADATA: " . ($bookingId ?? 'NULL'));
                
                if ($bookingId) {
                    $booking = TestBooking::find($bookingId);
                    if ($booking) {
                        error_log("📋 TEST BOOKING FOUND: " . $booking->booking_reference);
                        
                        // Try to get receipt URL
                        $receiptUrl = null;
                        if (isset($intent['charges']['data'][0]['receipt_url'])) {
                            $receiptUrl = $intent['charges']['data'][0]['receipt_url'];
                            error_log("🧾 TEST Receipt URL found: " . $receiptUrl);
                        }
                        
                        // Update booking
                        $booking->update([
                            'payment_status' => 'paid',
                            'receipt_url' => $receiptUrl,
                        ]);
                        
                        error_log("✅ TEST BOOKING UPDATED SUCCESSFULLY");
                    }
                }
            }
        }
        
        return response()->json(['status' => 'success']);
    }

    // 🎯 Handle payment success immediately (called from frontend after payment confirmation)
    public function handlePaymentSuccess(Request $request)
    {
        $data = $request->validate([
            'payment_intent_id' => 'required|string',
            'booking_id' => 'required|integer'
        ]);

        error_log("🎯 PAYMENT SUCCESS HANDLER CALLED for booking: " . $data['booking_id']);

        $booking = TestBooking::find($data['booking_id']);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        try {
            // Fetch fresh payment intent from Stripe to get receipt URL
            Stripe::setApiKey(config('services.stripe.secret'));
            $paymentIntent = PaymentIntent::retrieve([
                'id' => $data['payment_intent_id'],
                'expand' => ['charges']
            ]);

            $receiptUrl = null;
            if (isset($paymentIntent->charges->data[0]->receipt_url)) {
                $receiptUrl = $paymentIntent->charges->data[0]->receipt_url;
                error_log("🧾 Receipt URL retrieved: " . $receiptUrl);
            }

            // Update booking
            $booking->update([
                'payment_status' => 'paid',
                'receipt_url' => $receiptUrl,
            ]);

            // Send email with receipt URL
            if ($receiptUrl) {
                $emailSubject = "Payment Receipt - Booking {$booking->booking_reference}";
                $emailBody = "Dear {$booking->guest_firstname},\n\n";
                $emailBody .= "Your payment has been confirmed for booking {$booking->booking_reference}.\n\n";
                $emailBody .= "Receipt URL: {$receiptUrl}\n\n";
                $emailBody .= "Thank you for your booking!";
                
                try {
                    Mail::raw($emailBody, function ($message) use ($booking, $emailSubject) {
                        $message->to($booking->email)->subject($emailSubject);
                    });
                    error_log("📧 Receipt email sent to: " . $booking->email);
                } catch (\Exception $e) {
                    error_log("❌ Failed to send receipt email: " . $e->getMessage());
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Payment processed successfully',
                'data' => [
                    'payment_status' => $booking->payment_status,
                    'receipt_url' => $receiptUrl,
                    'booking_reference' => $booking->booking_reference
                ]
            ]);

        } catch (\Exception $e) {
            error_log("❌ Error processing payment success: " . $e->getMessage());
            return response()->json(['error' => 'Failed to process payment'], 500);
        }
    }
}