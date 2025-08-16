<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeatureBox;

class FeatureBoxesSeeder extends Seeder
{
    public function run()
    {
        $boxes = [
            [
                'title' => 'Flexible cancellations',
                'subtitle' => 'We know it’s hard to round everyone up, so guests can hold dates for 24 hours at no cost.',
                'icon' => 'location.png',  
                'color' => '#FAF6F1',
            ],
            [
                'title' => 'The best offer',
                'subtitle' => 'We know it’s hard to round everyone up, so guests can hold dates for 24 hours at no cost.',
                'icon' => 'The_best_offer.jpg',
                'color' => '#FAF6F1',
            ],
            [
                'title' => 'Request late checkout',
                'subtitle' => 'We know it’s hard to round everyone up, so guests can hold dates for 24 hours at no cost.',
                'icon' => 'Request_late.jpg',
                'color' => '#FAF6F1',
            ],
            [
                'title' => 'Secure Payments',
                'subtitle' => 'We know it’s hard to round everyone up, so guests can hold dates for 24 hours at no cost.',
                'icon' => 'Reserve_for_free.jpg',
                'color' => '#FAF6F1',
            ],
        ];

        foreach ($boxes as $box) {
            FeatureBox::updateOrCreate(
                ['title' => $box['title']],
                $box
            );
        }
    }
}
