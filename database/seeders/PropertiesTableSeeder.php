<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data first
        DB::table('property_amenity')->delete();
        DB::table('properties')->delete();
        
        $properties = [
            [
                'property_id' => 1,
                'host_id' => 1,
                'title' => 'Ocean View Villa',
                'description' => 'A beautiful villa with ocean views, perfect for a relaxing getaway. Features modern amenities and stunning panoramic views of the Mediterranean Sea.',
                'address' => '123 Beach Road',
                'city' => 'Rmeileh',
                'country' => 'Lebanon',
                'latitude' => 33.61073,
                'longitude' => 35.40232,
                'type' => 'villa',
                'price_per_night' => 250.00,
                'max_guests' => 8,
                'bedrooms' => 4,
                'bathrooms' => 3.0,
                'images' => json_encode(['public/images/villa1.jfif', 'public/images/villa2.jfif']),
                'status' => 'active',
                'featured' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'property_id' => 2,
                'host_id' => 2,
                'title' => 'Central City Apartment',
                'description' => 'Modern apartment in the heart of the city with easy access to shopping, restaurants, and public transportation. Perfect for business travelers.',
                'address' => '456 Main St',
                'city' => 'Beirut',
                'country' => 'Lebanon',
                'latitude' => 33.89548,
                'longitude' => 35.48202,
                'type' => 'apartment',
                'price_per_night' => 120.00,
                'max_guests' => 4,
                'bedrooms' => 2,
                'bathrooms' => 2.0,
                'images' => json_encode(['public/images/apt1.jfif', 'public/images/apt2.jfif']),
                'status' => 'active',
                'featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'property_id' => 3,
                'host_id' => 1,
                'title' => 'Cozy Studio Downtown',
                'description' => 'A compact but comfortable studio apartment perfect for solo travelers or couples. Located in the vibrant downtown area.',
                'address' => '789 Downtown Ave',
                'city' => 'Beirut',
                'country' => 'Lebanon',
                'latitude' => 33.89322,
                'longitude' => 35.50180,
                'type' => 'studio',
                'price_per_night' => 85.00,
                'max_guests' => 2,
                'bedrooms' => 0,
                'bathrooms' => 1.0,
                'images' => json_encode(['public/images/studio1.jpg']),
                'status' => 'active',
                'featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'property_id' => 4,
                'host_id' => 2,
                'title' => 'Luxury Penthouse',
                'description' => 'Exclusive penthouse with breathtaking city views and premium amenities. Perfect for special occasions and luxury stays.',
                'address' => '999 Skyline Tower',
                'city' => 'Beirut',
                'country' => 'Lebanon',
                'latitude' => 33.88743,
                'longitude' => 35.51285,
                'type' => 'penthouse',
                'price_per_night' => 450.00,
                'max_guests' => 6,
                'bedrooms' => 3,
                'bathrooms' => 2.5,
                'images' => json_encode(['public/images/penthouse1.jpg', 'public/images/penthouse2.jpg']),
                'status' => 'maintenance',
                'featured' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('properties')->insert($properties);

        $propertyAmenities = [
            ['property_id' => 1, 'amenity_id' => 22], 
            ['property_id' => 1, 'amenity_id' => 24], 
            ['property_id' => 1, 'amenity_id' => 34], 
            ['property_id' => 1, 'amenity_id' => 35], 

            ['property_id' => 2, 'amenity_id' => 23],
            ['property_id' => 2, 'amenity_id' => 27], 
            ['property_id' => 2, 'amenity_id' => 34], 
            ['property_id' => 2, 'amenity_id' => 35],

            ['property_id' => 3, 'amenity_id' => 34], 
            ['property_id' => 3, 'amenity_id' => 35],

            ['property_id' => 4, 'amenity_id' => 22], 
            ['property_id' => 4, 'amenity_id' => 23], 
            ['property_id' => 4, 'amenity_id' => 24],
            ['property_id' => 4, 'amenity_id' => 26], 
            ['property_id' => 4, 'amenity_id' => 27], 
            ['property_id' => 4, 'amenity_id' => 34], 
            ['property_id' => 4, 'amenity_id' => 35], 
        ];

        DB::table('property_amenity')->insert($propertyAmenities);
    }
}