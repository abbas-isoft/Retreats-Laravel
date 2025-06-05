<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Account;
use Illuminate\View\View;
use App\Jobs\FetchAccountJob;
use Illuminate\Support\Facades\Http;
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
            FetchAccountJob::dispatch();

            return to_route('accounts.index')
                ->with('success', 'Account data updated successfully from API.');

        } catch (Exception $e) {
            return back()->with('error', 'Someting went wrong');
        }
    }
}
