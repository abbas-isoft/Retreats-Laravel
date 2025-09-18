<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TestBooking;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;

class TestBookingController extends Controller
{
    public function bookTestProperty(Request $request)
    {
        $data = $request->validate([
            'start_date'        => 'required|date',
            'nights'            => 'required|integer',
            'guest_firstname'   => 'required|string',
            'guest_lastname'    => 'required|string',
            'email'             => 'required|email',
            'phone'             => 'required|string',
            'value'             => 'required|numeric', // base property price

            'adults'            => 'nullable|integer',
            'children'          => 'nullable|integer',
            'infants'           => 'nullable|integer',

            'dog_selected'      => 'required|boolean',
            'dog_count'         => 'nullable|integer',

            'hamper'            => 'nullable|boolean',
            'firewood'          => 'nullable|boolean',
            'decoration'        => 'nullable|boolean',

            'special_requests'  => 'nullable|string',

            'accepted_terms'    => 'required|boolean|in:1',
            'accepted_rules'    => 'required|boolean|in:1',
        ]);

        // Test property
        $testPropertyId = 659508;
        $bookingRef = strtoupper(uniqid('TEST-'));
        $bookingID = rand(100000, 999999);

        // --- Cost calculations ---
        $base_cents = (int) round($data['value'] * 100);

        // Dog fee calculation
        $dog_fee_cents_per = intval(env('DOG_FEE_CENTS', 5000)); // £50 default per dog
        $dog_fee_cents = ($data['dog_selected'] ? ($data['dog_count'] ?? 0) * $dog_fee_cents_per : 0);

        // Extras
        $extras_cents = 0;
        if (!empty($data['hamper'])) $extras_cents += 2000;      // £20
        if (!empty($data['firewood'])) $extras_cents += 1500;    // £15
        if (!empty($data['decoration'])) $extras_cents += 3000;  // £30

        $total_cents = $dog_fee_cents;

        $damage_deposit_cents = intval(env('PAYMENTS_DAMAGE_DEPOSIT_CENTS', 0));

        // --- Payment split ---
        $daysUntil = Carbon::parse($data['start_date'])->diffInDays(Carbon::now());
        if ($daysUntil < 60) {
            $upfront_cents = $total_cents; // full upfront
            $remaining_cents = 0;
        } else {
           $upfront_cents = (int) floor($total_cents * 0.25); // exact 25% without rounding up
$remaining_cents = $total_cents - $upfront_cents;
        }

        $remaining_payment_scheduled_at = $remaining_cents > 0 ? Carbon::parse($data['start_date'])->subDays(60) : null;
        $deposit_hold_scheduled_at = Carbon::parse($data['start_date'])->subDays(7);

        // --- Create local booking ---
        $booking = TestBooking::create([
            'property_id' => $testPropertyId,
            'start_date' => $data['start_date'],
            'nights' => $data['nights'],
            'guest_firstname' => $data['guest_firstname'],
            'guest_lastname' => $data['guest_lastname'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'value' => $data['value'],
            'booking_reference' => $bookingRef,
            'booking_id' => $bookingID,
            'supercontrol_response' => json_encode(['dummy' => 'Test booking created (no real API hit)']),

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

            'amount_cents' => $total_cents,
            'upfront_cents' => $upfront_cents,
            'remaining_cents' => $remaining_cents,
            'damage_deposit_cents' => $damage_deposit_cents,
            'remaining_payment_scheduled_at' => $remaining_payment_scheduled_at,
            'deposit_hold_scheduled_at' => $deposit_hold_scheduled_at,
            'payment_status' => 'pending',
        ]);

        // --- Stripe upfront payment ---
        Stripe::setApiKey(config('services.stripe.secret'));

        $stripeCustomer = Customer::create([
            'name' => $data['guest_firstname'] . ' ' . $data['guest_lastname'],
            'email' => $data['email'],
            'metadata' => [
                'local_booking_id' => $booking->id,
                'property_id' => $testPropertyId,
            ],
        ]);

        $clientSecret = null;
        if ($upfront_cents > 0) {
            $pi = PaymentIntent::create([
                'amount' => $upfront_cents,
                'currency' => 'gbp',
                'customer' => $stripeCustomer->id,
                'payment_method' => 'pm_card_visa', // frontend will provide actual PM
                'confirm' => true,
                'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
                'metadata' => [
                    'local_booking_id' => $booking->id,
                    'type' => 'upfront',
                ],
                'description' => "Upfront payment for booking {$booking->id}",
            ]);

            $clientSecret = $pi->client_secret;
            $booking->upfront_payment_intent_id = $pi->id;
            $booking->stripe_customer_id = $stripeCustomer->id;
            $booking->payment_status = $pi->status === 'succeeded' ? 'upfront_paid' : 'pending';
            $booking->save();
        }

        // --- Response for frontend ---
        $responseData = [
            'property_id' => $testPropertyId,
            'booking_ref' => $bookingRef,
            'booking_id' => $bookingID,
            'start_date' => $data['start_date'],
            'nights' => $data['nights'],
            'guests' => [
                'adults' => $data['adults'] ?? 0,
                'children' => $data['children'] ?? 0,
                'infants' => $data['infants'] ?? 0,
            ],
            'extras' => [
                'hamper' => !empty($data['hamper']),
                'firewood' => !empty($data['firewood']),
                'decoration' => !empty($data['decoration']),
                'dogs' => $data['dog_count'] ?? 0
            ],
            'special_requests' => $data['special_requests'] ?? null,
            'billing' => [
                'deposit_due_now' => $upfront_cents / 100,
                'balance_due' => $remaining_cents / 100,
                'balance_due_date' => $remaining_cents > 0 ? Carbon::parse($data['start_date'])->subDays(60)->toDateString() : null,
                'security_deposit' => $damage_deposit_cents / 100
            ],
            'stripe_client_secret' => $clientSecret,
            'payment_status' => $booking->payment_status,
            'payment_url' => $payment_url ?? null,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Test property booking created (only upfront charged).',
            'data' => $responseData
        ]);
    }
}
