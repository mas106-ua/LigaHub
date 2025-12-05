<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Season;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    /**
     * GET /api/seasons
     *
     * Devuelve un listado de temporadas.
     * - Ordenadas por fecha de inicio descendente.
     * - Permite filtrar por code (?code=2024/25).
     * - Permite filtrar por temporada “actual” (?current=1).
     * 
     * Respuesta: array JSON simple, no paginado.
     */
    public function index(Request $request)
    {
        $query = Season::query();

        // Filtro por código de temporada, ej: ?code=2024/25
        if ($code = $request->query('code')) {
            $query->where('code', $code);
        }

        // Filtro por temporada actual, ej: ?current=1
        if ($request->boolean('current')) {
            $today = now()->toDateString();

            // Ajusta si tu Season tiene otros campos, pero lo normal:
            $query->where('start_date', '<=', $today)
                  ->where('end_date', '>=', $today);
        }

        // Orden por fecha de inicio descendente (temporadas más nuevas primero)
        $seasons = $query
            ->orderByDesc('start_date')
            ->get([
                'id',
                'code',
                'start_date',
                'end_date',
            ]);

        // Muy importante: devolvemos un array simple,
        // porque en el front haces (r.data || []).map(...)
        return response()->json($seasons);
    }
}
