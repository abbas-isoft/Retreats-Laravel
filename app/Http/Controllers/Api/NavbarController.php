<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;

class NavbarController extends Controller
{
    public function getNavbarData(): JsonResponse
    {
        // DYNAMIC navbar items (not coming from database)
        $navbarItems = [
            ['id' => 1, 'title' => 'Home', 'slug' => 'home'],
            ['id' => 2, 'title' => 'Collection', 'slug' => 'collection'],
            ['id' => 3, 'title' => 'Journal', 'slug' => 'journal'],
            ['id' => 4, 'title' => 'Inspiration', 'slug' => 'inspiration'],
            ['id' => 5, 'title' => 'Contact Us', 'slug' => 'contact-us'],
        ];

        // Get collection titles dynamically from DB
        $collections = Collection::select('id', 'title')->get();

        return response()->json([
            'status' => true,
            'message' => 'Navbar and Collection Data',
            'navbar_items' => $navbarItems,
            'collections_dropdown' => $collections,
        ]);
    }
}
