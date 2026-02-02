<?php

namespace App\Services;

use App\Models\League;
use Illuminate\Support\Facades\DB;

class StandingsSnapshotService
{
    /**
     * Genera y guarda standings hasta $matchday (solo partidos status=played).
     * Guarda/actualiza en tabla standings (league_id + matchday_number).
     */
    public function generateForMatchday(League $league, int $matchday): void
    {
        // Equipos de la liga
        $teams = DB::table('league_teams as lt')
            ->join('teams as t', 't.id', '=', 'lt.team_id')
            ->where('lt.league_id', $league->id)
            ->select('t.id', 't.name', 't.short_name')
            ->orderBy('t.name')
            ->get();

        if ($teams->isEmpty()) {
            return;
        }

        $teamIds = $teams->pluck('id')->map(fn ($id) => (int)$id)->all();

        // Partidos jugados hasta matchday
        $matches = DB::table('matches as m')
            ->where('m.league_id', $league->id)
            ->where('m.status', 'played')
            ->where('m.matchday_number', '<=', $matchday)
            ->whereIn('m.home_team_id', $teamIds)
            ->whereIn('m.away_team_id', $teamIds)
            ->orderBy('m.matchday_number')
            ->orderBy('m.id')
            ->get(['m.home_team_id','m.away_team_id','m.home_goals','m.away_goals']);

        // Inicializar tabla
        $table = [];
        foreach ($teams as $t) {
            $id = (int)$t->id;
            $table[$id] = [
                'team_id' => $id,
                'team' => [
                    'id' => $id,
                    'name' => $t->name,
                    'short_name' => $t->short_name,
                ],
                'played' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'gf' => 0,
                'ga' => 0,
                'gd' => 0,
                'points' => 0,
                'form' => [],
            ];
        }

        foreach ($matches as $m) {
            $h = (int)$m->home_team_id;
            $a = (int)$m->away_team_id;
            $hg = (int)$m->home_goals;
            $ag = (int)$m->away_goals;

            $table[$h]['played']++;
            $table[$a]['played']++;

            $table[$h]['gf'] += $hg;
            $table[$h]['ga'] += $ag;

            $table[$a]['gf'] += $ag;
            $table[$a]['ga'] += $hg;

            if ($hg > $ag) {
                $table[$h]['wins']++;
                $table[$a]['losses']++;
                $table[$h]['points'] += 3;
                $table[$h]['form'][] = 'W';
                $table[$a]['form'][] = 'L';
            } elseif ($hg < $ag) {
                $table[$a]['wins']++;
                $table[$h]['losses']++;
                $table[$a]['points'] += 3;
                $table[$a]['form'][] = 'W';
                $table[$h]['form'][] = 'L';
            } else {
                $table[$h]['draws']++;
                $table[$a]['draws']++;
                $table[$h]['points'] += 1;
                $table[$a]['points'] += 1;
                $table[$h]['form'][] = 'D';
                $table[$a]['form'][] = 'D';
            }
        }

        foreach ($table as &$row) {
            $row['gd'] = $row['gf'] - $row['ga'];
        }

        $rows = array_values($table);

        // Orden estable
        usort($rows, function ($x, $y) {
            if ($x['points'] !== $y['points']) return $y['points'] <=> $x['points'];
            if ($x['gd'] !== $y['gd']) return $y['gd'] <=> $x['gd'];
            if ($x['gf'] !== $y['gf']) return $y['gf'] <=> $x['gf'];
            return strcmp($x['team']['name'], $y['team']['name']);
        });

        DB::table('standings')->updateOrInsert(
            [
                'league_id' => $league->id,
                'matchday_number' => $matchday,
            ],
            [
                'table_json' => json_encode($rows, JSON_UNESCAPED_UNICODE),
                'generated_at' => now(),
            ]
        );
    }
}
