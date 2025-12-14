<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Competition;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompetitionFactory extends Factory
{
    protected $model = Competition::class;

    public function definition(): array
    {
        $name = 'Competition '.$this->faker->unique()->words(3, true);
        $baseCode = Str::slug($name);

        // competitions.code <= 50
        $code = substr($baseCode.'-'.Str::lower($this->faker->unique()->lexify('??????')), 0, 50);

        $level  = $this->faker->randomElement(['pro','semi','amateur']);
        $gender = $this->faker->randomElement(['mixed','male','female']);

        return [
            'name'        => $name,
            'code'        => $code,
            'category_id' => Category::factory(),
            'level'       => $level,
            'gender'      => $gender,
            'region_id'   => Region::factory(),
            'province_id' => null,
            'type'        => 'official',
            'is_active'   => true,
        ];
    }

    /**
     * Estado: competición provincial (region_id coherente con province)
     */
    public function provincial(): static
    {
        return $this->state(function () {
            $province = Province::factory()->create();

            return [
                'province_id' => $province->id,
                'region_id'   => $province->region_id,
            ];
        });
    }
}
