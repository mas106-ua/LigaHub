<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class PrivateMatchReportController extends Controller
{
    public function generate(Request $request, MatchModel $match)
    {
        // Liga privada
        $league = DB::table('leagues')->where('id', $match->league_id)->first();
        if (! $league || $league->type !== 'private') {
            abort(404);
        }

        $user = $request->user();

        // Permisos: owner o admin
        $isOwner = (int) $league->owner_user_id === (int) $user->id;

        $isAdmin = DB::table('league_memberships')
            ->where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();

        if (! $isOwner && ! $isAdmin) {
            abort(403);
        }

        // Solo si el partido está jugado
        if ($match->status !== 'played') {
            return response()->json([
                'message' => 'El acta solo puede generarse para partidos jugados.',
            ], 422);
        }

        // Equipos
        $homeTeam = DB::table('teams')->where('id', $match->home_team_id)->first();
        $awayTeam = DB::table('teams')->where('id', $match->away_team_id)->first();

        if (! $homeTeam || ! $awayTeam) {
            abort(404);
        }

        // Generar PDF
        $pdf = Pdf::loadView('pdf.private_match_report', [
            'league' => $league,
            'match' => $match,
            'homeTeam' => $homeTeam,
            'awayTeam' => $awayTeam,
        ])->setPaper('a4');

        // Guardar
        $path = "private/actas/{$league->id}";
        $filename = "match_{$match->id}.pdf";

        Storage::disk('local')->makeDirectory($path);
        Storage::disk('local')->put("{$path}/{$filename}", $pdf->output());

        return response()->json([
            'message' => 'Acta generada correctamente.',
            'path' => "{$path}/{$filename}",
        ]);
    }
}
