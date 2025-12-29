<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CmsMasterSeeder extends Seeder
{
    public function run(): void
    {
        /* ================= ABOUT SECTIONS ================= */
        DB::table('about_sections')->insertOrIgnore([
            [
                'id' => 1,
                'title' => 'Frequently asked questions',
                'subtitle' => 'A shared passion for beautiful property and the desire to provide unique accommodation for large groups, led to the creation of Group Retreats in 2017.',
                'video_url' => 'https://www.youtube.com/embed/N1LQb1r2Dxw?si=F4m_f3iL5yIK655',
                'active' => 1,
                'created_at' => '2025-08-08 07:04:12',
                'updated_at' => '2025-08-22 12:27:52',
            ],
        ]);

        /* ================= ABOUT US ================= */
        DB::table('about_us')->insertOrIgnore([
            [
                'id' => 1,
                'title' => 'About Us',
                'subtitle' => 'A shared passion for beautiful property and the desire to provide unique accommodation for large groups, led to the creation of Group Retreats in 2017.',
                'image' => 'https://api.retreat.isoft-digital.net/storage/images/oEjOPapDcQcWGK91B04eP0fApV2Epl6VbxqDQQvw.jpg',
                'description' => 'All you need to do is let us know the property you like and the dates you would like to stay...',
                'button_text' => 'Enquire Now',
                'video_url' => 'https://api.retreat.isoft-digital.net/storage/videos/DFcuszTrsCzk7DJ1AMwSNnjh580N8wpmnmkw0Z77.mp4',
                'active' => 0,
                'created_at' => '2025-07-30 13:02:41',
                'updated_at' => '2025-11-04 18:53:40',
            ],
        ]);

        /* ================= BOOKING GUIDES ================= */
        DB::table('booking_guides')->insertOrIgnore([
            [
                'id' => 12,
                'title' => 'At Group Retreats we can help you plan an unforgettable trip.',
                'subtitle' => 'Recognising that large groups often struggle to find appropriate accommodation...',
                'video_url' => 'https://api.retreat.isoft-digital.net/storage/videos/V522Wb4KIt4XFdF6BklEthk6HXw2CFvCwCOzmy2b.mp4',
                'image' => 'https://api.retreat.isoft-digital.net/storage/images/QcUTFXPeXpoRVsYAxDv7aBLMGyGQeukUEdSCtk6f.jpg',
                'enquire_now_button' => 'Enquire Now',
                'description' => 'Recognising that large groups often struggle...',
                'is_video' => 0,
                'created_at' => '2025-08-05 08:58:07',
                'updated_at' => '2025-11-06 20:32:28',
            ],
        ]);

        /* ================= BOOKING STEPS ================= */
        DB::table('booking_steps')->insertOrIgnore([
            [
                'id' => 1,
                'title' => 'How to make a booking.',
                'subtitle' => 'A shared passion for beautiful property...',
                'image_url' => 'https://api.retreat.isoft-digital.net/storage/images/da4uB0Ya9Osh6TT8JMhwvKfUKgO9bAEhljQk0HET.jpg',
                'video_url' => 'https://api.retreat.isoft-digital.net/storage/videos/DFcuszTrsCzk7DJ1AMwSNnjh580N8wpmnmkw0Z77.mp4',
                'description' => 'All you need to do is let us know the property...',
                'note' => 'Enquire now',
                'step_number' => 1,
                'is_video' => 0,
                'active' => 0,
                'created_at' => '2025-08-14 04:17:08',
                'updated_at' => '2025-11-06 20:31:32',
            ],
        ]);

        /* ================= COLLECTION SECTIONS ================= */
        DB::table('collection_sections')->insertOrIgnore([
            [
                'id' => 1,
                'section_key' => 'our_collections',
                'title' => 'Our Collections',
                'description' => 'All of our luxury mansions are operated by Group Retreats...',
                'created_at' => '2025-07-29 11:07:04',
                'updated_at' => '2025-11-04 18:45:20',
            ],
            [
                'id' => 4,
                'section_key' => 'everything_you_need',
                'title' => 'Everything You Need',
                'description' => 'All of our luxury mansions are operated by Group Retreats...',
                'created_at' => '2025-07-29 11:47:00',
                'updated_at' => '2025-11-04 18:45:12',
            ],
        ]);

        /* ================= FAQ ================= */
        DB::table('faq_sections')->insertOrIgnore([
            [
                'id' => 1,
                'question' => 'How look I book a hotel through your website?',
                'answer' => 'We offer a full refund if you cancel within 30 days of booking.',
                'active' => 0,
                'created_at' => '2025-08-08 07:20:07',
                'updated_at' => '2025-08-08 07:22:48',
            ],
        ]);

        /* ================= FEATURE BOX ================= */
        DB::table('feature_boxes')->insertOrIgnore([
            [
                'id' => 2,
                'title' => 'The best offer',
                'subtitle' => 'We know it’s hard to round everyone up...',
                'icon' => 'https://api.retreat.isoft-digital.net/images/Bookmark1.png',
                'color' => '#faf6f1',
                'created_at' => '2025-07-29 12:28:42',
                'updated_at' => '2025-10-24 01:17:32',
            ],
        ]);

        /* ================= HERO SECTION ================= */
        DB::table('hero_sections')->insertOrIgnore([
            [
                'id' => 1,
                'title' => 'Hotel amenities without hotel formality',
                'subtitle' => 'Born through a passion for hospitality and heritage...',
                'button_text' => 'Start your Journey',
                'background_video_url' => 'https://api.retreat.isoft-digital.net/storage/videos/V522Wb4KIt4XFdF6BklEthk6HXw2CFvCwCOzmy2b.mp4',
                'created_at' => '2025-12-22 23:06:04',
                'updated_at' => '2025-12-22 23:06:04',
            ],
        ]);

        /* ================= PAGE SECTIONS ================= */
        DB::table('page_sections')->insertOrIgnore([
            [
                'id' => 10,
                'section_name' => 'how-to-make-booking',
                'data' => json_encode([
                    'title' => 'How to make a booking.',
                    'subtitle' => 'A shared passion for beautiful property...',
                    'content' => 'All you need to do is let us know...',
                    'image' => '/images/about.jpg',
                    'button_text' => 'Enquire now',
                    'button_link' => '/book',
                ]),
                'created_at' => '2025-08-05 10:43:36',
                'updated_at' => '2025-08-05 10:44:22',
            ],
        ]);

        DB::table('homepage_contents')->insertOrIgnore([
            [
                'id' => 1,
                'title' => 'Luxury Group Accommodations',
                'subtitle' => 'One-of-a-kind group stays, handpicked for big gatherings, special moments, and time well spent.',
                'background_image' => '[]',
                'background_video' => 'https://api.retreat.isoft-digital.net/storage/videos/DFcuszTrsCzk7DJ1AMwSNnjh580N8wpmnmkw0Z77.mp4',
                'is_video' => 1,
                'meta_title' => 'Luxury Group Retreats UK',
                'meta_description' => 'Book the best large holiday homes...',
                'meta_keywords' => 'group stays, luxury villas, UK retreats',
                'og_title' => 'Luxury Group Stay Experience',
                'og_description' => 'Perfect for celebrations & corporate stays',
                'og_image' => 'https://api.retreat.isoft-digital.net/storage/seo/4WT5rrDBVSb13EI08KTDcsmDD3lcmrFEfDs1FvxB.jpg',
                'canonical_url' => 'https://frontend.retreat.isoft-digital.net/home',
                'schema_markup' => '"{ \"type\": \"WebPage\", \"name\": \"Homepage\" }"',
                'robots_txt' => 1,
                'is_active' => 1,
                'created_at' => '2025-10-24 23:06:20',
                'updated_at' => '2025-12-22 23:44:23',
            ],
        ]);

        DB::table('hospitality_sections')->insertOrIgnore([
            [
                'id' => 1,
                'title' => 'Building the Future of Hospitality',
                'subtitle' => 'At Group Retreats, we\'re on a mission to redefine hospitality by bringing exceptional stays everywhere.',
                'meta_title' => 'Best Villas with Pools',
                'meta_description' => 'Luxury villas with pools',
                'meta_keywords' => 'villas, pool, luxury',
                'description' => 'At Group Retreats, we’re on a mission to redefine hospitality by bringing exceptional stays everywhere.',
                'og_title' => 'Pool Villas OG',
                'og_description' => 'Perfect for vacations',
                'og_images' => json_encode([
                    'https://api.retreat.isoft-digital.net/storage/uploads/og/Ul7jpET9JZBa90FUM9Di9tRRL1D2gQtFUmzzoiT5.jpg',
                    'https://api.retreat.isoft-digital.net/storage/uploads/og/TLGUp8Q7Gy7RV4tz0bVOuvwdMWB7YNHddFf9RrCr.jpg',
                    'https://api.retreat.isoft-digital.net/storage/uploads/og/lKVeytM4a4N0wGnA4fHdLLdMYkidZxjgNqhrsU4c.jpg',
                ]),
                'canonical_url' => 'https://frontend.retreat.isoft-digital.net/home',
                'schema_markup' => '{ "type": "WebPage", "name": "Pool Villas" }',
                'robots_txt' => 1,
                'is_active' => 1,
                'created_at' => '2025-08-16 02:45:55',
                'updated_at' => '2025-11-12 14:39:29',
            ],
        ]);

        DB::table('list_properties')->insertOrIgnore([
            [
                'id' => 1,
                'title' => 'list Your Property with Group Retreats',
                'subtitle' => 'A Carefully Curated Collection of the UK’s Most Remarkable Large Holiday Lets & Retreat Venues',
                'section1_subtitle' => 'Experience the comfort of luxury, the warmth of home, and the charm of the countryside',
                'description' => 'At Group Retreats, we don’t just market beautiful properties — we operate them...',
                'button_text' => 'Get in Touch',
                'button_url' => null,
                'image' => 'https://api.retreat.isoft-digital.net/storage/images/esbvZFKmcHDeYxS3OdomTjUW9KwKqrY2iI2G7Dyl.jpg',
                'hero_image' => 'https://api.retreat.isoft-digital.net/storage/images/ZcFFeOmN7rXsGQf1HFJ4DnZIbYNcjtg3kLHzYv9X.jpg',
                'meta_title' => 'List Your Property - Group Retreats',
                'meta_description' => 'Partner with Group Retreats to list your property in our curated collection...',
                'meta_keywords' => 'Group Retreats, List Property, Holiday Lets, Retreats',
                'og_title' => 'List Your Property with Group Retreats',
                'og_description' => 'Join our curated collection of the UK’s most remarkable large holiday lets...',
                'og_images' => json_encode(['list-your-property.jpg']),
                'canonical_url' => 'https://www.groupretreats.com/list-your-property',
                'schema_markup' => '<script type="application/ld+json">...</script>',
                'robots_txt' => '1',
                'is_active' => '1',
                'created_at' => '2025-10-20 13:18:04',
                'updated_at' => '2025-11-12 23:12:36',
            ],
        ]);

        DB::table('newsletter_subscriptions')->insertOrIgnore([
            [
                'id' => 1,
                'email' => 'huzaifaahmed0605@gmail.com',
                'created_at' => '2025-10-02 07:22:26',
                'updated_at' => '2025-10-02 07:22:26',
            ],
        ]);
        DB::table('owner_say_sections')->insertOrIgnore([
            [
                'id' => 1,
                'title' => 'What Our Owners Say',
                'description' => 'We’d managed our own bookings for years and thought we were doing fine...',
                'secondary_title' => 'Think Your Property Belongs Here?',
                'secondary_description' => 'We work with a small number of owners whose properties align with our values...',
                'owner_name' => 'Owner, 12-bed Manor, Cotswolds',
                'owner_title' => '',
                'testimonial' => 'nice',
                'rating' => 5,
                'active' => 1,
                'image' => 'https://api.retreat.isoft-digital.net/storage/images/nn5eeXsBEqHM4Yu5EKBLiKneU9KjdkUckpkZGyqK.jpg',
                'created_at' => '2025-11-12 23:12:58',
                'updated_at' => '2025-11-12 23:12:58',
            ],
        ]);
        DB::table('reviews')->insertOrIgnore([
            [
                'id' => 10,
                'property_id' => 457774,
                'review' => 'great work',
                'reviewer_name' => 'Charles',
                'location' => 'England',
                'created_at' => '2025-12-19 16:32:07',
                'updated_at' => '2025-12-19 16:32:07',
            ],
        ]);
        DB::table('why_partner_boxes')->insertOrIgnore([
            [
                'id' => 21,
                'section_title' => 'Why Partner with Group Retreats?',
                'box_title' => 'A True Understanding of the Market',
                'box_description' => 'We’ve built our business by living and breathing the group stay sector...',
                'order_number' => 0,
                'active' => 1,
                'created_at' => '2025-11-12 18:11:37',
                'updated_at' => '2025-11-12 18:11:37',
            ],
            [
                'id' => 23,
                'section_title' => 'Why Partner with Group Retreats?',
                'box_title' => 'Support That Goes Beyond Bookings',
                'box_description' => 'From pricing strategy and guest experience to operational planning...',
                'order_number' => 0,
                'active' => 1,
                'created_at' => '2025-11-12 18:11:37',
                'updated_at' => '2025-11-12 18:11:37',
            ],
            [
                'id' => 25,
                'section_title' => 'Why Partner with Group Retreats?',
                'box_title' => 'No Upfront Costs',
                'box_description' => 'Our model is simple: we only earn when you do...',
                'order_number' => 0,
                'active' => 1,
                'created_at' => '2025-11-12 18:11:37',
                'updated_at' => '2025-11-12 18:11:37',
            ],
        ]);
    }
}
