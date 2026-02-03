<?php

namespace App\Services;

use App\Models\League;
use Illuminate\Support\Facades\DB;

class PrivateLeagueStandingsService
{
    public function calculate(League $league): array
    {
        // 1. Equipos de la liga
        $teams = DB::table('league_teams')
            ->join('teams', 'teams.id', '=', 'league_teams.team_id')
            ->where('league_teams.league_id', $league->id)
            ->select('teams.id', 'teams.name')
            ->orderBy('teams.name')
            ->get();

        if ($teams->isEmpty()) {
            return [];
        }

        // 2. Inicializar tabla
        $table = [];
        foreach ($teams as $team) {
            $table[$team->id] = [
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                ],
                'played' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'gf' => 0,
                'ga' => 0,
                'gd' => 0,
                'points' => 0,
            ];
        }

        // 3. Partidos jugados
        $matches = DB::table('matches')
            ->where('league_id', $league->id)
            ->where('status', 'played')
            ->get([
                'home_team_id',
                'away_team_id',
                'home_goals',
                'away_goals',
            ]);

        // 4. Procesar partidos
        foreach ($matches as $m) {
            $home = $m->home_team_id;
            $away = $m->away_team_id;

            if (!isset($table[$home]) || !isset($table[$away])) {
                continue;
            }

            $hg = (int) $m->home_goals;
            $ag = (int) $m->away_goals;

            $table[$home]['played']++;
            $table[$away]['played']++;

            $table[$home]['gf'] += $hg;
            $table[$home]['ga'] += $ag;

            $table[$away]['gf'] += $ag;
            $table[$away]['ga'] += $hg;

            if ($hg > $ag) {
                $table[$home]['wins']++;
                $table[$home]['points'] += 3;
                $table[$away]['losses']++;
            } elseif ($hg < $ag) {
                $table[$away]['wins']++;
                $table[$away]['points'] += 3;
                $table[$home]['losses']++;
            } else {
                $table[$home]['draws']++;
                $table[$away]['draws']++;
                $table[$home]['points']++;
                $table[$away]['points']++;
            }
        }

        // 5. Diferencia de goles
        foreach ($table as &$row) {
            $row['gd'] = $row['gf'] - $row['ga'];
        }

        // 6. Ordenar (criterio estable)
        $rows = array_values($table);

        usort($rows, function ($a, $b) {
            return
                $b['points'] <=> $a['points']
                ?: $b['gd'] <=> $a['gd']
                ?: $b['gf'] <=> $a['gf']
                ?: strcmp($a['team']['name'], $b['team']['name']);
        });

        // 7. Añadir posición
        foreach ($rows as $i => &$row) {
            $row['position'] = $i + 1;
        }

        return $rows;
    }
}
