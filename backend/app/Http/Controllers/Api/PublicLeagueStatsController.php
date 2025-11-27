<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\League;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PublicLeagueStatsController extends Controller
{
    /**
     * GET /api/leagues/{league}/stats
     *
     * Devuelve rankings de:
     * - goleadores
     * - asistencias
     * - tarjetas (amarillas + rojas)
     *
     * combinando cálculo automático desde match_events
     * + overrides manuales (league_player_stat_overrides).
     */
    public function show(League $league): JsonResponse
    {
        // 1) Eventos de partidos "played" de esta liga con player_id
        $eventsAgg = DB::table('match_events as e')
            ->join('matches as m', 'm.id', '=', 'e.match_id')
            ->where('m.league_id', $league->id)
            ->where('m.status', 'played')
            ->whereNotNull('e.player_id')
            ->groupBy('e.player_id')
            ->selectRaw("
                e.player_id,
                SUM(CASE WHEN e.type = 'goal' THEN 1 ELSE 0 END)     as goals,
                SUM(CASE WHEN e.type = 'assist' THEN 1 ELSE 0 END)   as assists,
                SUM(CASE WHEN e.type = 'yellow' THEN 1 ELSE 0 END)   as yellow_cards,
                SUM(CASE WHEN e.type = 'red' THEN 1 ELSE 0 END)      as red_cards
            ")
            ->get()
            ->keyBy('player_id');

        // 2) Overrides manuales
        $overrides = DB::table('league_player_stat_overrides as o')
            ->where('o.league_id', $league->id)
            ->get()
            ->keyBy('player_id');

        // 3) Combinar: auto + overrides
        $playerIds = collect($eventsAgg->keys())
            ->merge($overrides->keys())
            ->unique()
            ->values();

        if ($playerIds->isEmpty()) {
            return response()->json([
                'data' => [
                    'league'  => [
                        'id'     => $league->id,
                        'name'   => $league->name,
                        'season' => $league->season?->code,
                    ],
                    'scorers'  => [],
                    'assists'  => [],
                    'cards'    => [],
                ],
            ]);
        }

        // 4) Datos de jugadores
        $players = DB::table('players')
            ->whereIn('id', $playerIds)
            ->pluck('full_name', 'id');

        // 5) Equipo por jugador dentro de esta liga
        $teamsByPlayer = DB::table('team_players as tp')
            ->join('teams as t', 't.id', '=', 'tp.team_id')
            ->join('league_teams as lt', 'lt.team_id', '=', 't.id')
            ->where('lt.league_id', $league->id)
            ->whereIn('tp.player_id', $playerIds)
            ->select('tp.player_id', 't.id as team_id', 't.name as team_name', 't.short_name as team_short_name')
            ->get()
            ->keyBy('player_id');

        // 6) Construir stats finales por jugador
        $stats = [];

        foreach ($playerIds as $pid) {
            $auto = $eventsAgg->get($pid);
            $ov   = $overrides->get($pid);

            $goalsAuto   = $auto ? (int) $auto->goals : 0;
            $assistsAuto = $auto ? (int) $auto->assists : 0;
            $ycAuto      = $auto ? (int) $auto->yellow_cards : 0;
            $rcAuto      = $auto ? (int) $auto->red_cards : 0;

            $goalsDelta  = $ov ? (int) $ov->goals_delta : 0;
            $assistsDelta= $ov ? (int) $ov->assists_delta : 0;
            $ycDelta     = $ov ? (int) $ov->yellow_cards_delta : 0;
            $rcDelta     = $ov ? (int) $ov->red_cards_delta : 0;

            $goals   = $goalsAuto   + $goalsDelta;
            $assists = $assistsAuto + $assistsDelta;
            $yc      = $ycAuto      + $ycDelta;
            $rc      = $rcAuto      + $rcDelta;

            // Si todo es 0, podríamos omitirlo, pero como puede haber overrides,
            // mantenemos el jugador si tiene algún override no nulo.
            if ($goals === 0 && $assists === 0 && $yc === 0 && $rc === 0) {
                continue;
            }

            $teamRow = $teamsByPlayer->get($pid);

            $stats[] = [
                'player_id'   => (int) $pid,
                'player_name' => $players[$pid] ?? null,
                'team'        => $teamRow ? [
                    'id'         => (int) $teamRow->team_id,
                    'name'       => $teamRow->team_name,
                    'short_name' => $teamRow->team_short_name,
                ] : null,
                'goals'       => $goals,
                'assists'     => $assists,
                'yellow_cards'=> $yc,
                'red_cards'   => $rc,
                'auto'        => [
                    'goals'        => $goalsAuto,
                    'assists'      => $assistsAuto,
                    'yellow_cards' => $ycAuto,
                    'red_cards'    => $rcAuto,
                ],
                'delta'      => [
                    'goals'        => $goalsDelta,
                    'assists'      => $assistsDelta,
                    'yellow_cards' => $ycDelta,
                    'red_cards'    => $rcDelta,
                ],
            ];
        }

        // 7) Rankings separados

        $scorers = collect($stats)
            ->filter(fn($s) => $s['goals'] > 0)
            ->sortBy([
                fn($a, $b) => $b['goals'] <=> $a['goals'],
                fn($a, $b) => strcmp($a['player_name'] ?? '', $b['player_name'] ?? ''),
            ])
            ->values()
            ->all();

        $assists = collect($stats)
            ->filter(fn($s) => $s['assists'] > 0)
            ->sortBy([
                fn($a, $b) => $b['assists'] <=> $a['assists'],
                fn($a, $b) => strcmp($a['player_name'] ?? '', $b['player_name'] ?? ''),
            ])
            ->values()
            ->all();

        $cards = collect($stats)
            ->filter(fn($s) => ($s['yellow_cards'] + $s['red_cards']) > 0)
            ->sortBy([
                fn($a, $b) =>
                    ($b['yellow_cards'] + $b['red_cards'])
                    <=>
                    ($a['yellow_cards'] + $a['red_cards']),
                fn($a, $b) => strcmp($a['player_name'] ?? '', $b['player_name'] ?? ''),
            ])
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'league'  => [
                    'id'     => $league->id,
                    'name'   => $league->name,
                    'season' => $league->season?->code,
                ],
                'scorers' => $scorers,
                'assists' => $assists,
                'cards'   => $cards,
            ],
        ]);
    }
}
