<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeasonsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => '2025/26', 'start_date' => '2025-08-15', 'end_date' => '2026-06-30'],
            ['code' => '2024/25', 'start_date' => '2024-08-15', 'end_date' => '2025-06-30'],
            ['code' => '2023/24', 'start_date' => '2023-08-15', 'end_date' => '2024-06-30'],
            ['code' => '2022/23', 'start_date' => '2022-08-15', 'end_date' => '2023-06-30'],
            ['code' => '2021/22', 'start_date' => '2021-08-15', 'end_date' => '2022-06-30'],
        ];

        foreach ($rows as $row) {
            DB::table('seasons')->updateOrInsert(['code' => $row['code']], $row);
        }
    }
}
