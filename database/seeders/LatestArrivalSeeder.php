<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LatestArrival;

class LatestArrivalSeeder extends Seeder
{
    public function run(): void
    {
        LatestArrival::create([
            'title' => 'Glentworth Hall',
            'subtitle' => 'Contrary to popular belief, Lorem Ipsum is not simply random text.',
            'image' => 'latest_arrivals/glentworth-hall.jpeg', 
        ]);

         LatestArrival::create([
            'title' => 'Ashfield Place Farm',
            'subtitle' => 'Contrary to popular belief, Lorem Ipsum is not simply random text.',
            'image' => 'latest_arrivals/Ashfield_place_farm.jpeg', 
        ]);

         LatestArrival::create([
            'title' => 'Caer Beris Manor',
            'subtitle' => 'Contrary to popular belief, Lorem Ipsum is not simply random text.',
            'image' => 'latest_arrivals/Caer_manis.jpeg', 
        ]);

    }
}