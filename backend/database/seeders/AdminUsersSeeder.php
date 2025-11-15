<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\League;
use App\Models\Region;
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
         * 1) Admins profesionales por liga concreta
         *    (se crean como admin global + admin de esa liga vía league_memberships)
         */
        $professionalAdmins = [
            'LaLiga EA SPORTS' => [
                'name'  => 'Admin LaLiga EA Sports',
                'email' => 'admin.laliga@tfg.local',
            ],
            'Liga F' => [
                'name'  => 'Admin Liga F',
                'email' => 'admin.ligaf@tfg.local',
            ],
            'LaLiga Hypermotion' => [
                'name'  => 'Admin LaLiga Hypermotion',
                'email' => 'admin.hypermotion@tfg.local',
            ],
        ];

        foreach ($professionalAdmins as $leagueName => $userData) {
            $leagues = League::where('name', $leagueName)->get();

            if ($leagues->isEmpty()) {
                $this->command?->warn("Liga '{$leagueName}' no encontrada, se omite ese admin.");
            } else {
                $user = User::updateOrCreate(
                    ['email' => $userData['email']],
                    [
                        'name'     => $userData['name'],
                        'password' => $password,
                        'role'     => 'admin',
                    ]
                );

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
            }
        }

        /*
         * 2) Admin de fútbol semiprofesional (level = 'semi')
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
                'region_id' => null,   // todas las CCAA
            ],
            []
        );


        /*
         * 3) Un admin amateur por cada CCAA (level = 'amateur')
         */

        Region::all()->each(function (Region $region) use ($password) {
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
