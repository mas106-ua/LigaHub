<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeasonsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('seasons')->insert([
            [
                'code' => '2025/26',
                'start_date' => '2025-09-01',
                'end_date' => '2026-06-30',
            ],
        ]);
    }
}
