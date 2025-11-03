<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TestBooking;
use Stripe\Webhook;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('🚀 Stripe Webhook received', ['time' => now()]);

         Stripe::setApiKey(config('services.stripe.secret'));

        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        // ✅ Verify Webhook Signature
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
            Log::info('✅ Stripe signature verified successfully');
        } catch (\UnexpectedValueException $e) {
            Log::error('❌ Invalid payload received', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('❌ Invalid Stripe signature', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        $type = $event->type;
        $obj  = $event->data->object;

        Log::info("📦 Stripe Event Type: {$type}");

        /**
         * ✅ CHECKOUT SESSION COMPLETED
         */
        if ($type === 'checkout.session.completed') {
            Log::info('✅ Handling checkout.session.completed event');
            $session   = $obj;
            $bookingId = $session->metadata->local_booking_id ?? null;

            if ($bookingId) {
                Log::info("🔍 Booking found in metadata", ['booking_id' => $bookingId]);
                $booking = TestBooking::find($bookingId);
                if ($booking) {
                    Log::info("🧾 Updating booking payment details", ['booking_id' => $booking->id]);

                    if (!empty($session->payment_intent)) {
                        $paymentIntent = PaymentIntent::retrieve([
                            'id' => $session->payment_intent,
                            'expand' => ['charges']
                        ]);

                        $booking->update([
                            'payment_status'            => 'paid',
                            'stripe_payment_method_id'  => $paymentIntent->payment_method,
                            'upfront_payment_intent_id' => $paymentIntent->id,
                            'stripe_transaction_id'     => $paymentIntent->id,
                            'receipt_url'               => $paymentIntent->charges->data[0]->receipt_url ?? null,
                        ]);

                        Log::info('💰 Booking marked as paid', [
                            'booking_id' => $booking->id,
                            'intent_id' => $paymentIntent->id
                        ]);
                    } else {
                        $booking->update(['payment_status' => 'paid']);
                        Log::info('✅ Booking marked as paid without payment intent', ['booking_id' => $booking->id]);
                    }
                } else {
                    Log::warning('⚠️ Booking not found for checkout.session.completed', ['booking_id' => $bookingId]);
                }
            } else {
                Log::warning('⚠️ No local_booking_id found in session metadata');
            }
        }

        /**
         * ✅ PAYMENT INTENT SUCCEEDED
         */
        if ($type === 'payment_intent.succeeded') {
            Log::info('✅ Handling payment_intent.succeeded event');
            $pi = $obj;
            $bookingId = $pi->metadata->booking_id ?? null;
            $typeMeta  = $pi->metadata->type ?? null;

            Log::info('🧠 Payment Intent Metadata', ['booking_id' => $bookingId, 'type' => $typeMeta]);

            if ($bookingId) {
                $booking = TestBooking::where('booking_id' ,$bookingId)->first();
                if ($booking) {
                    try {
                        Log::info('🔄 Retrieving full payment intent details', ['intent_id' => $pi->id]);
                        $intent = PaymentIntent::retrieve([
                            'id' => $pi->id,
                            'expand' => ['charges']
                        ]);

                        $updateData = [
                            'stripe_payment_method_id' => $intent->payment_method,
                            'stripe_transaction_id'    => $intent->id,
                            'receipt_url'              => $intent->charges->data[0]->receipt_url ?? null,
                        ];

                        if ($typeMeta === 'upfront') {
                            $updateData['payment_status'] = ($booking->remaining_cents > 0) ? 'upfront_paid' : 'fully_paid';
                            $updateData['upfront_payment_intent_id'] = $intent->id;
                            Log::info('💵 Upfront payment processed', ['booking_id' => $booking->id]);
                        } elseif ($typeMeta === 'remaining') {
                            $updateData['remaining_payment_intent_id'] = $intent->id;
                            $updateData['payment_status'] = 'fully_paid';
                            Log::info('✅ Remaining balance paid', ['booking_id' => $booking->id]);
                        } elseif ($typeMeta === 'deposit_hold' || $typeMeta === 'deposit_charge') {
                            $updateData['damage_deposit_intent_id'] = $intent->id;
                            Log::info('🔒 Deposit hold/charge processed', ['booking_id' => $booking->id]);
                        }

                        $booking->update($updateData);

                        // ✅ Optional: Sync transaction with SuperControl
                        Log::info('🌍 Syncing transaction with SuperControl', ['booking_ref' => $booking->booking_reference]);
                        // Http::post('https://secure.supercontrol.co.uk/api/update-transaction', [
                        //     'booking_reference' => $booking->booking_reference,
                        //     'transaction_id'    => $intent->id,
                        //     'amount'            => $intent->amount_received / 100,
                        //     'currency'          => $intent->currency,
                        // ]);

                        Log::info("✅ Payment synced successfully for booking {$booking->id}");

                        // ✅ Send receipt email
                        try {
                            Mail::to($booking->email)->send(new \App\Mail\BookingConfirmed($booking));
                            // Mail::to('enquiries@groupretreats.co.uk')->send(new \App\Mail\NewBookingNotification($booking));
                            Log::info("📧 Emails sent for booking {$booking->id}");
                        } catch (\Exception $e) {
                            Log::error("⚠️ Mail send failed for booking {$booking->id}: {$e->getMessage()}");
                        }
                    } catch (\Exception $e) {
                        Log::error("⚠️ StripeWebhook Error (PI succeeded): {$e->getMessage()}", [
                            'booking_id' => $booking->id,
                            'intent_id' => $pi->id
                        ]);
                    }
                } else {
                    Log::warning('⚠️ Booking not found for payment_intent.succeeded', ['booking_id' => $bookingId]);
                }
            } else {
                Log::warning('⚠️ No local_booking_id found in payment intent metadata');
            }
        }

        /**
         * ❌ PAYMENT FAILED
         */
        if ($type === 'payment_intent.payment_failed') {
            Log::warning('❌ Handling payment_intent.payment_failed event');
            $pi        = $obj;
            $bookingId = $pi->metadata->booking_id ?? null;

            if ($bookingId) {
                $booking = TestBooking::find($bookingId);
                if ($booking) {
                    $booking->update(['payment_status' => 'failed']);
                    Log::warning("⚠️ Payment failed for booking {$booking->id}");
                } else {
                    Log::warning('⚠️ Booking not found for failed payment', ['booking_id' => $bookingId]);
                }
            } else {
                Log::warning('⚠️ No local_booking_id in failed payment intent metadata');
            }
        }

        /**
         * 🔓 DEPOSIT RELEASED
         */
        if ($type === 'payment_intent.canceled') {
            Log::info('🔓 Handling deposit release (payment_intent.canceled)');
            $intent  = $obj;
            $booking = TestBooking::where('damage_deposit_intent_id', $intent->id)->first();

            if ($booking) {
                $booking->update(['damage_deposit_intent_id' => null]);
                Log::info("💸 Deposit released for booking {$booking->id}");
            } else {
                Log::warning("⚠️ No booking found for released deposit intent {$intent->id}");
            }
        }

        /**
         * 💰 DEPOSIT HOLD UPDATED
         */
        if ($type === 'payment_intent.amount_capturable_updated') {
            Log::info('💰 Handling payment_intent.amount_capturable_updated event');
            $intent  = $obj;
            $booking = TestBooking::where('damage_deposit_intent_id', $intent->id)->first();

            if ($booking) {
                Log::info("🔒 Deposit hold active for booking {$booking->id}");
            } else {
                Log::warning("⚠️ Deposit hold updated but booking not found for intent {$intent->id}");
            }
        }

        Log::info('✅ Stripe Webhook processed successfully', [
            'event' => $type,
            'o'=> $obj
        ]);
        return response('OK', 200);
    }
}
