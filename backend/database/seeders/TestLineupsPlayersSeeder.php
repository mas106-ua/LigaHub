<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Seeder;

class TestLineupsPlayersSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Localiza los equipos (ajusta si en tu BBDD el nombre es distinto)
        $cartagena = Team::where('name', 'FC Cartagena')
            ->orWhere('short_name', 'FC Cartagena')
            ->first();

        $marbella = Team::where('name', 'Marbella FC')
            ->orWhere('short_name', 'Marbella FC')
            ->first();

        if (!$cartagena || !$marbella) {
            $this->command?->warn('⚠️ No se encontraron FC Cartagena o Marbella FC en la tabla teams.');
            return;
        }

        $attachPlayers = function (Team $team, array $players) use ($now) {
            foreach ($players as $data) {
                $player = Player::updateOrCreate(
                    ['full_name' => $data['name']],
                    [
                        'position' => $data['pos'],        // GK / DF / MF / FW
                        'date_of_birth' => null,
                        'doc_number' => null,
                    ]
                );

                // Vincula al equipo en la pivot team_players
                $team->players()->syncWithoutDetaching([
                    $player->id => [
                        'shirt_number' => $data['shirt'],
                        'from_date'    => $now,
                        'to_date'      => null,
                    ],
                ]);
            }
        };

        // Plantilla básica FC Cartagena (ejemplo: 2 porteros, 6 defensas, 4 medios, 3 delanteros)
        $cartagenaPlayers = [
            // Porteros
            ['name' => 'Pablo Cuñat',        'pos' => 'GK', 'shirt' => 1],
            ['name' => 'Toni Fuidias',       'pos' => 'GK', 'shirt' => 13],

            // Defensas
            ['name' => 'Marc Jurado',        'pos' => 'DF', 'shirt' => 2],
            ['name' => 'Jorge Moreno',       'pos' => 'DF', 'shirt' => 4],
            ['name' => 'Nil Jiménez',        'pos' => 'DF', 'shirt' => 3],
            ['name' => 'Ríos Reina',         'pos' => 'DF', 'shirt' => 15],
            ['name' => 'Alcalá',             'pos' => 'DF', 'shirt' => 5],
            ['name' => 'Luca Lohr',          'pos' => 'DF', 'shirt' => 20],

            // Centrocampistas
            ['name' => 'Damián Musto',       'pos' => 'MF', 'shirt' => 6],
            ['name' => 'Andy',               'pos' => 'MF', 'shirt' => 8],
            ['name' => 'Luis Muñoz',         'pos' => 'MF', 'shirt' => 16],
            ['name' => 'Jairo',              'pos' => 'MF', 'shirt' => 11],

            // Delanteros
            ['name' => 'Alfredo Ortuño',     'pos' => 'FW', 'shirt' => 7],
            ['name' => 'Dani Escriche',      'pos' => 'FW', 'shirt' => 9],
        ];

        // Plantilla básica Marbella FC
        $marbellaPlayers = [
            // Porteros
            ['name' => 'Alberto Lejárraga',  'pos' => 'GK', 'shirt' => 1],
            ['name' => 'Eric Puerto',        'pos' => 'GK', 'shirt' => 13],

            // Defensas
            ['name' => 'Yac Diori',          'pos' => 'DF', 'shirt' => 4],
            ['name' => 'Aitor Puñal',        'pos' => 'DF', 'shirt' => 10],
            ['name' => 'Álex Martínez',      'pos' => 'DF', 'shirt' => 3],
            ['name' => 'José Carrasco',      'pos' => 'DF', 'shirt' => 5],
            ['name' => 'Marcos Olguín',      'pos' => 'DF', 'shirt' => 14],

            // Centrocampistas
            ['name' => 'Jonatan Carmona',    'pos' => 'MF', 'shirt' => 8],
            ['name' => 'Pablo Muñoz',        'pos' => 'MF', 'shirt' => 21],
            ['name' => 'Pere Marco',         'pos' => 'MF', 'shirt' => 6],
            ['name' => 'Valentino Fattore',  'pos' => 'MF', 'shirt' => 12],

            // Delanteros
            ['name' => 'Hicham Boussefiane', 'pos' => 'FW', 'shirt' => 11],
            ['name' => 'Sergio Castel',      'pos' => 'FW', 'shirt' => 9],
        ];

        $this->command?->info('➡️ Poblando plantilla FC Cartagena');
        $attachPlayers($cartagena, $cartagenaPlayers);

        $this->command?->info('➡️ Poblando plantilla Marbella FC');
        $attachPlayers($marbella, $marbellaPlayers);

        $this->command?->info('✅ Plantillas de prueba creadas.');
    }
}
