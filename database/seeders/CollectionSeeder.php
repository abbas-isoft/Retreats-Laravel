<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Collection;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        Collection::truncate(); // optional for fresh start

        Collection::insert([
            [
                'title' => 'Beautiful Gardens',
                'description' => 'Relax in well-maintained beautiful gardens.',
                'image' => 'images/beautiful_gardens.jpg',
            ],
            [
                'title' => 'Comfy Bedrooms',
                'description' => 'Sleep peacefully in luxury bedding.',
                'image' => 'images/comfy_bedrooms.jpg',
            ],
            [
                'title' => 'Swimming Pools',
                'description' => 'Enjoy refreshing swims in private pools.',
                'image' => 'images/swimming_pools.jpg',
            ],
            [
                'title' => 'Indoor Fire',
                'description' => 'Warm up beside our cozy indoor fireplaces.',
                'image' => 'images/indoor_fire.jpg',
            ],
            [
                'title' => 'Outdoor Dine-in',
                'description' => 'Dine in the lap of nature.',
                'image' => 'images/outdoor_dinein.jpg',
            ],
        ]);
    }
}
