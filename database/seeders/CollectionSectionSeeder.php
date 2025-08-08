<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CollectionSection;

class CollectionSectionSeeder extends Seeder
{
    public function run(): void
    {
        CollectionSection::updateOrCreate(
            ['section_key' => 'our_collections'],
            [
                'title' => 'Our Collections',
                'description' => 'All of our luxury mansions are operated by Group Retreats. This means that when you book a property with Group Retreats, you can be sure that it has been carefully selected for its grandeur, character, facilities and location, and it has been thoughtfully furnished throughout so that you can enjoy the perfect space to use as your own.',
            ]
        );

        CollectionSection::updateOrCreate(
            ['section_key' => 'everything_you_need'],
            [
                'title' => 'Everything You Need',
                'description' => 'All of our luxury mansions are operated by Group Retreats. This means that when you book a property with Group Retreats, you can be sure that it has been carefully selected for its grandeur, character, facilities and location, and it has been thoughtfully furnished throughout so that you can enjoy the perfect space to use as your own.',
            ]
        );
    }
}
