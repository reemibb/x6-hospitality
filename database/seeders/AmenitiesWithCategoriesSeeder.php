<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AmenitiesWithCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            ['name' => 'Swimming Pool', 'category' => 'property', 'description' => 'Outdoor swimming pool'],
            ['name' => 'Gym/Fitness Center', 'category' => 'property', 'description' => 'Fully equipped fitness center'],
            ['name' => 'Parking', 'category' => 'property', 'description' => 'Free parking available'],
            ['name' => 'Restaurant', 'category' => 'property', 'description' => 'On-site restaurant'],
            ['name' => 'Spa', 'category' => 'property', 'description' => 'Full-service spa'],
            ['name' => '24/7 Reception', 'category' => 'property', 'description' => '24-hour front desk service'],
            
            ['name' => 'Mini Bar', 'category' => 'room', 'description' => 'In-room mini bar'],
            ['name' => 'Balcony', 'category' => 'room', 'description' => 'Private balcony'],
            ['name' => 'Ocean View', 'category' => 'room', 'description' => 'Room with ocean view'],
            ['name' => 'Kitchenette', 'category' => 'room', 'description' => 'Small kitchen area'],
            ['name' => 'Fireplace', 'category' => 'room', 'description' => 'In-room fireplace'],
            ['name' => 'Jacuzzi', 'category' => 'room', 'description' => 'Private jacuzzi'],
            
            ['name' => 'WiFi', 'category' => 'both', 'description' => 'High-speed internet access'],
            ['name' => 'Air Conditioning', 'category' => 'both', 'description' => 'Climate control'],
            ['name' => 'TV', 'category' => 'both', 'description' => 'Flat-screen television'],
            ['name' => 'Safe', 'category' => 'both', 'description' => 'Security safe'],
        ];

        DB::table('amenities')->insert($amenities);
    }
}
