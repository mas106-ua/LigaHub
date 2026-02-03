<?php

namespace App\Services;

use App\Models\League;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivateLeagueStatsService
{
    public function calculate(League $league, Request $request): array
    {
        // Partidos jugados de la liga
        $query = DB::table('matches')
            ->where('league_id', $league->id)
            ->where('status', 'played');

        // Filtros por jornada
        if ($request->filled('from_matchday')) {
            $query->where('matchday_number', '>=', (int) $request->from_matchday);
        }

        if ($request->filled('to_matchday')) {
            $query->where('matchday_number', '<=', (int) $request->to_matchday);
        }

        $matches = $query
            ->orderBy('matchday_number')
            ->orderBy('id')
            ->get([
                'home_team_id',
                'away_team_id',
                'home_goals',
                'away_goals',
            ]);

        // Equipos de la liga
        $teams = DB::table('league_teams')
            ->join('teams', 'teams.id', '=', 'league_teams.team_id')
            ->where('league_teams.league_id', $league->id)
            ->select('teams.id', 'teams.name')
            ->orderBy('teams.name')
            ->get();

        if ($teams->isEmpty()) {
            return $this->emptyResponse();
        }

        // Inicializar estructura por equipo
        $stats = [];
        foreach ($teams as $t) {
            $stats[$t->id] = [
                'team' => [
                    'id' => $t->id,
                    'name' => $t->name,
                ],
                'played' => 0,
                'gf' => 0,
                'ga' => 0,
                'form' => [],
                'clean_sheets' => 0,
            ];
        }

        // Procesar partidos
        foreach ($matches as $m) {
            $h = $m->home_team_id;
            $a = $m->away_team_id;

            if (!isset($stats[$h]) || !isset($stats[$a])) {
                continue;
            }

            $hg = (int) $m->home_goals;
            $ag = (int) $m->away_goals;

            // Partidos jugados
            $stats[$h]['played']++;
            $stats[$a]['played']++;

            // Goles
            $stats[$h]['gf'] += $hg;
            $stats[$h]['ga'] += $ag;

            $stats[$a]['gf'] += $ag;
            $stats[$a]['ga'] += $hg;

            // Porterías a cero
            if ($ag === 0) {
                $stats[$h]['clean_sheets']++;
            }
            if ($hg === 0) {
                $stats[$a]['clean_sheets']++;
            }

            // Forma
            if ($hg > $ag) {
                $stats[$h]['form'][] = 'W';
                $stats[$a]['form'][] = 'L';
            } elseif ($hg < $ag) {
                $stats[$a]['form'][] = 'W';
                $stats[$h]['form'][] = 'L';
            } else {
                $stats[$h]['form'][] = 'D';
                $stats[$a]['form'][] = 'D';
            }
        }

        // Últimos N partidos para forma
        $lastN = (int) $request->get('last_n', 5);
        foreach ($stats as &$row) {
            $row['form'] = array_slice($row['form'], -$lastN);
        }
        unset($row);

        /*
         |--------------------------------------------------------------------------
         | Rankings y KPIs
         |--------------------------------------------------------------------------
         */

        // Ranking por forma (últimos N)
        $formRanking = collect($stats)
            ->map(function ($r) {
                $points = 0;
                foreach ($r['form'] as $res) {
                    if ($res === 'W') $points += 3;
                    elseif ($res === 'D') $points += 1;
                }

                return [
                    'team' => $r['team'],
                    'points_last_n' => $points,
                    'form' => $r['form'],
                ];
            })
            ->sortByDesc('points_last_n')
            ->values()
            ->all();

        // Goles por partido
        $goalsPerMatch = collect($stats)
            ->map(fn ($r) => [
                'team' => $r['team'],
                'value' => $r['played'] > 0
                    ? round($r['gf'] / $r['played'], 2)
                    : 0,
            ])
            ->values()
            ->all();

        // Porterías a cero (ranking)
        $cleanSheetsRanking = collect($stats)
            ->sortByDesc('clean_sheets')
            ->map(fn ($r) => [
                'team' => $r['team'],
                'value' => $r['clean_sheets'],
            ])
            ->values()
            ->all();

        // Equipos más goleadores
        $mostScoringTeams = collect($stats)
            ->sortByDesc('gf')
            ->map(fn ($r) => [
                'team' => $r['team'],
                'value' => $r['gf'],
            ])
            ->values()
            ->all();

        // Equipos más goleados
        $mostConcedingTeams = collect($stats)
            ->sortByDesc('ga')
            ->map(fn ($r) => [
                'team' => $r['team'],
                'value' => $r['ga'],
            ])
            ->values()
            ->all();

        /*
         |--------------------------------------------------------------------------
         | Respuesta final
         |--------------------------------------------------------------------------
         */

        return [
            'form' => array_values(
                array_map(fn ($r) => [
                    'team' => $r['team'],
                    'form' => $r['form'],
                ], $stats)
            ),

            'form_ranking' => $formRanking,

            'goals_per_match' => $goalsPerMatch,

            'clean_sheets' => array_values(
                array_map(fn ($r) => [
                    'team' => $r['team'],
                    'value' => $r['clean_sheets'],
                ], $stats)
            ),

            'clean_sheets_ranking' => $cleanSheetsRanking,

            'most_scoring_teams' => $mostScoringTeams,

            'most_conceding_teams' => $mostConcedingTeams,

            // Preparado para futuros sprints (jugadores / eventos)
            'top_scorers' => [],
        ];
    }

    private function emptyResponse(): array
    {
        return [
            'form' => [],
            'form_ranking' => [],
            'goals_per_match' => [],
            'clean_sheets' => [],
            'clean_sheets_ranking' => [],
            'most_scoring_teams' => [],
            'most_conceding_teams' => [],
            'top_scorers' => [],
        ];
    }
}
