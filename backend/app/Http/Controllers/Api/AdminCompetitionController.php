<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCompetitionIndexRequest;
use App\Http\Resources\AdminCompetitionLeagueResource;
use App\Http\Resources\AdminCompetitionResource;
use App\Models\Competition;
use App\Models\League;
use App\Models\Province;
use Illuminate\Http\JsonResponse;

class AdminCompetitionController extends Controller
{
    /**
     * GET /api/admin/competitions
     * Lista competiciones oficiales para el panel admin (con filtros básicos).
     */
    public function index(AdminCompetitionIndexRequest $request): JsonResponse
    {
        $search   = (string) $request->query('search', '');
        $level    = (string) $request->query('level', '');
        $gender   = (string) $request->query('gender', '');
        $category = (string) $request->query('category', '');
        $region   = (string) $request->query('region', '');
        $province = (string) $request->query('province', '');
        $perPage  = (int) $request->query('per_page', 12);

        $q = Competition::query()
            ->official()
            ->with([
                'category:id,name,level,gender',
                'region:id,name,code',
                'province:id,name,code,region_id',
            ])
            ->withCount([
                'leagues as leagues_count' => fn ($qq) => $qq->official(),
            ]);

        if ($search !== '') {
            $like = '%' . str_replace('%', '\%', $search) . '%';
            $q->where(function ($w) use ($like) {
                $w->where('competitions.name', 'like', $like)
                  ->orWhere('competitions.code', 'like', $like);
            });
        }

        if ($level !== '') {
            $q->where('competitions.level', $level);
        }

        if ($gender !== '') {
            $q->where('competitions.gender', $gender);
        }

        if ($category !== '') {
            $q->whereHas('category', function ($c) use ($category) {
                $c->where('name', 'like', $category . '%');
            });
        }

        if ($region !== '') {
            $q->whereHas('region', function ($r) use ($region) {
                $r->where('code', $region);
            });
        }

        if ($province !== '') {
            $prov = Province::query()->where('code', $province)->first();
            if ($prov) {
                $q->where('competitions.province_id', $prov->id);
            } else {
                // si viene un code inválido, devolvemos vacío (sin 422 para no romper FE)
                $q->whereRaw('1=0');
            }
        }

        $q->orderBy('competitions.name');

        $paginator = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => AdminCompetitionResource::collection($paginator)->resolve(),
            'meta' => [
                'page'     => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total'    => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /api/admin/competitions/{competition}/leagues
     * Lista ediciones (leagues) de una Competition (temporadas/grupos).
     */
    public function leagues(Competition $competition): JsonResponse
    {
        // Solo competiciones oficiales
        if ($competition->type !== 'official') {
            abort(404);
        }

        // Join a seasons para ordenar + devolver season_code sin N+1
        $rows = League::query()
            ->where('competition_id', $competition->id)
            ->official()
            ->leftJoin('seasons', 'seasons.id', '=', 'leagues.season_id')
            ->with(['season:id,code,start_date'])
            ->withCount([
                'matches as matches_total_count',
                'matches as matches_played_count' => fn ($m) => $m->where('status', 'played'),
                'teams as teams_count',
            ])
            ->orderByDesc('seasons.start_date')
            ->orderBy('leagues.name')
            ->get([
                'leagues.*',
                'seasons.code as season_code',
                'seasons.start_date as season_start_date',
            ]);

        return response()->json([
            'competition' => [
                'id'   => (int) $competition->id,
                'name' => $competition->name,
                'code' => $competition->code,
            ],
            'data' => AdminCompetitionLeagueResource::collection($rows)->resolve(),
        ]);
    }
}
