<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublicMatchDetailController extends Controller
{
    public function show(int $id): JsonResponse
    {
        // 1) Partido + equipos + venue
        /** @var \App\Models\MatchModel $match */
        $match = MatchModel::query()
            ->with([
                'homeTeam:id,name,short_name,crest_url',
                'awayTeam:id,name,short_name,crest_url',
                'venue:id,name,city,address',
                'league:id,name,season_id',
            ])
            ->findOrFail($id);

                // 2) Eventos (adaptado a tu tabla: 'detail' y players.full_name)
        $events = [];
        if (Schema::hasTable('match_events')) {
            $events = DB::table('match_events as e')
                ->leftJoin('teams as t', 't.id', '=', 'e.team_id')
                ->leftJoin('players as p', 'p.id', '=', 'e.player_id')
                ->leftJoin('players as rp', 'rp.id', '=', 'e.related_player_id')
                ->where('e.match_id', $match->id)
                ->orderBy('e.minute')
                ->orderBy('e.id')
                ->get([
                    'e.id',
                    'e.match_id',
                    'e.minute',
                    'e.type',
                    'e.team_id',
                    DB::raw('t.name as team_name'),
                    'e.player_id',
                    DB::raw('p.full_name as player_name'),
                    'e.related_player_id',
                    DB::raw('rp.full_name as related_player_name'),
                    'e.detail',
                ])
                ->map(function ($row) use ($match) {
                    // Calculamos 'side' según team_id
                    $side = null;
                    if ($row->team_id === $match->home_team_id) {
                        $side = 'home';
                    } elseif ($row->team_id === $match->away_team_id) {
                        $side = 'away';
                    }

                    return [
                        'id'                   => (int) $row->id,
                        'match_id'             => (int) $row->match_id,
                        'minute'               => $row->minute,
                        'type'                 => $row->type,
                        'side'                 => $side,
                        'team_id'              => $row->team_id,
                        'team_name'            => $row->team_name,
                        'player_id'            => $row->player_id,
                        'player_name'          => $row->player_name,
                        'related_player_id'    => $row->related_player_id,
                        'related_player_name'  => $row->related_player_name,
                        'detail'               => $row->detail,
                        // Formato “rico” para el frontend
                        'player' => $row->player_id ? [
                            'id'   => $row->player_id,
                            'name' => $row->player_name,
                        ] : null,
                        'related_player' => $row->related_player_id ? [
                            'id'   => $row->related_player_id,
                            'name' => $row->related_player_name,
                        ] : null,
                    ];
                })
                ->values();
        }

        // 3) Estadísticas de equipo (si algún día añades match_team_stats)
        $teamStats = [];
        if (Schema::hasTable('match_team_stats')) {
        $teamStats = DB::table('match_team_stats as s')
            ->leftJoin('teams as t','t.id','=','s.team_id')
            ->where('s.match_id', $match->id)
            ->orderByRaw("FIELD(s.side,'home','away')")
            ->get([
            's.side','s.team_id','t.name as team_name',
            's.possession','s.shots_total','s.shots_on_target',
            's.corners','s.fouls','s.offsides','s.yellow_cards','s.red_cards'
            ]);
        }

        $lineups = [];
        if (Schema::hasTable('match_lineups')) {
            $rows = DB::table('match_lineups as ml')
                ->leftJoin('teams as t','t.id','=','ml.team_id')
                ->where('ml.match_id', $match->id)
                ->orderByRaw("FIELD(ml.side,'home','away')")
                ->get([
                    'ml.side','ml.team_id','ml.formation','ml.coach_name','ml.starters','ml.bench',
                    DB::raw('t.name as team_name'),
                ]);

            // Recoge todos los player_id presentes
            $allIds = [];
            foreach ($rows as $r) {
                foreach (['starters','bench'] as $k) {
                    $arr = json_decode($r->$k ?? '[]', true);
                    if (!is_array($arr)) $arr = [];
                    foreach ($arr as $it) {
                        if (isset($it['player_id'])) $allIds[] = (int) $it['player_id'];
                    }
                }
            }

            $names = [];
            if ($allIds && Schema::hasTable('players')) {
                $names = DB::table('players')
                    ->whereIn('id', array_values(array_unique($allIds)))
                    ->pluck('full_name','id')
                    ->all();
            }

            $normalize = function ($json) use ($names) {
                $arr = json_decode($json ?? '[]', true);
                if (!is_array($arr)) $arr = [];

                $out = array_map(function ($it) use ($names) {
                    $pid = isset($it['player_id']) ? (int) $it['player_id'] : null;
                    return [
                        'player_id'   => $pid,
                        'player_name' => ($pid && isset($names[$pid])) ? $names[$pid] : null,
                        'shirt'       => isset($it['shirt']) ? (int) $it['shirt'] : null,
                        'pos'         => $it['pos'] ?? null,
                    ];
                }, $arr);

                // Ordena por dorsal (los null al final)
                usort($out, function ($a, $b) {
                    return ($a['shirt'] ?? 999) <=> ($b['shirt'] ?? 999);
                });

                return array_values($out);
            };

            $lineups = collect($rows)->map(function ($r) use ($normalize) {
                return [
                    'side'       => $r->side, // 'home' | 'away'
                    'team_id'    => (int) $r->team_id,
                    'team_name'  => $r->team_name,
                    'formation'  => $r->formation,
                    'coach_name' => $r->coach_name,
                    'starters'   => $normalize($r->starters),
                    'bench'      => $normalize($r->bench),
                ];
            })->values();
        }


        // 4) Respuesta
        return response()->json([
            'data' => [
                'id'        => $match->id,
                'league_id' => $match->league_id,
                'matchday'  => $match->matchday_number,
                'scheduled_at' => optional($match->scheduled_at)->format('Y-m-d H:i:s'),
                'status'    => $match->status,
                'score'     => [
                    'home' => (int) $match->home_goals,
                    'away' => (int) $match->away_goals,
                ],
                'home_team' => [
                    'id'   => $match->homeTeam->id,
                    'name' => $match->homeTeam->name,
                    'short_name' => $match->homeTeam->short_name,
                    'crest_url'  => $match->homeTeam->crest_url,
                ],
                'away_team' => [
                    'id'   => $match->awayTeam->id,
                    'name' => $match->awayTeam->name,
                    'short_name' => $match->awayTeam->short_name,
                    'crest_url'  => $match->awayTeam->crest_url,
                ],
                'venue' => $match->venue ? [
                    'id' => $match->venue->id,
                    'name' => $match->venue->name,
                    'city' => $match->venue->city,
                    'address' => $match->venue->address,
                ] : null,

                'events' => $events,     // contiene minute,type,team_name,player_name,detail,side…
                'team_stats' => $teamStats, // si existe la tabla
                'lineups'    => $lineups,
            ],
        ]);

        function syncScoreFromEvents(int $matchId): void {
        $m = MatchModel::findOrFail($matchId);
        $ev = DB::table('match_events')->where('match_id', $matchId)->get();

        $home = 0; $away = 0;
        foreach ($ev as $e) {
            if ($e->type === 'goal') {
            if ($e->team_id == $m->home_team_id) $home++;
            if ($e->team_id == $m->away_team_id) $away++;
            } elseif ($e->type === 'own_goal') {
            if ($e->team_id == $m->home_team_id) $away++;
            if ($e->team_id == $m->away_team_id) $home++;
            }
        }
        $m->home_goals = $home;
        $m->away_goals = $away;
        if ($m->status !== 'played') $m->status = 'played';
        $m->save();
        }
    }
}
