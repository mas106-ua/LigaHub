<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeasonsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => '2024/25', 'start_date' => '2024-08-15', 'end_date' => '2025-06-30'],
            ['code' => '2025/26', 'start_date' => '2025-08-15', 'end_date' => '2026-06-30'],
        ];

        foreach ($rows as $row) {
            DB::table('seasons')->updateOrInsert(['code' => $row['code']], $row);
        }
    }
}
