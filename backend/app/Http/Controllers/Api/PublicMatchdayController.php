<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MatchdayListRequest;
use App\Http\Resources\MatchdaySummaryResource;
use App\Models\League;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class PublicMatchdayController extends Controller
{
    public function index(MatchdayListRequest $request, League $league): JsonResponse
    {
        $group = $request->query('group');

        $q = DB::table('matches AS m')
            ->where('m.league_id', $league->id);

        if ($group) {
            // ambos equipos pertenecen al grupo en esa liga
            $q->join('league_teams AS lth', function($j) use ($league){
                $j->on('lth.team_id', '=', 'm.home_team_id')
                  ->where('lth.league_id', '=', $league->id);
            })->join('league_teams AS lta', function($j) use ($league){
                $j->on('lta.team_id', '=', 'm.away_team_id')
                  ->where('lta.league_id', '=', $league->id);
            })->where('lth.group_name', $group)
              ->where('lta.group_name', $group);
        }

        $rows = $q->selectRaw('m.matchday_number AS number')
                  ->selectRaw('COUNT(*) AS matches_count')
                  ->selectRaw("SUM(m.status = 'played') AS played_count")
                  ->selectRaw('MIN(m.scheduled_at) AS first_date')
                  ->selectRaw('MAX(m.scheduled_at) AS last_date')
                  ->groupBy('m.matchday_number')
                  ->orderBy('m.matchday_number')
                  ->get();

        return response()->json([
            'data' => MatchdaySummaryResource::collection($rows)->resolve(),
        ]);
    }
}
