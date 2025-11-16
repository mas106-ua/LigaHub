<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminMatchLineupRequest;
use App\Models\MatchLineup;
use App\Models\MatchModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminMatchLineupController extends Controller
{
    /**
     * Guarda/actualiza las alineaciones (home/away) de un partido.
     *
     * PUT /api/admin/matches/{match}/lineups
     */
    public function update(AdminMatchLineupRequest $request, int $match): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        /** @var \App\Models\MatchModel $matchModel */
        $matchModel = MatchModel::with('league')->findOrFail($match);

        // Permisos: solo admins de esta liga (superadmin / scopes / owner / league_admin)
        if (!$user || !$user->canManageLeague($matchModel->league)) {
            return response()->json([
                'message' => 'No tienes permisos para gestionar esta liga.',
            ], 403);
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $matchModel, $user) {
            foreach (['home', 'away'] as $side) {
                if (!isset($data[$side])) {
                    continue;
                }

                $payload = $data[$side];

                $teamId = $side === 'home'
                    ? $matchModel->home_team_id
                    : $matchModel->away_team_id;

                $starters = $payload['starters'] ?? [];
                $bench    = $payload['bench'] ?? [];

                // 1) Regla: exactamente 1 GK en los titulares
                $gkCount = collect($starters)->where('pos', 'GK')->count();
                if ($gkCount !== 1) {
                    throw ValidationException::withMessages([
                        "{$side}.starters" => [
                            'Debe haber exactamente 1 portero (GK) en el once titular.',
                        ],
                    ]);
                }

                // 2) Jugadores duplicados entre titulares y banquillo
                $all = collect(array_merge($starters, $bench));
                $allIds = $all
                    ->pluck('player_id')
                    ->filter()
                    ->map(fn($id) => (int) $id)
                    ->values()
                    ->all();

                $unique = array_values(array_unique($allIds));
                if (count($unique) !== count($allIds)) {
                    throw ValidationException::withMessages([
                        "{$side}.players" => [
                            'Hay jugadores duplicados entre titulares y banquillo.',
                        ],
                    ]);
                }

                // 3) Comprobar que pertenecen al equipo (team_players)
                if ($unique) {
                    $validIds = DB::table('team_players')
                        ->where('team_id', $teamId)
                        ->whereIn('player_id', $unique)
                        ->pluck('player_id')
                        ->map(fn($id) => (int) $id)
                        ->values()
                        ->all();

                    sort($validIds);
                    $missing = array_diff($unique, $validIds);

                    if (!empty($missing)) {
                        throw ValidationException::withMessages([
                            "{$side}.players" => [
                                'Hay jugadores que no pertenecen a este equipo.',
                            ],
                        ]);
                    }
                }

                // 4) Guardar alineación (idempotente: updateOrCreate por match+side)
                $normalize = fn(array $items) => array_map(function ($it) {
                    return [
                        'player_id' => isset($it['player_id']) ? (int) $it['player_id'] : null,
                        'shirt'     => isset($it['shirt']) ? (int) $it['shirt'] : null,
                        'pos'       => $it['pos'] ?? null,
                    ];
                }, $items);

                MatchLineup::updateOrCreate(
                    [
                        'match_id' => $matchModel->id,
                        'side'     => $side,
                    ],
                    [
                        'team_id'    => $teamId,
                        'formation'  => $payload['formation'] ?? null,
                        'coach_name' => $payload['coach_name'] ?? null,
                        'starters'   => $normalize($starters),
                        'bench'      => $normalize($bench),
                    ]
                );

                // Auditoría mínima en logs
                Log::info('Match lineup updated', [
                    'match_id' => $matchModel->id,
                    'side'     => $side,
                    'user_id'  => $user->id,
                ]);
            }
        });

        return response()->json([
            'message' => 'Alineaciones guardadas correctamente.',
        ]);
    }
}
