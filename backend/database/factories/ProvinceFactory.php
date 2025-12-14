<?php

namespace Database\Factories;

use App\Models\Province;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProvinceFactory extends Factory
{
    protected $model = Province::class;

    public function definition(): array
    {
        $code = strtoupper($this->faker->unique()->lexify('??'));

        return [
            'region_id' => Region::factory(),
            'name'      => $this->faker->unique()->city(),
            'code'      => $code,
        ];
    }
}
