<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Property;
use Carbon\Carbon;

class PropertyController extends Controller
{
    public function index()
    {
        return view('properties', [
            'properties' => Property::get(),
        ]);
    }

    public function fetch()
    {
        try {
            $controller = new SuperControlController;

            // Fetch properties for all accounts
            $accounts = Account::all();

            foreach ($accounts as $account) {
                $controller->fetchAccountProperties($account->accountId);
            }

            return redirect()->route('properties')->with('success', 'Properties updated successfully from API.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
