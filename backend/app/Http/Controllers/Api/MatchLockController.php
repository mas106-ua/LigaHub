<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use Illuminate\Http\Request;

class MatchLockController extends Controller
{
    /**
     * Reabrir partido para edición (edit_status = open).
     *
     * - Si el partido está VERIFIED → solo superadmin puede reabrir.
     * - Si está OPEN o CLOSED → puede reabrir cualquier usuario que canManageMatch().
     */
    public function open(Request $request, MatchModel $match)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        if ($match->edit_status === 'verified') {
            // Solo superadmin puede reabrir un partido verificado
            if ($user->role !== 'superadmin') {
                return response()->json([
                    'message' => 'Solo un superadmin puede reabrir un partido verificado.',
                ], 403);
            }
        } else {
            // Si no está verificado (open/closed) → admin de la liga o superadmin
            if (!$user->canManageMatch($match)) {
                return response()->json([
                    'message' => 'No tienes permisos para reabrir este partido.',
                ], 403);
            }
        }

        $match->edit_status = 'open';
        $match->verified_at = null;
        $match->verified_by = null;
        $match->save();

        return response()->json([
            'message' => 'Partido reabierto para edición.',
            'data'    => $match,
        ]);
    }

    /**
     * Cerrar partido para edición (edit_status = closed).
     *
     * - Admin de la liga o superadmin (canManageMatch).
     * - No permite cerrar si ya está verified.
     * - Si el estado deportivo sigue en scheduled, lo pasa a played.
     * - Aquí más adelante podrás enganchar la recalculación de clasificación/estadísticas.
     */
    public function close(Request $request, MatchModel $match)
    {
        $user = $request->user();

        if (!$user || !$user->canManageMatch($match)) {
            return response()->json([
                'message' => 'No tienes permisos para cerrar este partido.',
            ], 403);
        }

        if ($match->edit_status === 'verified') {
            return response()->json([
                'message' => 'El partido ya está verificado y no se puede cerrar de nuevo.',
            ], 422);
        }

        if ($match->status === 'scheduled') {
            $match->status = 'played';
        }

        $match->edit_status = 'closed';
        $match->save();

        // TODO: aquí engancharás BE-04 (recalcular clasificación/estadísticas).
        // MatchService::recalculateForMatch($match);

        return response()->json([
            'message' => 'Partido cerrado correctamente.',
            'data'    => $match,
        ]);
    }

    /**
     * Verificar partido (edit_status = verified).
     *
     * - Admin de la liga o superadmin (canManageMatch).
     * - Pone status = played si aún está scheduled.
     * - Marca verified_at + verified_by y bloquea edición (salvo superadmin reabriendo).
     */
    public function verify(Request $request, MatchModel $match)
    {
        $user = $request->user();

        if (!$user || !$user->canManageMatch($match)) {
            return response()->json([
                'message' => 'No tienes permisos para verificar este partido.',
            ], 403);
        }

        if ($match->edit_status === 'verified') {
            return response()->json([
                'message' => 'El partido ya está verificado.',
            ], 422);
        }

        // Si estaba open, lo cerramos implícitamente
        if ($match->edit_status === 'open') {
            $match->edit_status = 'closed';
        }

        if ($match->status === 'scheduled') {
            $match->status = 'played';
        }

        $match->edit_status = 'verified';
        $match->verified_at = now();
        $match->verified_by = $user->id;
        $match->save();

        // TODO: aquí también puedes enganchar la recalculación de clasificación/estadísticas.
        // MatchService::recalculateForMatch($match);

        return response()->json([
            'message' => 'Partido verificado correctamente.',
            'data'    => $match,
        ]);
    }
}
