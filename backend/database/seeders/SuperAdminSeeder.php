<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // para no duplicar si lo ejecutas varias veces
        $user = User::where('email', 'superadmin@tfg.local')->first();

        if (! $user) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'superadmin@tfg.local',
                'password' => Hash::make('password123'),
                'role' => 'superadmin',
            ]);
        }
    }
}
