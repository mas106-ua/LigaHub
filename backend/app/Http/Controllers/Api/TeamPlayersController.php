<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class TeamPlayersController extends Controller
{
    /**
     * Devuelve la plantilla de un equipo.
     *
     * GET /api/teams/{team}/players
     */
    public function index(int $teamId): JsonResponse
    {
        /** @var \App\Models\Team $team */
        $team = Team::with(['players' => function ($q) {
            $q->orderBy('full_name');
        }])->findOrFail($teamId);

        $players = $team->players->map(function ($p) {
            return [
                'id'           => $p->id,
                'name'         => $p->full_name,
                'position'     => $p->position,
                'shirt_number' => $p->pivot->shirt_number,
            ];
        })->values();

        return response()->json($players);
    }
}
