<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrivateMatchResultsRequest;
use App\Models\League;
use App\Models\MatchModel;
use App\Services\StandingsSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivateMatchResultController extends Controller
{
    public function __construct(private readonly StandingsSnapshotService $standings)
    {
    }

    /**
     * PUT /api/private/leagues/{league}/matchdays/{number}/results
     */
    public function bulkUpdate(PrivateMatchResultsRequest $request, League $league, int $number): JsonResponse
    {
        $this->ensurePrivateLeague($league);
        $this->ensureMemberOrOwner($request, $league);

        $user = $request->user();

        // Solo admin/owner
        if (! $user || ! $user->canManageLeague($league)) {
            return response()->json([
                'message' => 'No tienes permisos para gestionar esta liga.',
            ], 403);
        }

        $data = $request->validated();
        $rows = $data['matches'];

        $ids = collect($rows)->pluck('id')->unique()->values();

        $matches = MatchModel::query()
            ->where('league_id', $league->id)
            ->where('matchday_number', $number)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        if ($matches->count() !== $ids->count()) {
            return response()->json([
                'message' => 'Algún partido no pertenece a la liga o jornada indicadas.',
            ], 422);
        }

        // Validaciones de coherencia extra
        $errors = [];
        foreach ($rows as $row) {
            $status = $row['status'] ?? null;
            $home   = $row['home_goals'] ?? null;
            $away   = $row['away_goals'] ?? null;

            if ($status === 'played' && ($home === null || $away === null)) {
                $errors[] = sprintf(
                    "El partido %d está 'played' pero falta indicar ambos goles.",
                    $row['id']
                );
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Hay errores en algunos partidos.',
                'errors'  => $errors,
            ], 422);
        }

        DB::transaction(function () use ($rows, $matches, $user) {
            foreach ($rows as $row) {
                /** @var MatchModel $match */
                $match = $matches[$row['id']];

                // Si no llega goles y status no es played, dejamos lo que hubiera
                if (array_key_exists('home_goals', $row)) $match->home_goals = $row['home_goals'] ?? $match->home_goals;
                if (array_key_exists('away_goals', $row)) $match->away_goals = $row['away_goals'] ?? $match->away_goals;

                $match->status = $row['status'];
                $match->updated_by = $user->id;

                $match->save();
            }
        });

        // Disparar recálculo (snapshot standings para esa jornada)
        $this->standings->generateForMatchday($league, $number);

        return response()->json([
            'message' => 'Resultados actualizados correctamente.',
            'updated' => count($rows),
        ]);
    }

    private function ensurePrivateLeague(League $league): void
    {
        if ($league->type !== 'private') {
            abort(404);
        }
    }

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
