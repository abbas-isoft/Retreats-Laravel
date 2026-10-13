<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;

class NavbarController extends Controller
{
    public function getNavbarData(): JsonResponse
    {
        $navbarItems = [
            ['id' => 1, 'title' => 'Home', 'slug' => 'home'],
            ['id' => 2, 'title' => 'Collection', 'slug' => 'collection'],
            ['id' => 3, 'title' => 'Journal', 'slug' => 'journal'],
            ['id' => 4, 'title' => 'Inspiration', 'slug' => 'inspiration'],
            ['id' => 5, 'title' => 'Contact Us', 'slug' => 'contact-us'],
        ];

        $collections = Collection::select('id', 'title', 'show_in_navigation')->get();

        return response()->json([
            'status' => true,
            'message' => 'Navbar and Collection Data',
            'navbar_items' => $navbarItems,
            'collections_dropdown' => $collections,
        ]);
    }
}
