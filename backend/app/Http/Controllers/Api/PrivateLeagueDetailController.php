<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\League;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivateLeagueDetailController extends Controller
{
    /**
     * GET /api/private/leagues/{league}/detail
     *
     * Detalle de liga privada (solo miembros).
     */
    public function show(Request $request, League $league): JsonResponse
    {
        // 1) Solo privadas
        if ($league->type !== 'private') {
            abort(404);
        }

        $user = $request->user();

        // 2) Membership (cualquier rol) o owner directo
        $membership = DB::table('league_memberships')
            ->where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->first(['role_in_league', 'joined_at']);

        $isOwner = $league->owner_user_id && ((int)$league->owner_user_id === (int)$user->id);

        if (!$membership && !$isOwner) {
            // 404 para evitar enumeración
            abort(404);
        }

        // Cargamos relaciones básicas (igual que el público)
        $league->loadMissing([
            'category:id,name',
            'season:id,code',
            'region:id,code,name',

            'competition.category:id,name,level,gender',
            'competition.region:id,code,name',
        ]);

        $competition   = $league->competition;
        $categoryModel = $competition?->category ?? $league->category;
        $regionModel   = $competition?->region   ?? $league->region;

        // 1) Grupos (league_teams.group_name)
        $groupRows = DB::table('league_teams')
            ->where('league_id', $league->id)
            ->select('group_name')
            ->distinct()
            ->orderBy('group_name')
            ->get();

        $groups = $groupRows->map(function ($row) {
            $key = $row->group_name ?? null;
            return [
                'key'  => $key,
                'name' => $key ?: 'Único'
            ];
        });

        if ($groups->isEmpty()) {
            $groups = collect([['key' => null, 'name' => 'Único']]);
        }

        // 2) Rango de jornadas y totales
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

        // 3) Equipos
        $teams = DB::table('league_teams as lt')
            ->join('teams as t', 't.id', '=', 'lt.team_id')
            ->where('lt.league_id', $league->id)
            ->orderBy('t.name')
            ->get([
                't.id as id',
                't.name as name',
                't.short_name as short_name',
                'lt.group_name as group',
            ]);

        // 4) Features (igual que público)
        $stand = DB::table('standings')
            ->where('league_id', $league->id)
            ->selectRaw('MAX(matchday_number) as max_matchday')
            ->first();

        $hasStandings = $stand && $stand->max_matchday !== null;

        $hasStats = DB::table('match_events as e')
            ->join('matches as m', 'm.id', '=', 'e.match_id')
            ->where('m.league_id', $league->id)
            ->whereNotNull('e.player_id')
            ->exists();

        $role = $membership?->role_in_league ?? ($isOwner ? 'owner' : null);
        $canManage = $user->canManageLeague($league);

        return response()->json([
            'data' => [
                'id'   => $league->id,
                'name' => $league->name,

                'season' => [
                    'id'   => $league->season?->id,
                    'code' => $league->season?->code,
                ],

                'category' => [
                    'id'   => $categoryModel?->id,
                    'name' => $categoryModel?->name,
                ],

                'region' => [
                    'id'   => $regionModel?->id,
                    'code' => $regionModel?->code,
                    'name' => $regionModel?->name,
                ],

                'groups' => $groups->values(),

                'matchdays' => [
                    'min'            => $minMatchday,
                    'max'            => $maxMatchday,
                    'total_matches'  => (int) ($totals->total_matches ?? 0),
                    'played_matches' => (int) ($totals->played_matches ?? 0),
                ],

                'teams' => $teams,

                'features' => [
                    'has_matchdays' => $minMatchday !== null,
                    'has_standings' => $hasStandings,
                    'has_stats'     => $hasStats,
                ],

                // Útil para FE (sin cambiar la shape del público)
                'permissions' => [
                    'role_in_league' => $role,
                    'can_manage'     => $canManage,
                ],
            ],
        ]);
    }
}
