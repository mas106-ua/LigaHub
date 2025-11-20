<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StandingsRequest;
use App\Models\League;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PublicStandingsController extends Controller
{
    /**
     * GET /api/leagues/{league}/standings?group=&matchday=
     *
     * Devuelve la clasificación de una liga (y grupo opcional)
     * hasta una jornada concreta (o hasta la última jugada si no se indica).
     */
    public function show(StandingsRequest $request, League $league): JsonResponse
    {
        $group    = $request->query('group');
        $matchday = $request->query('matchday');

        // Cargamos info básica de la liga (para metadata)
        $league->loadMissing(['season', 'category', 'region']);

        // 1) Equipos de la liga (y grupo opcional)
        $teamsQuery = DB::table('league_teams AS lt')
            ->join('teams AS t', 't.id', '=', 'lt.team_id')
            ->where('lt.league_id', $league->id);

        if ($group) {
            $teamsQuery->where('lt.group_name', $group);
        }

        $teams = $teamsQuery
            ->select('lt.team_id', 'lt.group_name', 't.name', 't.short_name')
            ->orderBy('t.name')
            ->get();

        if ($teams->isEmpty()) {
            return response()->json([
                'data' => [
                    'league' => [
                        'id'       => $league->id,
                        'name'     => $league->name,
                        'season'   => $league->season?->code,
                        'category' => $league->category?->name,
                        'region'   => $league->region?->code,
                    ],
                    'group'        => $group,
                    'matchday'     => $matchday ? (int) $matchday : null,
                    'max_matchday' => null,
                    'rows'         => [],
                ],
            ]);
        }

        $teamIds = $teams->pluck('team_id')->map(fn($id) => (int)$id)->all();

        // 2) Partidos "played" de esta liga y solo entre equipos de ese set
        $baseMatches = DB::table('matches AS m')
            ->where('m.league_id', $league->id)
            ->where('m.status', 'played')
            ->whereIn('m.home_team_id', $teamIds)
            ->whereIn('m.away_team_id', $teamIds);

        // Máxima jornada jugada (dentro de este grupo)
        $maxMatchday = (clone $baseMatches)->max('m.matchday_number');

        // Jornada objetivo
        if ($matchday) {
            $targetMatchday = (int) $matchday;
            if ($maxMatchday && $targetMatchday > $maxMatchday) {
                $targetMatchday = (int) $maxMatchday;
            }
        } else {
            $targetMatchday = $maxMatchday ?: null;
        }

        if ($targetMatchday) {
            $matches = (clone $baseMatches)
                ->where('m.matchday_number', '<=', $targetMatchday)
                ->orderBy('m.matchday_number')
                ->orderBy('m.id')
                ->get([
                    'm.matchday_number',
                    'm.home_team_id',
                    'm.away_team_id',
                    'm.home_goals',
                    'm.away_goals',
                ]);
        } else {
            $matches = collect();
        }

        // 3) Inicializar tabla por equipo
        $table = [];

        foreach ($teams as $t) {
            $id = (int) $t->team_id;
            $table[$id] = [
                'team_id' => $id,
                'team'    => [
                    'id'         => $id,
                    'name'       => $t->name,
                    'short_name' => $t->short_name,
                ],
                'played' => 0,
                'wins'   => 0,
                'draws'  => 0,
                'losses' => 0,
                'gf'     => 0,
                'ga'     => 0,
                'gd'     => 0,
                'points' => 0,
                // racha: array de resultados en orden cronológico (W/D/L)
                'form'   => [],
            ];
        }

        // 4) Acumular estadísticas a partir de los partidos
        foreach ($matches as $m) {
            $homeId = (int) $m->home_team_id;
            $awayId = (int) $m->away_team_id;

            if (!isset($table[$homeId]) || !isset($table[$awayId])) {
                continue;
            }

            $hg = (int) $m->home_goals;
            $ag = (int) $m->away_goals;

            // Jugados
            $table[$homeId]['played']++;
            $table[$awayId]['played']++;

            // Goles
            $table[$homeId]['gf'] += $hg;
            $table[$homeId]['ga'] += $ag;
            $table[$awayId]['gf'] += $ag;
            $table[$awayId]['ga'] += $hg;

            // Resultado y puntos (3-1-0)
            if ($hg > $ag) {
                // Local gana
                $table[$homeId]['wins']++;
                $table[$awayId]['losses']++;
                $table[$homeId]['points'] += 3;

                $homeRes = 'W';
                $awayRes = 'L';
            } elseif ($hg < $ag) {
                // Visitante gana
                $table[$awayId]['wins']++;
                $table[$homeId]['losses']++;
                $table[$awayId]['points'] += 3;

                $homeRes = 'L';
                $awayRes = 'W';
            } else {
                // Empate
                $table[$homeId]['draws']++;
                $table[$awayId]['draws']++;
                $table[$homeId]['points']++;
                $table[$awayId]['points']++;

                $homeRes = 'D';
                $awayRes = 'D';
            }

            // Racha (forma)
            $table[$homeId]['form'][] = $homeRes;
            $table[$awayId]['form'][] = $awayRes;
        }

        // 5) Cerrar gd y recortar racha a últimos 5
        foreach ($table as &$row) {
            $row['gd'] = $row['gf'] - $row['ga'];

            $form = $row['form'];
            if (count($form) > 5) {
                $form = array_slice($form, -5);
            }
            $row['form'] = $form;
        }
        unset($row);

        // 6) Ordenar tabla con desempates
        $rows = array_values($table);

        usort($rows, function (array $a, array $b) use ($matches) {
            // 1) Puntos totales
            if ($a['points'] !== $b['points']) {
                return $b['points'] <=> $a['points'];
            }

            // 2) Diferencia de goles en enfrentamientos directos
            $aId = $a['team_id'];
            $bId = $b['team_id'];

            $aH2Hgf = 0;
            $aH2Hga = 0;
            $bH2Hgf = 0;
            $bH2Hga = 0;

            foreach ($matches as $m) {
                $homeId = (int) $m->home_team_id;
                $awayId = (int) $m->away_team_id;

                // Solo partidos entre A y B
                if (
                    ($homeId === $aId && $awayId === $bId) ||
                    ($homeId === $bId && $awayId === $aId)
                ) {
                    $hg = (int) $m->home_goals;
                    $ag = (int) $m->away_goals;

                    if ($homeId === $aId && $awayId === $bId) {
                        $aH2Hgf += $hg;
                        $aH2Hga += $ag;
                        $bH2Hgf += $ag;
                        $bH2Hga += $hg;
                    } elseif ($homeId === $bId && $awayId === $aId) {
                        $bH2Hgf += $hg;
                        $bH2Hga += $ag;
                        $aH2Hgf += $ag;
                        $aH2Hga += $hg;
                    }
                }
            }

            $aH2Hgd = $aH2Hgf - $aH2Hga;
            $bH2Hgd = $bH2Hgf - $bH2Hga;

            if ($aH2Hgd !== $bH2Hgd) {
                // El que tenga mejor DG en enfrentamientos directos va delante
                return $bH2Hgd <=> $aH2Hgd;
            }

            // 3) Diferencia de goles global
            if ($a['gd'] !== $b['gd']) {
                return $b['gd'] <=> $a['gd'];
            }

            // 4) Goles a favor global
            if ($a['gf'] !== $b['gf']) {
                return $b['gf'] <=> $a['gf'];
            }

            // 5) Nombre de equipo (para tener orden estable)
            return strcmp($a['team']['name'], $b['team']['name']);
        });

        // 7) Asignar posición
        $pos = 1;
        foreach ($rows as &$row) {
            $row['position'] = $pos++;
        }
        unset($row);

        return response()->json([
            'data' => [
                'league' => [
                    'id'       => $league->id,
                    'name'     => $league->name,
                    'season'   => $league->season?->code,
                    'category' => $league->category?->name,
                    'region'   => $league->region?->code,
                ],
                'group'        => $group,
                'matchday'     => $targetMatchday,
                'max_matchday' => $maxMatchday,
                'rows'         => $rows,
            ],
        ]);
    }
}
