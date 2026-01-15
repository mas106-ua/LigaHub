<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\League;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyLeaguesController extends Controller
{
    /**
     * GET /api/my/leagues?type=private
     *
     * Devuelve ligas donde el usuario es miembro + su rol (league_memberships.role_in_league).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Por defecto: privadas (lo que necesitas para Sprint 4)
        $type = (string) $request->query('type', 'private');

        if (!in_array($type, ['private', 'official'], true)) {
            return response()->json([
                'message' => 'Invalid type. Allowed: private, official',
            ], 422);
        }

        $rows = League::query()
            ->select([
                'leagues.*',
                'league_memberships.role_in_league',
                'league_memberships.joined_at',
            ])
            ->join('league_memberships', 'league_memberships.league_id', '=', 'leagues.id')
            ->where('league_memberships.user_id', $user->id)
            ->where('leagues.type', $type)
            ->with([
                'season:id,code,start_date,end_date',
            ])
            ->orderByDesc('league_memberships.joined_at')
            ->orderByDesc('leagues.id')
            ->get();

        $data = $rows->map(function (League $league) {
            return [
                'id'            => $league->id,
                'name'          => $league->name,
                'type'          => $league->type,
                'visibility'    => $league->visibility,
                'owner_user_id' => $league->owner_user_id,

                'role_in_league'=> $league->role_in_league,
                'joined_at'     => $league->joined_at,

                'season' => $league->season ? [
                    'id'         => $league->season->id,
                    'code'       => $league->season->code,
                    'start_date' => $league->season->start_date,
                    'end_date'   => $league->season->end_date,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}
