<?php

namespace App\Http\Controllers;

use App\Jobs\FetchPropertyJob;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\Property;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PropertyController extends Controller
{
    public function index($accountid): View
    {
        return view('properties', [
            'properties' => Property::where('account_id', $accountid)->get(),
            'accountid' => $accountid,
        ]);
    }

    public function fetch(Account $account): RedirectResponse
    {
        try {
            FetchPropertyJob::dispatch($account);

            return to_route('properties', $account->id)
                ->with('success', 'Properties updated successfully from API.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
