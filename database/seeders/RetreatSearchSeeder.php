<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RetreatSearch;

class RetreatSearchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RetreatSearch::create([
        // 'title' => 'Peaceful Cabin',
        'city' => 'Bath',
        'check_in' => '2025-08-01',
        'check_out' => '2025-08-10',
        'guests' => 4,
    ]);

    RetreatSearch::create([
        // 'title' => 'Ocean View Retreat',
        'city' => 'Brighton',
        'check_in' => '2025-08-05',
        'check_out' => '2025-08-20',
        'guests' => 6,
    ]);
    }
}
