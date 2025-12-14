<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $cats = [
            // Senior masculino
            ['name' => 'Senior', 'level' => 'pro',     'gender' => 'male'],
            ['name' => 'Senior', 'level' => 'semi',    'gender' => 'male'],
            ['name' => 'Senior', 'level' => 'amateur', 'gender' => 'male'],
            // Senior femenino
            ['name' => 'Senior', 'level' => 'pro',     'gender' => 'female'],
            ['name' => 'Senior', 'level' => 'semi',    'gender' => 'female'],
            ['name' => 'Senior', 'level' => 'amateur', 'gender' => 'female'],
            // Juvenil nacional masculino (División de Honor y Liga Nacional)
            ['name' => 'Juvenil Nacional', 'level' => 'amateur', 'gender' => 'male'],

            // Juvenil territorial masculino (máxima autonómica)
            ['name' => 'Juvenil', 'level' => 'amateur', 'gender' => 'male'],

            // Juvenil femenino (torneos y ligas territoriales sub‑19/sub‑18)
            ['name' => 'Juvenil', 'level' => 'amateur', 'gender' => 'female'],
        ];

        foreach ($cats as $c) {
            DB::table('categories')->updateOrInsert($c, $c);
        }
    }
}
