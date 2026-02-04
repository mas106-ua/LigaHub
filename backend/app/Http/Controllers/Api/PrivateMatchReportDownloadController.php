<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PrivateMatchReportDownloadController extends Controller
{
    public function download(Request $request, MatchModel $match)
    {
        // Liga privada
        $league = DB::table('leagues')->where('id', $match->league_id)->first();
        if (! $league || $league->type !== 'private') {
            abort(404);
        }

        $user = $request->user();

        // Permisos: cualquier miembro u owner
        $isOwner = (int) $league->owner_user_id === (int) $user->id;

        $isMember = DB::table('league_memberships')
            ->where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isOwner && ! $isMember) {
            abort(404);
        }

        // Ruta del PDF
        $path = "private/actas/{$league->id}/match_{$match->id}.pdf";

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        // Descargar (stream)
        return Storage::disk('local')->download(
            $path,
            "acta_partido_{$match->id}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}
