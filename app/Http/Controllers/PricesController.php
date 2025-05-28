<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\PropertyPrice;

class PricesController extends Controller
{
    public function index()
    {
        return view('prices', [
            'prices' => PropertyPrice::get(),
        ]);
    }

    public function fetch()
    {
        try {
            $account = Account::first();

            if (! $account) {
                return redirect()->back()->with('error', 'No account found.');
            }

            $controller = new SuperControlController;
            $controller->fetchAccountPrices($account->accountId);

            return redirect()->route('prices.index')->with('success', 'Prices updated successfully from API.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: '.$e->getMessage());
        }
    }
}
