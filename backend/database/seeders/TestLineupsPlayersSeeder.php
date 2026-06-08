<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestLineupsPlayersSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::transaction(function () use ($now) {
            $cartagena = $this->upsertTeam('FC Cartagena', 'Cartagena', 'Cartagena');
            $marbella  = $this->upsertTeam('Marbella FC', 'Marbella', 'Marbella');

            $cartagenaPlayers = $this->upsertSquad($cartagena->id, $this->cartagenaSquad(), $now);
            $marbellaPlayers  = $this->upsertSquad($marbella->id, $this->marbellaSquad(), $now);

            $league = $this->findOrCreateDemoLeague($cartagena->id, $marbella->id, $now);

            DB::table('league_teams')->updateOrInsert(
                ['league_id' => $league->id, 'team_id' => $cartagena->id],
                ['group_name' => $league->group_name ?? 'Grupo Demo']
            );

            DB::table('league_teams')->updateOrInsert(
                ['league_id' => $league->id, 'team_id' => $marbella->id],
                ['group_name' => $league->group_name ?? 'Grupo Demo']
            );

            $match = $this->findOrCreateDemoMatch($league->id, $cartagena->id, $marbella->id, $now);

            $homeIsCartagena = ((int) $match->home_team_id === (int) $cartagena->id);

            $homeGoals = $homeIsCartagena ? 2 : 1;
            $awayGoals = $homeIsCartagena ? 1 : 2;

            DB::table('matches')->where('id', $match->id)->update([
                'status'       => 'played',
                'home_goals'   => $homeGoals,
                'away_goals'   => $awayGoals,
                'scheduled_at' => $match->scheduled_at ?? Carbon::parse('2026-06-14 19:30:00'),
                'notes'        => 'Partido demo preparado para la defensa del TFG: alineaciones, eventos y estadísticas completas.',
                'updated_at'   => $now,
            ]);

            $match = DB::table('matches')->where('id', $match->id)->first();

            $homePlayers = $homeIsCartagena ? $cartagenaPlayers : $marbellaPlayers;
            $awayPlayers = $homeIsCartagena ? $marbellaPlayers : $cartagenaPlayers;

            $this->upsertLineup(
                matchId: $match->id,
                teamId: $match->home_team_id,
                side: 'home',
                formation: $homeIsCartagena ? '4-2-3-1' : '4-4-2',
                coachName: $homeIsCartagena ? 'Entrenador FC Cartagena' : 'Entrenador Marbella FC',
                players: $homePlayers,
                starters: $homeIsCartagena
                    ? ['gk1', 'rb', 'cb1', 'cb2', 'lb', 'dm', 'cm', 'rw', 'am', 'lw', 'st']
                    : ['gk1', 'rb', 'cb1', 'cb2', 'lb', 'rm', 'cm1', 'cm2', 'lm', 'fw1', 'fw2'],
                bench: $homeIsCartagena
                    ? ['gk2', 'df3', 'df4', 'mf2', 'mf3', 'fw2', 'fw3']
                    : ['gk2', 'df3', 'df4', 'mf3', 'mf4', 'fw3', 'fw4'],
                now: $now
            );

            $this->upsertLineup(
                matchId: $match->id,
                teamId: $match->away_team_id,
                side: 'away',
                formation: $homeIsCartagena ? '4-4-2' : '4-2-3-1',
                coachName: $homeIsCartagena ? 'Entrenador Marbella FC' : 'Entrenador FC Cartagena',
                players: $awayPlayers,
                starters: $homeIsCartagena
                    ? ['gk1', 'rb', 'cb1', 'cb2', 'lb', 'rm', 'cm1', 'cm2', 'lm', 'fw1', 'fw2']
                    : ['gk1', 'rb', 'cb1', 'cb2', 'lb', 'dm', 'cm', 'rw', 'am', 'lw', 'st'],
                bench: $homeIsCartagena
                    ? ['gk2', 'df3', 'df4', 'mf3', 'mf4', 'fw3', 'fw4']
                    : ['gk2', 'df3', 'df4', 'mf2', 'mf3', 'fw2', 'fw3'],
                now: $now
            );

            $this->upsertTeamStats($match->id, $cartagena->id, $homeIsCartagena ? 'home' : 'away', [
                'possession'      => 57,
                'shots_total'     => 14,
                'shots_on_target' => 6,
                'corners'         => 5,
                'fouls'           => 12,
                'offsides'        => 2,
                'yellow_cards'    => 2,
                'red_cards'       => 0,
            ], $now);

            $this->upsertTeamStats($match->id, $marbella->id, $homeIsCartagena ? 'away' : 'home', [
                'possession'      => 43,
                'shots_total'     => 8,
                'shots_on_target' => 3,
                'corners'         => 4,
                'fouls'           => 15,
                'offsides'        => 1,
                'yellow_cards'    => 3,
                'red_cards'       => 0,
            ], $now);

            $this->replaceEvents(
                $match->id,
                $cartagena->id,
                $marbella->id,
                $cartagenaPlayers,
                $marbellaPlayers
            );

            $this->command?->info('✅ Partido demo preparado: FC Cartagena vs Marbella FC.');
            $this->command?->info("➡️ Liga ID: {$league->id} | Partido ID: {$match->id}");
        });
    }

    private function upsertTeam(string $name, string $shortName, string $city): object
    {
        DB::table('teams')->updateOrInsert(
            ['name' => $name],
            [
                'short_name' => $shortName,
                'city'       => $city,
                'crest_url'  => null,
            ]
        );

        return DB::table('teams')->where('name', $name)->first();
    }

    private function upsertSquad(int $teamId, array $squad, Carbon $now): array
    {
        $players = [];

        foreach ($squad as $key => $data) {
            DB::table('players')->updateOrInsert(
                ['full_name' => $data['name']],
                [
                    'position'      => $data['pos'],
                    'date_of_birth' => null,
                    'doc_number'    => null,
                ]
            );

            $player = DB::table('players')
                ->where('full_name', $data['name'])
                ->first();

            DB::table('team_players')->updateOrInsert(
                ['team_id' => $teamId, 'player_id' => $player->id],
                [
                    'shirt_number' => $data['shirt'],
                    'from_date'    => Carbon::parse('2025-07-01')->toDateString(),
                    'to_date'      => null,
                ]
            );

            $players[$key] = [
                'id'        => $player->id,
                'full_name' => $player->full_name,
                'shirt'     => $data['shirt'],
                'pos'       => $data['pos'],
            ];
        }

        return $players;
    }

    private function findOrCreateDemoLeague(int $cartagenaId, int $marbellaId, Carbon $now): object
    {
        $existingLeagueId = DB::table('league_teams as lt1')
            ->join('league_teams as lt2', 'lt2.league_id', '=', 'lt1.league_id')
            ->where('lt1.team_id', $cartagenaId)
            ->where('lt2.team_id', $marbellaId)
            ->value('lt1.league_id');

        if ($existingLeagueId) {
            return DB::table('leagues')->where('id', $existingLeagueId)->first();
        }

        DB::table('leagues')->updateOrInsert(
            ['name' => 'Liga Demo Defensa TFG'],
            [
                'type'        => 'official',
                'group_name'  => 'Grupo Demo',
                'visibility'  => 'public',
                'category_id' => DB::table('categories')->value('id'),
                'season_id'   => DB::table('seasons')->where('code', '2025/26')->value('id')
                    ?? DB::table('seasons')->value('id'),
                'region_id'   => DB::table('regions')->value('id'),
                'is_active'   => true,
                'updated_at'  => $now,
                'created_at'  => $now,
            ]
        );

        return DB::table('leagues')
            ->where('name', 'Liga Demo Defensa TFG')
            ->first();
    }

    private function findOrCreateDemoMatch(int $leagueId, int $cartagenaId, int $marbellaId, Carbon $now): object
    {
        $match = DB::table('matches')
            ->where('league_id', $leagueId)
            ->where(function ($query) use ($cartagenaId, $marbellaId) {
                $query->where(function ($q) use ($cartagenaId, $marbellaId) {
                    $q->where('home_team_id', $cartagenaId)
                        ->where('away_team_id', $marbellaId);
                })->orWhere(function ($q) use ($cartagenaId, $marbellaId) {
                    $q->where('home_team_id', $marbellaId)
                        ->where('away_team_id', $cartagenaId);
                });
            })
            ->orderBy('matchday_number')
            ->first();

        if ($match) {
            return $match;
        }

        $matchdayNumber = ((int) DB::table('matches')
            ->where('league_id', $leagueId)
            ->max('matchday_number')) + 1;

        $matchId = DB::table('matches')->insertGetId([
            'league_id'       => $leagueId,
            'matchday_number' => $matchdayNumber,
            'home_team_id'    => $cartagenaId,
            'away_team_id'    => $marbellaId,
            'scheduled_at'    => Carbon::parse('2026-06-14 19:30:00'),
            'venue_id'        => null,
            'status'          => 'played',
            'home_goals'      => 2,
            'away_goals'      => 1,
            'notes'           => 'Partido demo preparado para la defensa del TFG.',
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        return DB::table('matches')->where('id', $matchId)->first();
    }

    private function upsertLineup(
        int $matchId,
        int $teamId,
        string $side,
        string $formation,
        string $coachName,
        array $players,
        array $starters,
        array $bench,
        Carbon $now
    ): void {
        DB::table('match_lineups')->updateOrInsert(
            ['match_id' => $matchId, 'side' => $side],
            [
                'team_id'    => $teamId,
                'formation'  => $formation,
                'coach_name' => $coachName,
                'starters'   => json_encode($this->lineupItems($players, $starters), JSON_UNESCAPED_UNICODE),
                'bench'      => json_encode($this->lineupItems($players, $bench), JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function lineupItems(array $players, array $keys): array
    {
        return array_map(function (string $key) use ($players) {
            return [
                'player_id' => $players[$key]['id'],
                'full_name' => $players[$key]['full_name'],
                'shirt'     => $players[$key]['shirt'],
                'pos'       => $players[$key]['pos'],
            ];
        }, $keys);
    }

    private function upsertTeamStats(int $matchId, int $teamId, string $side, array $stats, Carbon $now): void
    {
        DB::table('match_team_stats')->updateOrInsert(
            ['match_id' => $matchId, 'side' => $side],
            array_merge($stats, [
                'team_id'    => $teamId,
                'created_at' => $now,
                'updated_at' => $now,
            ])
        );
    }

    private function replaceEvents(
        int $matchId,
        int $cartagenaId,
        int $marbellaId,
        array $cartagena,
        array $marbella
    ): void {
        DB::table('match_events')->where('match_id', $matchId)->delete();

        $events = [
            [
                'minute'            => 23,
                'type'              => 'goal',
                'player_id'         => $cartagena['dm']['id'],
                'related_player_id' => $cartagena['rw']['id'],
                'team_id'           => $cartagenaId,
                'detail'            => 'Gol de Damián Musto tras asistencia de Jairo.',
            ],
            [
                'minute'            => 38,
                'type'              => 'yellow',
                'player_id'         => $marbella['cb2']['id'],
                'related_player_id' => null,
                'team_id'           => $marbellaId,
                'detail'            => 'Tarjeta amarilla por falta táctica.',
            ],
            [
                'minute'            => 51,
                'type'              => 'goal',
                'player_id'         => $marbella['fw2']['id'],
                'related_player_id' => $marbella['fw1']['id'],
                'team_id'           => $marbellaId,
                'detail'            => 'Gol de Sergio Castel tras pase de Hicham.',
            ],
            [
                'minute'            => 64,
                'type'              => 'sub_out',
                'player_id'         => $cartagena['rw']['id'],
                'related_player_id' => $cartagena['fw2']['id'],
                'team_id'           => $cartagenaId,
                'detail'            => 'Sale Jairo.',
            ],
            [
                'minute'            => 64,
                'type'              => 'sub_in',
                'player_id'         => $cartagena['fw2']['id'],
                'related_player_id' => $cartagena['rw']['id'],
                'team_id'           => $cartagenaId,
                'detail'            => 'Entra Dani Escriche.',
            ],
            [
                'minute'            => 70,
                'type'              => 'yellow',
                'player_id'         => $cartagena['cb2']['id'],
                'related_player_id' => null,
                'team_id'           => $cartagenaId,
                'detail'            => 'Tarjeta amarilla por cortar un contraataque.',
            ],
            [
                'minute'            => 78,
                'type'              => 'goal',
                'player_id'         => $cartagena['st']['id'],
                'related_player_id' => $cartagena['cm']['id'],
                'team_id'           => $cartagenaId,
                'detail'            => 'Gol de Alfredo Ortuño tras asistencia de Andy.',
            ],
            [
                'minute'            => 84,
                'type'              => 'yellow',
                'player_id'         => $marbella['cm1']['id'],
                'related_player_id' => null,
                'team_id'           => $marbellaId,
                'detail'            => 'Tarjeta amarilla por protestar.',
            ],
        ];

        foreach ($events as $event) {
            DB::table('match_events')->insert($event + [
                'match_id' => $matchId,
            ]);
        }
    }

    private function cartagenaSquad(): array
    {
        return [
            'gk1' => ['name' => 'Pablo Cuñat',    'pos' => 'GK', 'shirt' => 1],
            'gk2' => ['name' => 'Toni Fuidias',   'pos' => 'GK', 'shirt' => 13],
            'rb'  => ['name' => 'Marc Jurado',    'pos' => 'DF', 'shirt' => 2],
            'cb1' => ['name' => 'Jorge Moreno',   'pos' => 'DF', 'shirt' => 4],
            'cb2' => ['name' => 'Alcalá',         'pos' => 'DF', 'shirt' => 5],
            'lb'  => ['name' => 'Ríos Reina',     'pos' => 'DF', 'shirt' => 15],
            'df3' => ['name' => 'Nil Jiménez',    'pos' => 'DF', 'shirt' => 3],
            'df4' => ['name' => 'Luca Lohr',      'pos' => 'DF', 'shirt' => 20],
            'dm'  => ['name' => 'Damián Musto',   'pos' => 'MF', 'shirt' => 6],
            'cm'  => ['name' => 'Andy',           'pos' => 'MF', 'shirt' => 8],
            'am'  => ['name' => 'Luis Muñoz',     'pos' => 'MF', 'shirt' => 16],
            'mf2' => ['name' => 'Mikel Rico',     'pos' => 'MF', 'shirt' => 10],
            'mf3' => ['name' => 'Iván Ayllón',    'pos' => 'MF', 'shirt' => 14],
            'rw'  => ['name' => 'Jairo',          'pos' => 'MF', 'shirt' => 11],
            'lw'  => ['name' => 'Cedric Teguia',  'pos' => 'FW', 'shirt' => 23],
            'st'  => ['name' => 'Alfredo Ortuño', 'pos' => 'FW', 'shirt' => 7],
            'fw2' => ['name' => 'Dani Escriche',  'pos' => 'FW', 'shirt' => 9],
            'fw3' => ['name' => 'Gastón Valles',  'pos' => 'FW', 'shirt' => 19],
        ];
    }

    private function marbellaSquad(): array
    {
        return [
            'gk1' => ['name' => 'Alberto Lejárraga',  'pos' => 'GK', 'shirt' => 1],
            'gk2' => ['name' => 'Eric Puerto',        'pos' => 'GK', 'shirt' => 13],
            'rb'  => ['name' => 'Aitor Puñal',        'pos' => 'DF', 'shirt' => 2],
            'cb1' => ['name' => 'Yac Diori',          'pos' => 'DF', 'shirt' => 4],
            'cb2' => ['name' => 'José Carrasco',      'pos' => 'DF', 'shirt' => 5],
            'lb'  => ['name' => 'Álex Martínez',      'pos' => 'DF', 'shirt' => 3],
            'df3' => ['name' => 'Marcos Olguín',      'pos' => 'DF', 'shirt' => 14],
            'df4' => ['name' => 'Carlos Cordero',     'pos' => 'DF', 'shirt' => 22],
            'rm'  => ['name' => 'Valentino Fattore',  'pos' => 'MF', 'shirt' => 12],
            'cm1' => ['name' => 'Jonatan Carmona',    'pos' => 'MF', 'shirt' => 8],
            'cm2' => ['name' => 'Pere Marco',         'pos' => 'MF', 'shirt' => 6],
            'lm'  => ['name' => 'Pablo Muñoz',        'pos' => 'MF', 'shirt' => 21],
            'mf3' => ['name' => 'Jorge Álvarez',      'pos' => 'MF', 'shirt' => 16],
            'mf4' => ['name' => 'Hugo Rodríguez',     'pos' => 'MF', 'shirt' => 18],
            'fw1' => ['name' => 'Hicham Boussefiane', 'pos' => 'FW', 'shirt' => 11],
            'fw2' => ['name' => 'Sergio Castel',      'pos' => 'FW', 'shirt' => 9],
            'fw3' => ['name' => 'Dorian Hanza',       'pos' => 'FW', 'shirt' => 7],
            'fw4' => ['name' => 'Rafa Tresaco',       'pos' => 'FW', 'shirt' => 19],
        ];
    }
}