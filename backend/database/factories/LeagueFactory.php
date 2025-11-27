<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\League;
use App\Models\Region;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeagueFactory extends Factory
{
    protected $model = League::class;

    public function definition(): array
    {
        return [
            'name'        => 'Liga '.$this->faker->unique()->word(),
            'type'        => 'official',
            'region_id'   => Region::factory(),
            'category_id' => Category::factory(),
            'season_id'   => Season::factory(),
            'visibility'  => 'public',
            'access_uuid' => null,
            'owner_user_id' => null,
            'is_active'   => true,
        ];
    }
}
