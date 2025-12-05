<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompetitionIndexRequest;
use App\Http\Resources\CompetitionResource;
use App\Models\League;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB; 

class CompetitionController extends Controller
{
    public function index(CompetitionIndexRequest $request): JsonResponse
    {
        $region   = (string) $request->query('region', '');
        $season   = (string) $request->query('season', '');
        $search   = (string) $request->query('search', '');
        $perPage  = (int) $request->query('per_page', 12);
        $gender   = (string) $request->query('gender', '');
        $level    = (string) $request->query('level', '');
        $province = (string) $request->query('province', '');

        // Base: ligas oficiales y públicas, enlazadas a una Competition
        $query = League::query()
            ->select('leagues.*')
            ->with([
                'competition.region:id,name,code',
                'competition.category:id,name,level,gender',
                'competition.province:id,name,code,region_id',
                'season:id,code,start_date',
            ])
            ->leftJoin('seasons', 'seasons.id', '=', 'leagues.season_id')
            ->official()
            ->public()
            ->whereNotNull('competition_id');

        // Filtro por CCAA usando competition.region
        if ($region !== '') {
            $query->whereHas('competition.region', function ($q) use ($region) {
                $q->where('code', $region);
            });
        }

        // Filtro por provincia:
        // - competiciones con esa provincia concreta
        // - o competiciones sin provincia pero de la misma región
        if ($province !== '') {
            $prov = Province::where('code', $province)->first();

            if ($prov) {
                $query->where(function ($q) use ($prov) {
                    $q->whereHas('competition', function ($cq) use ($prov) {
                        $cq->where('province_id', $prov->id);
                    })->orWhere(function ($q2) use ($prov) {
                        $q2->whereHas('competition', function ($cq2) use ($prov) {
                            $cq2->whereNull('province_id')
                                ->where('region_id', $prov->region_id);
                        });
                    });
                });
            }
        }

        // Filtro por temporada (Season)
        if ($season !== '') {
            $query->whereHas('season', function ($q) use ($season) {
                $q->where('code', $season);
            });
        }

        // Búsqueda por nombre de liga (edición concreta)
        if ($search !== '') {
            $like = '%' . str_replace('%', '\%', $search) . '%';
            $query->where('leagues.name', 'like', $like);
        }

        // Filtro por género y/o nivel, vía Competition->Category
        if ($gender !== '' || $level !== '') {
            $query->whereHas('competition.category', function ($q) use ($gender, $level) {
                if ($gender !== '') {
                    $q->where('gender', $gender);
                }
                if ($level !== '') {
                    $q->where('level', $level);
                }
            });
        }

        $query->orderByDesc('seasons.start_date')
            ->orderBy('leagues.name');

        $paginator = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => CompetitionResource::collection($paginator)->resolve(),
            'meta' => [
                'page'     => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total'    => $paginator->total(),
            ],
        ]);
    }

    // === NUEVO: resumen de liga para cabecera FE
    public function show(League $league): JsonResponse
    {
        // Carga relaciones básicas para la cabecera
        $league->load([
            'region:id,name,code',
            'season:id,code,start_date',
            'category:id,name,level,gender',
            'province:id,name,code,region_id',
        ]);

        $md = DB::table('matches')
            ->where('league_id', $league->id)
            ->selectRaw('MIN(matchday_number) AS min_md, MAX(matchday_number) AS max_md')
            ->selectRaw('MIN(scheduled_at) AS first_date, MAX(scheduled_at) AS last_date')
            ->first();

        $groups = DB::table('league_teams')
            ->where('league_id', $league->id)
            ->whereNotNull('group_name')
            ->distinct()
            ->orderBy('group_name')
            ->pluck('group_name');

        return response()->json([
            'data' => [
                // puedes devolver la liga envuelta en tu CompetitionResource si prefieres:
                // 'league' => (new CompetitionResource($league))->resolve(),
                'id'          => $league->id,
                'name'        => $league->name,
                'season'      => $league->season?->code,
                'category'    => [
                    'name'   => $league->category?->name,
                    'level'  => $league->category?->level,
                    'gender' => $league->category?->gender,
                ],
                'region'      => $league->region?->code,
                'province'    => $league->province?->code,
                'min_matchday'=> (int)($md->min_md ?? 1),
                'max_matchday'=> (int)($md->max_md ?? 0),
                'date_range'  => ['from' => $md->first_date, 'to' => $md->last_date],
                'groups'      => $groups,
            ]
        ]);
    }

    // === NUEVO: lista de grupos de una liga (para el selector FE)
    public function groups(League $league): JsonResponse
    {
        $groups = DB::table('league_teams')
            ->where('league_id', $league->id)
            ->whereNotNull('group_name')
            ->distinct()
            ->orderBy('group_name')
            ->pluck('group_name');

        return response()
            ->json(['data' => $groups])
            // cache baratito (2 minutos) porque cambia poco:
            ->header('Cache-Control', 'public, max-age=120');
    }

    public function siblings(League $league): JsonResponse
    {
        // Base: "Primera Federación" recortando " – Grupo X" al final
        $base = preg_replace('/\s*–\s*Grupo\s+\d+$/u', '', $league->name);

        $siblings = DB::table('leagues')
            ->where('season_id', $league->season_id)
            ->where('category_id', $league->category_id)
            ->where('name', 'like', $base.'%')
            ->orderBy('name')
            ->get(['id','name']);

        return response()->json(['data' => $siblings]);
    }
}
