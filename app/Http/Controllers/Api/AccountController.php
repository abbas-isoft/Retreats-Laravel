<?php

// namespace App\Http\Controllers\Api;

// use App\Http\Resources\AccountResource;
// use Exception;
// use App\Models\Account;
// use Illuminate\Http\JsonResponse;
// use Illuminate\View\View;
// use App\Http\Controllers\Controller;
// use Illuminate\Support\Facades\Http;
// use Illuminate\Http\RedirectResponse;

// class AccountController extends Controller
// {
//     public function index(): JsonResponse
//     {
//         return response()->json([
//             'accounts' => AccountResource::collection(Account::latest()->get()),
//         ]);
//     }
// }


namespace App\Http\Controllers\Api;

use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
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
                    'message' => 'No accounts found.'
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => AccountResource::collection($accounts)
            ]);
        } catch (\Exception $e) {
            Log::error('Account fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching accounts.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

