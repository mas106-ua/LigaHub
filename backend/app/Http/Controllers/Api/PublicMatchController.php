<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MatchListRequest;
use App\Http\Resources\MatchResource;
use App\Models\League;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\JsonResponse;

class PublicMatchController extends Controller
{
    public function byMatchday(MatchListRequest $request, League $league, int $number): JsonResponse
    {
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
}
