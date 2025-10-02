<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NewsletterSubscription;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:newsletter_subscriptions,email',
        ]);

        $subscription = NewsletterSubscription::create([
            'email' => $request->email,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Thank you for subscribing to our newsletter!',
            'data' => $subscription
        ]);
    }

    // 👇 Admin ke liye index method
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
