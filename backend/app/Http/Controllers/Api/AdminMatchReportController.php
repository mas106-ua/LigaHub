<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\MatchReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminMatchReportController extends Controller
{
    /**
     * Sube o reemplaza el acta PDF de un partido.
     *
     * POST /api/admin/matches/{match}/report
     */
    public function upload(Request $request, MatchModel $match): JsonResponse
    {
        $user = $request->user();

        // Permisos: ¿puede gestionar este partido?
        if (!$user || !$user->canManageMatch($match)) {
            return response()->json([
                'message' => 'No tienes permisos para gestionar este partido.',
            ], 403);
        }

        // Opcional: si el partido está verificado, solo superadmin puede tocar el acta
        if ($match->edit_status === 'verified' && $user->role !== 'superadmin') {
            return response()->json([
                'message' => 'El partido está verificado y no se puede modificar el acta.',
            ], 403);
        }

        // Validación del PDF (10MB máx)
        $data = $request->validate([
            'file' => ['required', 'file', 'mimetypes:application/pdf', 'max:10240'],
        ]);

        $file = $data['file'];

        $disk = 'public'; // usa el disco "public" (asegúrate de tener storage:link)
        $directory = 'match-reports/' . $match->id;

        // Limpia ficheros antiguos de este partido
        if (Storage::disk($disk)->exists($directory)) {
            Storage::disk($disk)->deleteDirectory($directory);
        }

        // Guarda el nuevo PDF
        $storedPath = $file->store($directory, $disk);

        // Checksum para auditoría
        $checksum = hash_file('sha256', $file->getRealPath());

        // Upsert del registro
        $report = MatchReport::updateOrCreate(
            ['match_id' => $match->id],
            [
                'file_path'    => $storedPath,
                'generated_at' => now(),
                'checksum'     => $checksum,
            ]
        );

        Log::info('Match report uploaded', [
            'match_id' => $match->id,
            'user_id'  => $user->id,
            'path'     => $storedPath,
        ]);

        $url = Storage::disk($disk)->url($storedPath);

        return response()->json([
            'message' => 'Acta PDF subida correctamente.',
            'data' => [
                'match_id'     => $match->id,
                'file_path'    => $report->file_path,
                'url'          => $url,
                'generated_at' => optional($report->generated_at)->toIso8601String(),
                'checksum'     => $report->checksum,
            ],
        ]);
    }
}
