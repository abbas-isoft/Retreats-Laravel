<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NewsletterSubscription;
use Illuminate\Validation\ValidationException;

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
}
