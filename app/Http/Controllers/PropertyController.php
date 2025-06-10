<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Property;
use Carbon\Carbon;

class PropertyController extends Controller
{
    public function index($accountid)
    {
        return view('properties', [
            'properties' => Property::where('account_id', $accountid)->get(),
            'accountid' => $accountid,
        ]);
    }

    public function fetch($accountid)
    {
        try {
            $controller = new SuperControlController;

            // Fetch properties for all accounts
            $account = Account::find($accountid);

            $controller->fetchAccountProperties($account->accountId);

            return redirect()->route('properties', $accountid)->with('success', 'Properties updated successfully from API.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
