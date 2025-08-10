<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name'         => 'Reem Host',
                'email'        => 'reem.host@gmail.com',
                'password'     => Hash::make('123456'),
                'role'         => 'host',
                'profile_info' => 'Experienced host specializing in beachfront villas.',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'name'         => 'Ahmad Host',
                'email'        => 'ahmad.host@gmail.com',
                'password'     => Hash::make('123456'),
                'role'         => 'host',
                'profile_info' => 'Host with multiple properties in the city center.',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'name'         => 'Tia Admin',
                'email'        => 'tia.admin@gmail.com',
                'password'     => Hash::make('123456'),
                'role'         => 'admin',
                'profile_info' => 'Platform administrator.',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'name'         => 'Nour Guest',
                'email'        => 'nour.guest@gmail.com',
                'password'     => Hash::make('123456'),
                'role'         => 'guest',
                'profile_info' => 'Travel enthusiast and frequent guest.',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            
            [
                'name'         => 'Silvie Guest',
                'email'        => 'silvie.guest@gmail.com',
                'password'     => Hash::make('123456'),
                'role'         => 'guest',
                'profile_info' => 'Guest looking for family-friendly accommodations.',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }
}
