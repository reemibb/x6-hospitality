<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data first
        DB::table('room_amenity')->delete();
        DB::table('rooms')->delete();
        
        $rooms = [
            [
                'room_id' => 1,
                'property_id' => 1,
                'room_type' => 'Suite',
                'price_per_night' => 300.00,
                'description' => 'Spacious suite with ocean view and private balcony.',
                'photos' => json_encode(['public/images/suite1.jfif', 'public/images/suite2.jfif']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'room_id' => 2,
                'property_id' => 1,
                'room_type' => 'Double Room',
                'price_per_night' => 200.00,
                'description' => 'Comfortable double room with sea view.',
                'photos' => json_encode(['public/images/double1.jpg']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'room_id' => 3,
                'property_id' => 2,
                'room_type' => 'Studio',
                'price_per_night' => 120.00,
                'description' => 'Cozy studio apartment with kitchenette.',
                'photos' => json_encode(['public/images/studio1.jfif']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'room_id' => 4,
                'property_id' => 2,
                'room_type' => 'Single Room',
                'price_per_night' => 90.00,
                'description' => 'Compact single room perfect for solo travelers.',
                'photos' => json_encode(['public/images/single1.jpg']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'room_id' => 5,
                'property_id' => 4,
                'room_type' => 'Presidential Suite',
                'price_per_night' => 600.00,
                'description' => 'Luxurious presidential suite with panoramic city views.',
                'photos' => json_encode(['public/images/presidential1.jpg', 'public/images/presidential2.jpg']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'room_id' => 6,
                'property_id' => 4,
                'room_type' => 'Deluxe Room',
                'price_per_night' => 350.00,
                'description' => 'Deluxe room with premium amenities and city view.',
                'photos' => json_encode(['public/images/deluxe1.jpg']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('rooms')->insert($rooms);

        $roomAmenities = [
            // Suite (room_id: 1) - room amenities
            ['room_id' => 1, 'amenity_id' => 29], // Balcony
            ['room_id' => 1, 'amenity_id' => 30], // Ocean View
            ['room_id' => 1, 'amenity_id' => 28], // Mini Bar
            ['room_id' => 1, 'amenity_id' => 36], // TV
            ['room_id' => 1, 'amenity_id' => 37], // Safe

            // Double Room (room_id: 2) - room amenities
            ['room_id' => 2, 'amenity_id' => 29], // Balcony
            ['room_id' => 2, 'amenity_id' => 30], // Ocean View
            ['room_id' => 2, 'amenity_id' => 36], // TV
            ['room_id' => 2, 'amenity_id' => 37], // Safe

            // Studio (room_id: 3) - room amenities
            ['room_id' => 3, 'amenity_id' => 31], // Kitchenette
            ['room_id' => 3, 'amenity_id' => 36], // TV
            ['room_id' => 3, 'amenity_id' => 37], // Safe

            // Single Room (room_id: 4) - room amenities
            ['room_id' => 4, 'amenity_id' => 36], // TV
            ['room_id' => 4, 'amenity_id' => 37], // Safe

            // Presidential Suite (room_id: 5) - room amenities
            ['room_id' => 5, 'amenity_id' => 28], // Mini Bar
            ['room_id' => 5, 'amenity_id' => 29], // Balcony
            ['room_id' => 5, 'amenity_id' => 32], // Fireplace
            ['room_id' => 5, 'amenity_id' => 33], // Jacuzzi
            ['room_id' => 5, 'amenity_id' => 36], // TV
            ['room_id' => 5, 'amenity_id' => 37], // Safe

            // Deluxe Room (room_id: 6) - room amenities
            ['room_id' => 6, 'amenity_id' => 28], // Mini Bar
            ['room_id' => 6, 'amenity_id' => 29], // Balcony
            ['room_id' => 6, 'amenity_id' => 36], // TV
            ['room_id' => 6, 'amenity_id' => 37], // Safe
        ];

        DB::table('room_amenity')->insert($roomAmenities);
    }
}