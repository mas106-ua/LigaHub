<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\League;
use App\Models\Region;
use App\Models\Competition;
use App\Models\LeagueMembership;
use App\Models\CompetitionAdminScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Usa la misma contraseña para todos estos admins de ejemplo
        $password = Hash::make('admin1234');

        /*
         * 1) Admins profesionales por COMPETICIÓN (no por league suelta)
         *    - Se crean como role=admin (para poder entrar al panel)
         *    - Y se les da membership de admin en TODAS las leagues (todas las temporadas)
         *      que cuelgan de esa competition_id.
         *
         * Ventaja:
         * - Si siembras 2021/22..2025/26, el admin puede gestionar todas las ediciones.
         */
        $competitionAdmins = [
            [
                'competition_code' => 'laliga-ea-sports',
                'fallback_name'    => 'LaLiga EA SPORTS',
                'user' => [
                    'name'  => 'Admin LaLiga EA Sports',
                    'email' => 'admin.laliga@tfg.local',
                ],
            ],
            [
                'competition_code' => 'liga-f',
                'fallback_name'    => 'Liga F',
                'user' => [
                    'name'  => 'Admin Liga F',
                    'email' => 'admin.ligaf@tfg.local',
                ],
            ],
            [
                'competition_code' => 'laliga-hypermotion',
                'fallback_name'    => 'LaLiga Hypermotion',
                'user' => [
                    'name'  => 'Admin LaLiga Hypermotion',
                    'email' => 'admin.hypermotion@tfg.local',
                ],
            ],
        ];

        foreach ($competitionAdmins as $cfg) {
            $competition = Competition::query()
                ->where('code', $cfg['competition_code'])
                ->orWhere('name', $cfg['fallback_name'])
                ->first();

            if (!$competition) {
                $this->command?->warn("Competition no encontrada: {$cfg['competition_code']} / {$cfg['fallback_name']}");
                continue;
            }

            $user = User::updateOrCreate(
                ['email' => $cfg['user']['email']],
                [
                    'name'     => $cfg['user']['name'],
                    'password' => $password,
                    'role'     => 'admin',
                ]
            );

            // Todas las leagues (todas las temporadas/grupos) de esa competición
            $leagues = League::query()
                ->where('competition_id', $competition->id)
                ->where('type', 'official')
                ->get(['id']);

            if ($leagues->isEmpty()) {
                $this->command?->warn("No hay leagues oficiales para competition '{$competition->name}' (id={$competition->id}).");
                continue;
            }

            foreach ($leagues as $league) {
                LeagueMembership::updateOrCreate(
                    [
                        'league_id' => $league->id,
                        'user_id'   => $user->id,
                    ],
                    [
                        'role_in_league' => 'admin',
                        'joined_at'      => now(),
                    ]
                );
            }

            $this->command?->info("OK: {$user->email} => admin en {$leagues->count()} leagues de '{$competition->name}'.");
        }

        /*
         * 2) Admin de fútbol semiprofesional (scope level = 'semi', todas las CCAA)
         */
        $semiGlobal = User::updateOrCreate(
            ['email' => 'admin.semi.global@tfg.local'],
            [
                'name'     => 'Admin Semi Global',
                'password' => $password,
                'role'     => 'admin',
            ]
        );

        CompetitionAdminScope::updateOrCreate(
            [
                'user_id'   => $semiGlobal->id,
                'level'     => 'semi',
                'region_id' => null,
            ],
            []
        );

        /*
         * 3) Un admin amateur por cada CCAA (scope level = 'amateur' + region_id)
         */
        Region::query()->get()->each(function (Region $region) use ($password) {
            $email = sprintf('admin.amateur.%s@tfg.local', strtolower($region->code));
            $name  = sprintf('Admin Amateur %s', $region->name);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name'     => $name,
                    'password' => $password,
                    'role'     => 'admin',
                ]
            );

            CompetitionAdminScope::updateOrCreate(
                [
                    'user_id'   => $user->id,
                    'level'     => 'amateur',
                    'region_id' => $region->id,
                ],
                []
            );
        });
    }
}
