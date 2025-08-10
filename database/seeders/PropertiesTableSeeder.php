<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PropertiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('properties')->insert([
            [
                'host_id' => 1,
                'title' => 'Ocean View Villa',
                'description' => 'A beautiful villa with ocean views.',
                'address' => '123 Beach Road',
                'city' => 'Rmeileh',
                'country' => 'Lebanon',
                'latitude' => 33.61073,
                'longitude' => 35.40232,
                'photos' => json_encode(['public/images/villa1.jfif', 'public/images/villa2.jfif'])
            ],
            [
                'host_id' => 2,
                'title' => 'Central City Apartment',
                'description' => 'Modern apartment in the heart of the city.',
                'address' => '456 Main St',
                'city' => 'Beirut',
                'country' => 'Lebanon',
                'latitude' => 33.89548,
                'longitude' => 35.48202,
                'photos' => json_encode(['public/images/apt1.jfif', 'public/images/apt2.jfif'])
            ],
        ]);
    }
}
