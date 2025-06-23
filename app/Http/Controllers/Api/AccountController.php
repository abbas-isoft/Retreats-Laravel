<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $accounts = Account::latest()->get();

            if ($accounts->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No accounts found.',
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $accounts,
            ]);
        } catch (Exception $e) {
            Log::error('Account fetch failed: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching accounts.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
