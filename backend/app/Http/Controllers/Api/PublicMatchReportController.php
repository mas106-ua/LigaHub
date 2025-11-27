<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\MatchReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class PublicMatchReportController extends Controller
{
    /**
     * GET /api/matches/{match}/report
     *
     * Devuelve info del acta (o 404 si no existe).
     */
    public function show(MatchModel $match): JsonResponse
    {
        $report = MatchReport::where('match_id', $match->id)->first();

        // Si no hay acta o no hay ruta de archivo, devolvemos 404 controlado
        if (! $report || ! $report->file_path) {
            return response()->json([
                'data'    => null,
                'message' => 'Acta no encontrada para este partido.',
            ], 404);
        }

        // URL pública usando el disco "public"
        $url = Storage::disk('public')->url($report->file_path);

        return response()->json([
            'data' => [
                'exists'      => true,
                'url'         => $url,
                'updated_at'  => optional($report->generated_at)->toIso8601String(),
                // si quieres más campos, añade aquí:
                'match_id'    => $report->match_id,
                'file_path'   => $report->file_path,
                'checksum'    => $report->checksum,
            ],
        ]);
    }
}
