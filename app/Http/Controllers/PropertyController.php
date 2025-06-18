<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Property;
use Carbon\Carbon;

class PropertyController extends Controller
{
    public function index($propertyId)
    {

        $test = Account::where('accountId', $propertyId)->first()->id;
        
        return view('properties', [
            'properties' => Property::where('account_id', $test)->get(),
            'accountid' => $propertyId,
        ]);
    }

    public function fetch($propertyId)
{
    try {
        $controller = new SuperControlController;

        // Fix: Find by 'accountId' instead of primary 'id'
        $account = Account::where('accountId', $propertyId)->first();

        if (!$account) {
            return redirect()->back()->with('error', 'Account not found for accountId: ' . $propertyId);
        }

        $controller->fetchAccountProperties($account->accountId);

        return redirect()->route('properties', $propertyId)->with('success', 'Properties updated successfully from API.');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
    }
}
}