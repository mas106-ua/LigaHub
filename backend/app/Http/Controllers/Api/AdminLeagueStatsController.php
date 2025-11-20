<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeagueStatsOverrideRequest;
use App\Models\League;
use App\Models\LeaguePlayerStatOverride;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminLeagueStatsController extends Controller
{
    /**
     * GET /api/admin/leagues/{league}/stats/overrides
     * Devuelve los overrides existentes para esa liga.
     */
    public function index(League $league): JsonResponse
    {
        $user = request()->user();
        if (! $user || ! $user->canManageLeague($league)) {
            return response()->json([
                'message' => 'No tienes permisos para gestionar esta liga.',
            ], 403);
        }

        $rows = LeaguePlayerStatOverride::query()
            ->with(['player:id,full_name'])
            ->where('league_id', $league->id)
            ->orderBy('player_id')
            ->get()
            ->map(function (LeaguePlayerStatOverride $ov) {
                return [
                    'player_id'          => $ov->player_id,
                    'player_name'        => $ov->player?->full_name,
                    'goals_delta'        => (int) $ov->goals_delta,
                    'assists_delta'      => (int) $ov->assists_delta,
                    'yellow_cards_delta' => (int) $ov->yellow_cards_delta,
                    'red_cards_delta'    => (int) $ov->red_cards_delta,
                    'reason'             => $ov->reason,
                ];
            })
            ->values();

        return response()->json([
            'data' => $rows,
        ]);
    }

    /**
     * PUT /api/admin/leagues/{league}/stats/overrides
     * Sincroniza overrides para la liga (estilo "sync"):
     * - Borra los overrides existentes
     * - Inserta los que vengan en "items" con algún delta no nulo
     */
    public function sync(LeagueStatsOverrideRequest $request, League $league): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->canManageLeague($league)) {
            return response()->json([
                'message' => 'No tienes permisos para gestionar esta liga.',
            ], 403);
        }

        $data  = $request->validated();
        $items = $data['items'] ?? [];

        DB::transaction(function () use ($league, $items, $user) {
            LeaguePlayerStatOverride::query()
                ->where('league_id', $league->id)
                ->delete();

            foreach ($items as $item) {
                $g  = (int) ($item['goals_delta'] ?? 0);
                $a  = (int) ($item['assists_delta'] ?? 0);
                $yc = (int) ($item['yellow_cards_delta'] ?? 0);
                $rc = (int) ($item['red_cards_delta'] ?? 0);

                if ($g === 0 && $a === 0 && $yc === 0 && $rc === 0) {
                    continue; // no almacenamos filas vacías
                }

                LeaguePlayerStatOverride::create([
                    'league_id'          => $league->id,
                    'player_id'          => $item['player_id'],
                    'goals_delta'        => $g,
                    'assists_delta'      => $a,
                    'yellow_cards_delta' => $yc,
                    'red_cards_delta'    => $rc,
                    'reason'             => $item['reason'] ?? null,
                    'created_by'         => $user->id,
                ]);
            }
        });

        return response()->json([
            'message' => 'Overrides de estadísticas guardados correctamente.',
        ]);
    }
}
