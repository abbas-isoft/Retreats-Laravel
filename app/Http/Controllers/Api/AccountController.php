<?php

namespace App\Http\Controllers\Api;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Jobs\FetchAccountJob;

class AccountController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $accounts = \App\Models\Account::latest()->get();

            if ($accounts->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No accounts found.'
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $accounts
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
        
        $accountsResponse = \Illuminate\Support\Facades\Http::withHeaders([
            'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99'
        ])->get('https://api.supercontrol.co.uk/v3/properties/index');

        $accounts = $accountsResponse->json();

        if (!isset($accounts['accounts'])) {
            return response()->json([
                'status' => false,
                'message' => 'No accounts data found from API.'
            ], 404);
        }

        foreach ($accounts['accounts'] as $account) {
            \App\Models\Account::updateOrCreate(
                ['accountId' => $account['accountId']],
                [
                    'companyName' => $account['companyName'] ?? null,
                    'contentIndexUrl' => $account['contentIndexUrl'] ?? null,
                    'configurationIndexUrl' => $account['configurationIndexUrl'] ?? null,
                    'pricesIndexUrl' => $account['pricesIndexUrl'] ?? null,
                    'availabilityIndexUrl' => $account['availabilityIndexUrl'] ?? null,
                ]
            );
        }

        FetchAccountJob::dispatch();
        $updatedAccounts = \App\Models\Account::latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'Accounts data fetched, updated & job dispatched successfully.',
            'data' => $updatedAccounts
        ]);

    } catch (Exception $e) {
        \Illuminate\Support\Facades\Log::error('Account fetch failed: ' . $e->getMessage());

        return response()->json([
            'status' => false,
            'message' => 'Something went wrong while fetching accounts.',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
