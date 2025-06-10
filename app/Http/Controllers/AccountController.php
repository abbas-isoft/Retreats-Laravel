<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Account;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AccountController extends Controller
{
    public function index(): View
    {
        return view('accounts', [
            'accounts' => Account::latest()->get(),
        ]);
    }

    public function fetch(): RedirectResponse
    {
        try {
            $accounts = Http::withHeader('SC-TOKEN', 'cddc35d0-1491-4b04-969c-9f8419ef0e99')
                ->get('https://api.supercontrol.co.uk/v3/properties/index')
                ->json();

            foreach ($accounts['accounts'] as $account) {
                Account::updateOrCreate(
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

            return to_route('accounts.index')
                ->with('success', 'Account data updated successfully from API.');

        } catch (Exception $e) {
            return back()->with('error', 'Someting went wrong');
        }
    }
}
