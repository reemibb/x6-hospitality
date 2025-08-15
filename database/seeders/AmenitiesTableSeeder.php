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
            ['name' => 'WiFi', 'description' => 'High-speed wireless internet connection'],
            ['name' => 'Air Conditioning', 'description' => 'Climate control system for comfort'],
            ['name' => 'Swimming Pool', 'description' => 'Private or shared swimming pool access'],
            ['name' => 'Parking', 'description' => 'Dedicated parking space available'],
            ['name' => 'Kitchen', 'description' => 'Fully equipped kitchen with appliances'],
            ['name' => 'Washing Machine', 'description' => 'In-unit or shared laundry facilities'],
            ['name' => 'TV', 'description' => 'Television with cable or streaming services'],
            ['name' => 'Gym', 'description' => 'Fitness center or gym access'],
            ['name' => 'Pet Friendly', 'description' => 'Pets are welcome'],
            ['name' => 'Balcony', 'description' => 'Private balcony or terrace'],
            ['name' => 'Garden', 'description' => 'Access to garden or outdoor space'],
            ['name' => 'Hot Tub', 'description' => 'Private or shared hot tub/jacuzzi'],
            ['name' => 'Fireplace', 'description' => 'Indoor fireplace for ambiance'],
            ['name' => 'Elevator', 'description' => 'Building has elevator access'],
            ['name' => 'Security System', 'description' => '24/7 security or alarm system'],
        ]);
    }
}
