<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AboutUs;

class AboutUsSeeder extends Seeder
{
    public function run(): void
    {
        AboutUs::create([
            'title' => 'About Us',
            'subtitle' => 'A shared passion for beautiful property and the desire to provide unique accommodation for large groups, led to the creation of Group Retreats in 2017.',
            'image' => 'about-us.jpg',
            'description' => 'All you need to do is let us know the property you like and the dates you would like to stay. If you like more than one of our houses or your dates aren’t set in stone, no problem! Just tell us and we will be happy to discuss your requirements. Once we know where you would like to go and for how long, we will give you a price. To book we take a 25% deposit and the rest is due two months before you check in. We will send you out all the details you need for your stay including how to check in, information on the local area and our recommendations for fantastic local suppliers.',
            'button_text' => 'Enquire Now'
        ]);
    }
}
