<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        Review::create([
            'review' => 'An absolutely brilliant home that is even bigger than the pictures make it look – which is hard to believe! We had a memorable birthday weekend with family and friends – thank you Martin for all your help and hospitality.',
            'reviewer_name' => 'Tom & Margot',
            'location' => 'Fawley Court',
        ]);

        // Add more reviews if needed
    }
}