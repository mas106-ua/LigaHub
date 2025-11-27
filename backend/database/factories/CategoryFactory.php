<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name'   => $this->faker->randomElement(['Senior', 'Juvenil', 'Cadete']),
            'level'  => $this->faker->randomElement(['pro','semi','amateur']),
            'gender' => $this->faker->randomElement(['mixed','male','female']),
        ];
    }
}
