<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MatchListRequest;
use App\Http\Resources\MatchResource;
use App\Models\League;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrivateMatchController extends Controller
{
    /**
     * GET /api/private/leagues/{league}/matchdays/{number}/matches
     * Lista partidos de una jornada (solo miembros).
     */
    public function byMatchday(MatchListRequest $request, League $league, int $number): JsonResponse
    {
        $this->ensurePrivateLeague($league);
        $this->ensureMemberOrOwner($request, $league);

        $status = $request->query('status');
        $group  = $request->query('group');

        $q = DB::table('matches AS m')
            ->join('teams AS th', 'th.id', '=', 'm.home_team_id')
            ->join('teams AS ta', 'ta.id', '=', 'm.away_team_id')
            ->where('m.league_id', $league->id)
            ->where('m.matchday_number', $number);

        if ($status) {
            $q->where('m.status', $status);
        }

        if ($group) {
            $q->join('league_teams AS lth', function($j) use ($league){
                $j->on('lth.team_id', '=', 'm.home_team_id')
                  ->where('lth.league_id', '=', $league->id);
            })->join('league_teams AS lta', function($j) use ($league){
                $j->on('lta.team_id', '=', 'm.away_team_id')
                  ->where('lta.league_id', '=', $league->id);
            })->where('lth.group_name', $group)
              ->where('lta.group_name', $group);
        }

        if (Schema::hasTable('venues')) {
            $q->leftJoin('venues AS v', 'v.id', '=', 'm.venue_id')
              ->selectRaw('v.name AS venue_name');
        } else {
            $q->selectRaw('NULL AS venue_name');
        }

        $rows = $q->selectRaw('m.id, m.league_id, m.matchday_number, m.scheduled_at, m.status, m.home_goals, m.away_goals, m.venue_id')
                  ->selectRaw('th.id AS home_team_id, th.name AS home_team_name')
                  ->selectRaw('ta.id AS away_team_id, ta.name AS away_team_name')
                  ->orderBy('m.scheduled_at')
                  ->orderBy('m.id')
                  ->get();

        return response()->json([
            'data' => MatchResource::collection($rows)->resolve(),
        ]);
    }

    private function ensurePrivateLeague(League $league): void
    {
        if ($league->type !== 'private') {
            abort(404);
        }
    }

    /**
     * 404 si no es miembro/owner (anti-enumeración)
     */
    private function ensureMemberOrOwner(Request $request, League $league): void
    {
        $user = $request->user();

        $isOwner = $league->owner_user_id && ((int)$league->owner_user_id === (int)$user->id);
        if ($isOwner) return;

        $isMember = DB::table('league_memberships')
            ->where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isMember) {
            abort(404);
        }
    }
}
