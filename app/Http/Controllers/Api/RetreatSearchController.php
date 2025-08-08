<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RetreatSearch;
use App\Http\Resources\RetreatSearchResource;

class RetreatSearchController extends Controller
{
   public function index(Request $request)
{
    $query = RetreatSearch::query();

    if ($request->has('city')) {
        $query->where('city', $request->city);
    }

    if ($request->has('check_in')) {
        $query->where('check_in', '<=', $request->check_in);
    }

    if ($request->has('check_out')) {
        $query->where('check_out', '>=', $request->check_out);
    }

    if ($request->has('guests')) {
        $query->where('guests', '>=', $request->guests);
    }

    $results = RetreatSearchResource::collection($query->get());

    return response()->json([
        'status' => true,
        'data' => $results,
    ]);
}
}

