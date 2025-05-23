<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Property;
use App\Models\PropertyConfiguration;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;


class ConfigurationController extends Controller
{
    public function index($id): View
    {
        return view('configuration', [
            'configurations' => PropertyConfiguration::where('account_id', $id)->get(),
            'account' => Account::find($id),
        ]);
    }

    public function fetch($account_id): RedirectResponse 
    {
        try {
            $controller = new SuperControlController;

            $accountId = Account::find($account_id);

            $controller->fetchAccountConfigurations($accountId->accountId);

                return redirect()->route('configurations.index', ['id' => $accountId->id])

                 ->with('success', 'Configurations updated successfully from API.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: '.$e->getMessage());
        }
    }




}
