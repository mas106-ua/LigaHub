<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\League;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MyLeaguesController extends Controller
{
    /**
     * GET /api/my/leagues
     *
     * Devuelve las ligas donde el usuario autenticado es miembro,
     * incluyendo rol, metadatos básicos y filtros opcionales.
     *
     * Filtros soportados:
     * - type: private|official
     * - search: texto en nombre de liga
     * - role: owner|admin|member|viewer
     * - season: código de temporada, ej. 2025/26
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'type' => ['nullable', Rule::in(['private', 'official'])],
            'search' => ['nullable', 'string', 'max:150'],
            'role' => ['nullable', Rule::in(['owner', 'admin', 'member', 'viewer'])],
            'season' => ['nullable', 'string', 'max:20'],
        ]);

        $type = $validated['type'] ?? 'private';
        $search = trim((string) ($validated['search'] ?? ''));
        $role = $validated['role'] ?? null;
        $season = trim((string) ($validated['season'] ?? ''));

        $rows = League::query()
            ->select([
                'leagues.*',
                'league_memberships.role_in_league as role_in_league',
                'league_memberships.joined_at as membership_joined_at',
            ])
            ->join('league_memberships', 'league_memberships.league_id', '=', 'leagues.id')
            ->where('league_memberships.user_id', $user->id)
            ->where('leagues.type', $type)
            ->when($search !== '', function ($query) use ($search) {
                $query->where('leagues.name', 'like', "%{$search}%");
            })
            ->when($role, function ($query) use ($role) {
                $query->where('league_memberships.role_in_league', $role);
            })
            ->when($season !== '', function ($query) use ($season) {
                $query->whereHas('season', function ($seasonQuery) use ($season) {
                    $seasonQuery->where('code', $season);
                });
            })
            ->with([
                'season:id,code,start_date,end_date',
                'category:id,name,level,gender',
                'region:id,code,name',
            ])
            ->withCount([
                'teams as teams_count',
                'matches as matches_count',
            ])
            ->orderByRaw("
                CASE league_memberships.role_in_league
                    WHEN 'owner' THEN 1
                    WHEN 'admin' THEN 2
                    WHEN 'member' THEN 3
                    WHEN 'viewer' THEN 4
                    ELSE 5
                END
            ")
            ->orderByDesc('league_memberships.joined_at')
            ->orderByDesc('leagues.id')
            ->get();

        $data = $rows->map(function (League $league) {
            $role = (string) $league->role_in_league;

            return [
                'id' => $league->id,
                'name' => $league->name,
                'type' => $league->type,
                'visibility' => $league->visibility,
                'is_active' => (bool) $league->is_active,
                'owner_user_id' => $league->owner_user_id,

                'role_in_league' => $role,
                'role_label' => $this->roleLabel($role),
                'can_manage' => in_array($role, ['owner', 'admin'], true),
                'joined_at' => $league->membership_joined_at,

                'season' => $league->season ? [
                    'id' => $league->season->id,
                    'code' => $league->season->code,
                    'start_date' => $league->season->start_date,
                    'end_date' => $league->season->end_date,
                ] : null,

                'category' => $league->category ? [
                    'id' => $league->category->id,
                    'name' => $league->category->name,
                    'level' => $league->category->level,
                    'gender' => $league->category->gender,
                ] : null,

                'region' => $league->region ? [
                    'id' => $league->region->id,
                    'code' => $league->region->code,
                    'name' => $league->region->name,
                ] : null,

                'stats' => [
                    'teams_count' => (int) ($league->teams_count ?? 0),
                    'matches_count' => (int) ($league->matches_count ?? 0),
                ],

                'links' => [
                    'detail' => "/mis-ligas/{$league->id}",
                ],
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $data->count(),
                'filters' => [
                    'type' => $type,
                    'search' => $search,
                    'role' => $role,
                    'season' => $season,
                ],
            ],
        ]);
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'owner' => 'Propietario',
            'admin' => 'Administrador',
            'member' => 'Miembro',
            'viewer' => 'Solo lectura',
            default => $role,
        };
    }
}