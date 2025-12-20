<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\League;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BackfillLeagueCompetitionSeeder extends Seeder
{
    public function run(): void
    {
        League::query()
            ->where('type', 'official')
            ->whereNull('competition_id')
            ->with(['category:id,level,gender', 'region:id,code'])
            ->chunkById(500, function ($chunk) {
                foreach ($chunk as $league) {
                    $name = $league->name;
                    $baseName = $name;
                    $group = null;

                    if (preg_match('/^(.+?)\s+–\s+(.+)$/u', $name, $m)) {
                        $baseName = trim($m[1]);
                        $group    = trim($m[2]);
                    }

                    $level  = $league->category?->level  ?? 'amateur';
                    $gender = $league->category?->gender ?? 'mixed';
                    $regionCode = $league->region?->code;

                    $baseSlug = Str::slug($baseName);
                    $code = $baseSlug;
                    if ($regionCode) $code .= '-'.Str::lower($regionCode);

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

                    $league->competition_id = $competition->id;
                    if (!$league->group_name && $group) $league->group_name = $group;
                    $league->save();
                }
            });
    }
}
