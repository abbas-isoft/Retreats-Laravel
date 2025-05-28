<?php

namespace App\Http\Controllers;

use App\Models\SingleConfiguration;

class SingleConfigurationController extends Controller
{
    public function showSingleConfiguration()
    {
        $config = SingleConfiguration::latest()->first(); // get latest record

        return view('show-single-configuration', compact('config'));
    }
}
