<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminMatchResultsRequest;
use App\Models\League;
use App\Models\MatchModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminMatchResultController extends Controller
{
    /**
     * Actualiza resultados (goles + estado) de los partidos de una jornada.
     *
     * PUT /api/admin/leagues/{league}/matchdays/{number}/results
     */
    public function bulkUpdate(
        AdminMatchResultsRequest $request,
        League $league,
        int $number
    ): JsonResponse {
        $user = $request->user();

        // 1) Permisos: ¿puede este usuario gestionar esta liga?
        if (! $user || ! $user->canManageLeague($league)) {
            return response()->json([
                'message' => 'No tienes permisos para gestionar esta liga.',
            ], 403);
        }

        $data = $request->validated();
        $rows = $data['matches'];

        // IDs únicos de partidos recibidos
        $ids = collect($rows)->pluck('id')->unique()->values();

        // 2) Cargar partidos de esa liga + jornada
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

        if ($user->role !== 'superadmin') {
            foreach ($matches as $match) {
                if ($match->edit_status === 'verified') {
                    return response()->json([
                        'message' => "El partido {$match->id} está verificado y no se puede editar.",
                    ], 403);
                }
            }
        }

        // 3) Validaciones de coherencia extra (goles/estado)
        $errors = [];

        foreach ($rows as $row) {
            $status = $row['status'] ?? null;
            $home   = $row['home_goals'] ?? null;
            $away   = $row['away_goals'] ?? null;

            // Si está "played", ambos goles son obligatorios
            if ($status === 'played' && ($home === null || $away === null)) {
                $errors[] = sprintf(
                    "El partido %d está 'played' pero falta indicar ambos goles.",
                    $row['id']
                );
            }

            // (Ejemplo extra: si está canceled/postponed podrías forzar goles NULL, si quisieras)
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Hay errores en algunos partidos.',
                'errors'  => $errors,
            ], 422);
        }

        // 4) Guardar cambios en transacción
        DB::transaction(function () use ($rows, $matches) {
            foreach ($rows as $row) {
                /** @var MatchModel $match */
                $match = $matches[$row['id']];

                $match->home_goals = $row['home_goals'];
                $match->away_goals = $row['away_goals'];
                $match->status     = $row['status'];

                $match->save();
            }
        });

        return response()->json([
            'message' => 'Resultados actualizados correctamente.',
            'updated' => count($rows),
        ]);
    }
}
