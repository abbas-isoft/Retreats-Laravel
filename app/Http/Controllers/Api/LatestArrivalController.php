<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;
use App\Http\Resources\PropertyContentResource;
use Illuminate\Http\Request;

class LatestArrivalController extends Controller
{
    public function index(Request $request)
    {
       
        $filters = [
            'New Stays' => 'new_stays',
            'Without Hot Tubs' => 'without_hot_tubs',
            'Beach Stays' => 'beach_stays',
            'Luxury Stays' => 'luxury_stays',
        ];

        
        $selectedFilter = $request->query('filter', null);

        
        $query = PropertyContent::latest();

        
        if ($selectedFilter && isset($filters[$selectedFilter])) {
            switch ($filters[$selectedFilter]) {
                case 'new_stays':
                  
                    $query->whereDate('created_at', '>=', now()->subDays(30));
                    break;
                case 'without_hot_tubs':
                  
                    $query->where('has_hot_tub', 0);
                    break;
                case 'beach_stays':
                    
                    $query->where('location', 'like', '%beach%');
                    break;
                case 'luxury_stays':
                    
                    $query->where('property_type', 'luxury');
                    break;
            }
        }

       
        $arrivals = $query->take(10)->with(['images', 'rooms', 'beds'])->get();

        
        return response()->json([
            'status' => true,
            'message' => 'Latest arrivals fetched successfully.',
            'filters' => $filters, 
            'selected_filter' => $selectedFilter, 
            'latest_arrivals' => PropertyContentResource::collection($arrivals), 
        ]);
    }
}

