<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaguesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $regionIds = DB::table('regions')->pluck('id', 'code');
        $categoryId = DB::table('categories')->where('name', 'Senior')->value('id');
        $seasonId = DB::table('seasons')->where('code', '2025/26')->value('id');

        $leagues = [];

        foreach ($regionIds as $code => $id) {
            $leagues[] = [
                'name' => "Preferente " . $code,
                'type' => 'official',
                'region_id' => $id,
                'category_id' => $categoryId,
                'season_id' => $seasonId,
                'visibility' => 'public',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $leagues[] = [
                'name' => "Primera Autonómica " . $code,
                'type' => 'official',
                'region_id' => $id,
                'category_id' => $categoryId,
                'season_id' => $seasonId,
                'visibility' => 'public',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('leagues')->insert($leagues);
    }
}
