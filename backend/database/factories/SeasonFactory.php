<?php

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('first day of August last year', 'first day of September this year');
        $end   = (clone $start)->modify('+10 months');
        $code  = $start->format('Y').'/'.substr($end->format('Y'), -2);

        return [
            'code'       => $code,
            'start_date' => $start->format('Y-m-d'),
            'end_date'   => $end->format('Y-m-d'),
        ];
    }
}
