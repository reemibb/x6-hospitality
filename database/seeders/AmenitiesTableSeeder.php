<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AmenitiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('amenities')->insert([
            ['name' => 'WiFi', 'description' => 'Wireless Internet'],
            ['name' => 'Swimming Pool', 'description' => 'Outdoor pool'],
            ['name' => 'Parking', 'description' => 'Free parking space'],
            ['name' => 'Air Conditioning', 'description' => 'AC in rooms'],
            ['name' => 'Breakfast', 'description' => 'Free breakfast included'],
            ['name' => 'Pet Friendly', 'description' => 'Pets are allowed'],
        ]);
    }
}
