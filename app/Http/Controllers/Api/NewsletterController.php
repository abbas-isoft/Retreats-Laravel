<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NewsletterSubscription;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|unique:newsletter_subscriptions,email',
            ]);

            $subscription = NewsletterSubscription::create([
                'email' => $request->email,
            ]);

            // Sync with HubSpot
            $this->syncWithHubSpot($request->email);

            return response()->json([
                'status' => true,
                'message' => 'Thank you for subscribing to our newsletter!',
                // 'data' => $subscription
            ], 201);
        } catch (ValidationException $e) {

            if (isset($e->errors()['email'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'This email already exists',
                ], 409);
            }

            return response()->json([
                'status' => false,
                'message' => 'Invalid data provided',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function index()
    {
        $subscriptions = NewsletterSubscription::latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'All subscribed emails fetched successfully.',
            'data' => $subscriptions
        ]);
    }

    private function syncWithHubSpot($email)
    {
        $token = env('HUBSPOT_ACCESS_TOKEN');

        if (!$token) {
            Log::warning('HubSpot Access Token is missing in .env');
            return;
        }

        try {
            $response = Http::withToken($token)
                ->post('https://api.hubapi.com/crm/v3/objects/contacts', [
                    'properties' => [
                        'email' => $email,
                        'firstname' => 'Newsletter',
                        'lastname' => 'Subscriber',
                        'company' => 'GR Website Newsletter Subscriber',
                    ]
                ]);

            if ($response->successful()) {
                Log::info('Email successfully synced with HubSpot: ' . $email);
            } else {
                Log::error('HubSpot sync failed for: ' . $email, [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception during HubSpot sync: ' . $e->getMessage());
        }
    }
}
