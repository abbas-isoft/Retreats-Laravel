<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Account;
use App\Jobs\FetchAccountJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;

class AccountController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $accounts = Account::latest()->get();

            if ($accounts->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No accounts found.'
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => AccountResource::collection($accounts)
            ]);
        } catch (Exception $e) {
            Log::error('Account fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching accounts.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function fetch(): JsonResponse
    {
        try {
            FetchAccountJob::dispatch();

            return response()->json([
                'status' => false,
                'message' => 'Account data updated successfully from API.',
            ]);

        } catch (Exception $e) {
            Log::error('Account fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching accounts.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

