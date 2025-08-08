<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroSection;
use App\Http\Resources\HeroSectionResource;

class HeroSectionController extends Controller
{
    public function show()
    {
        $hero = HeroSection::first();
        return new HeroSectionResource($hero);
    }
}
