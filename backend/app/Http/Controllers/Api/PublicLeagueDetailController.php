<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\League;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PublicLeagueDetailController extends Controller
{
    /**
     * GET /api/leagues/{league}/detail
     *
     * Ficha integral de liga:
     * - info básica
     * - grupos
     * - rango de jornadas
     * - equipos
     * - flags de clasificación / stats
     * - enlaces sugeridos a otras vistas
     */
    public function show(League $league): JsonResponse
    {
        // Cargamos relaciones básicas
        $league->loadMissing([
            'category:id,name',
            'season:id,code',
            'region:id,code,name',
        ]);

        // 1) Grupos (usando league_teams.group_name)
        $groupRows = DB::table('league_teams')
            ->where('league_id', $league->id)
            ->select('group_name')
            ->distinct()
            ->orderBy('group_name')
            ->get();

        $groups = $groupRows->map(function ($row) {
            $key = $row->group_name ?? null;

            return [
                'key'  => $key,           // valor bruto (ej. "G1", "A", null)
                'name' => $key ?: 'Único' // etiqueta para mostrar
            ];
        });

        if ($groups->isEmpty()) {
            $groups = collect([
                [
                    'key'  => null,
                    'name' => 'Único',
                ],
            ]);
        }

        // 2) Rango de jornadas y totales de partidos
        $matchdayStats = DB::table('matches')
            ->where('league_id', $league->id)
            ->selectRaw('MIN(matchday_number) as min_matchday, MAX(matchday_number) as max_matchday')
            ->first();

        $totals = DB::table('matches')
            ->where('league_id', $league->id)
            ->selectRaw("
                COUNT(*) as total_matches,
                SUM(CASE WHEN status = 'played' THEN 1 ELSE 0 END) as played_matches
            ")
            ->first();

        $minMatchday = $matchdayStats?->min_matchday;
        $maxMatchday = $matchdayStats?->max_matchday;

        // 3) Equipos de la liga
        $teams = DB::table('league_teams as lt')
            ->join('teams as t', 't.id', '=', 'lt.team_id')
            ->where('lt.league_id', $league->id)
            ->orderBy('t.name')
            ->get([
                't.id as id',
                't.name as name',
                't.short_name as short_name',
                // exponemos group_name como "group" en la respuesta
                'lt.group_name as group',
            ]);

        // 4) ¿Hay standings calculados?
        $stand = DB::table('standings')
            ->where('league_id', $league->id)
            ->selectRaw('MAX(matchday_number) as max_matchday')
            ->first();

        $hasStandings = $stand && $stand->max_matchday !== null;

        // 5) ¿Hay eventos con jugadores para stats?
        $hasStats = DB::table('match_events as e')
            ->join('matches as m', 'm.id', '=', 'e.match_id')
            ->where('m.league_id', $league->id)
            ->whereNotNull('e.player_id')
            ->exists();

        return response()->json([
            'data' => [
                'id'   => $league->id,
                'name' => $league->name,

                'season' => [
                    'id'   => $league->season?->id,
                    'code' => $league->season?->code,
                ],

                'category' => [
                    'id'   => $league->category?->id,
                    'name' => $league->category?->name,
                ],

                'region' => [
                    'id'   => $league->region?->id,
                    'code' => $league->region?->code,
                    'name' => $league->region?->name,
                ],

                // Lista de grupos disponibles para filtros
                'groups' => $groups->values(),

                // Rango de jornadas y resumen de partidos
                'matchdays' => [
                    'min'            => $minMatchday,
                    'max'            => $maxMatchday,
                    'total_matches'  => (int) ($totals->total_matches ?? 0),
                    'played_matches' => (int) ($totals->played_matches ?? 0),
                ],

                // Equipos participantes en esta liga
                'teams' => $teams,

                // Flags para saber qué pestañas tienen sentido
                'features' => [
                    'has_matchdays' => $minMatchday !== null,
                    'has_standings' => $hasStandings,
                    'has_stats'     => $hasStats,
                ],

                // Enlaces sugeridos a otros endpoints de la API
                'links' => [
                    'matchdays' => "/api/leagues/{$league->id}/matchdays",
                    'standings' => "/api/leagues/{$league->id}/standings",
                    'stats'     => "/api/leagues/{$league->id}/stats",
                ],
            ],
        ]);
    }
}
