<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Competition;
use App\Models\League;
use App\Models\Region;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LeagueFactory extends Factory
{
    protected $model = League::class;

    public function definition(): array
    {
        return [
            'name'          => 'Liga '.$this->faker->unique()->word(),
            'type'          => 'official',
            'visibility'    => 'public',
            'access_uuid'   => null,
            'owner_user_id' => null,
            'is_active'     => true,

            // legacy (compat)
            'region_id'     => Region::factory(),
            'province_id'   => null,
            'category_id'   => Category::factory(),
            'season_id'     => Season::factory(),

            // nuevo modelo
            'competition_id' => null,
            'group_name'     => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (League $league) {
            // privadas pueden ir sin competition_id
            if ($league->type !== 'official') return;
            if ($league->competition_id) return;

            $cat = $league->category;
            $level  = $cat->level  ?? 'amateur';
            $gender = $cat->gender ?? 'mixed';

            // baseName sin sufijo si viene "X – Y"
            $baseName = preg_replace('/\s+–\s+(.+)$/u', '', $league->name);
            $baseName = $baseName ?: $league->name;

            $regionCode = $league->region?->code;
            $baseSlug   = Str::slug($baseName);

            $code = $baseSlug;
            if ($regionCode) $code .= '-'.Str::lower($regionCode);

            // code <= 50
            if (strlen($code) > 50) {
                $hash = substr(md5($baseName.'|'.$league->category_id.'|'.$level.'|'.$gender.'|'.($league->region_id ?? '')), 0, 8);
                $suffix = $regionCode ? '-'.Str::lower($regionCode) : '';
                $prefixLen = 50 - strlen($suffix) - 1 - strlen($hash);
                $prefixLen = max(1, $prefixLen);
                $prefix = rtrim(substr($baseSlug, 0, $prefixLen), '-');
                $code = substr($prefix.'-'.$hash.$suffix, 0, 50);
            }

            $competition = Competition::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name'        => $baseName,
                    'category_id' => $league->category_id,
                    'level'       => $level,
                    'gender'      => $gender,
                    'region_id'   => $league->region_id,
                    'province_id' => $league->province_id,
                    'type'        => 'official',
                    'is_active'   => true,
                ]
            );

            if (!$league->group_name && preg_match('/^(.+?)\s+–\s+(.+)$/u', $league->name, $m)) {
                $league->group_name = trim($m[2]);
            }

            $league->competition_id = $competition->id;
            $league->save();
        });
    }
}
