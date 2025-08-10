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
        DB::table('rooms')->insert([
            [
                'property_id' => 1,
                'room_type' => 'Suite',
                'price_per_night' => 250.00,
                'description' => 'Spacious suite with ocean view.',
                'photos' => json_encode(['public/images/suite1.jfif', 'public/images/suite2.jfif'])
            ],
            [
                'property_id' => 2,
                'room_type' => 'Studio',
                'price_per_night' => 120.00,
                'description' => 'Cozy studio apartment.',
                'photos' => json_encode(['public/images/studio1.jfif'])
            ],
        ]);
    }
}
