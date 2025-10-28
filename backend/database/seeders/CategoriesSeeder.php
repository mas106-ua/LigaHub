<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insert([
            ['name' => 'Alevín',   'level' => 'amateur', 'gender' => 'mixed'],
            ['name' => 'Infantil', 'level' => 'amateur', 'gender' => 'mixed'],
            ['name' => 'Cadete',   'level' => 'amateur', 'gender' => 'mixed'],
            ['name' => 'Juvenil',  'level' => 'amateur', 'gender' => 'mixed'],
            ['name' => 'Senior',   'level' => 'semi',    'gender' => 'male'],
            ['name' => 'Femenino Senior', 'level' => 'semi', 'gender' => 'female'],
        ]);
    }
}
