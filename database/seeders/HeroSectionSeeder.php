<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HeroSection;

class HeroSectionSeeder extends Seeder
{
    public function run(): void
    {
        HeroSection::updateOrCreate(
            ['title' => 'Hotel amenities without hotel formality'],
            [
                'subtitle' => 'Born through a passion for hospitality and heritage, Group Retreats is proud to provide the exclusive hire of large group accommodation and luxury mansions for a truly unique stay in a homely environment.',
                'button_text' => 'Start your Journey',
                'background_video_url' => 'videos/CB Promo Video.mp4',
            ]
        );
    }
}

