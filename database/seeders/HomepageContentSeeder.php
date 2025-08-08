<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HomepageContent; // ✅ Import the model

class HomepageContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HomepageContent::updateOrCreate(
            ['id' => 1],
            [
                'title' => 'Luxury Group Accommodation',
                'subtitle' => 'One-of-a-kind group stays, handpicked for big gatherings, special moments, and time well spent.',
                'background_image' => 'bg.jpg', // just path, we'll make it full URL in controller
            ]
        );
    }
}
