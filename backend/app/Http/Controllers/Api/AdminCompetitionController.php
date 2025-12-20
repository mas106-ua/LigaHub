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
use Illuminate\Support\Facades\DB;

class AdminCompetitionController extends Controller
{
    /**
     * GET /api/admin/competitions
     * Lista competiciones oficiales para el panel admin (con filtros básicos).
     */
    public function index(AdminCompetitionIndexRequest $request): JsonResponse
    {
        $user = $request->user();

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

        // Aplicar permisos si NO es superadmin
        if (($user?->role ?? null) !== 'superadmin') {
            // 1) ids de competitions donde el usuario es admin/owner de alguna league
            $managedCompetitionIds = DB::table('leagues as l')
                ->whereNotNull('l.competition_id')
                ->where(function ($w) use ($user) {
                    $w->where('l.owner_user_id', $user->id)
                      ->orWhereExists(function ($sq) use ($user) {
                          $sq->select(DB::raw(1))
                              ->from('league_memberships as lm')
                              ->whereColumn('lm.league_id', 'l.id')
                              ->where('lm.user_id', $user->id)
                              ->whereIn('lm.role_in_league', ['owner', 'admin']);
                      });
                })
                ->select('l.competition_id');

            // 2) scopes del usuario (level/region)
            $scopes = DB::table('competition_admin_scopes')
                ->where('user_id', $user->id)
                ->get(['level', 'region_id']);

            $hasGlobalScope = $scopes->contains(fn ($s) => $s->level === null && $s->region_id === null);

            // Si no tiene scope global, restringe a (managed OR scopes)
            if (!$hasGlobalScope) {
                $q->where(function ($outer) use ($managedCompetitionIds, $scopes) {
                    // a) competiciones por membership/owner
                    $outer->whereIn('competitions.id', $managedCompetitionIds);

                    // b) competiciones por scope
                    if ($scopes->isNotEmpty()) {
                        $outer->orWhere(function ($or) use ($scopes) {
                            foreach ($scopes as $s) {
                                $or->orWhere(function ($w) use ($s) {
                                    if ($s->level !== null) {
                                        $w->where('competitions.level', $s->level);
                                    }
                                    if ($s->region_id !== null) {
                                        $w->where('competitions.region_id', $s->region_id);
                                    }
                                });
                            }
                        });
                    }
                });
            }
        }

        // ===== Filtros (los tuyos)
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
     *
     * Recomendado: proteger esta ruta con middleware competition_scope
     * (o policy) para comprobar canManageCompetition.
     */
    public function leagues(Competition $competition): JsonResponse
    {
        // Solo competiciones oficiales
        if ($competition->type !== 'official') {
            abort(404);
        }

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
