<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
        RegionsSeeder::class,
        CategoriesSeeder::class,
        SeasonsSeeder::class,
        ProvincesSeeder::class,
        OfficialCompetitionsSeeder::class,
        BackfillLeagueProvinceSeeder::class,
        SuperAdminSeeder::class,
        AdminUsersSeeder::class,
        TeamsAndFixturesSeeder::class,
        TestLineupsPlayersSeeder::class,
    ]);
    }
}
