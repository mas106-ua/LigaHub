<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrivateLeagueTeamStoreRequest;
use App\Http\Requests\PrivateLeagueTeamUpdateRequest;
use App\Models\League;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivateLeagueTeamsController extends Controller
{
    private function ensurePrivateLeague(League $league): void
    {
        if ($league->type !== 'private') {
            abort(404);
        }
    }

    private function ensureMember(Request $request, League $league): void
    {
        $user = $request->user();

        $isOwner = $league->owner_user_id && ((int)$league->owner_user_id === (int)$user->id);

        $isMember = DB::table('league_memberships')
            ->where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isOwner && ! $isMember) {
            abort(404); // evitar enumeración
        }
    }

    private function ensureAdmin(Request $request, League $league): ?JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->canManageLeague($league)) {
            return response()->json([
                'message' => 'No tienes permisos para gestionar esta liga.',
            ], 403);
        }
        return null;
    }

    private function ensureTeamInLeague(League $league, Team $team): void
    {
        $exists = DB::table('league_teams')
            ->where('league_id', $league->id)
            ->where('team_id', $team->id)
            ->exists();

        if (! $exists) {
            abort(404);
        }
    }

    /**
     * GET /api/private/leagues/{league}/teams
     * (Miembros: lectura)
     */
    public function index(Request $request, League $league): JsonResponse
    {
        $this->ensurePrivateLeague($league);
        $this->ensureMember($request, $league);

        $teams = DB::table('league_teams as lt')
            ->join('teams as t', 't.id', '=', 'lt.team_id')
            ->where('lt.league_id', $league->id)
            ->orderBy('t.name')
            ->get([
                't.id as id',
                't.name as name',
                't.short_name as short_name',
                't.city as city',
                't.crest_url as crest_url',
                'lt.group_name as group_name',
            ]);

        return response()->json(['data' => $teams]);
    }

    /**
     * POST /api/private/leagues/{league}/teams
     * (Solo admin-liga)
     */
    public function store(PrivateLeagueTeamStoreRequest $request, League $league): JsonResponse
    {
        $this->ensurePrivateLeague($league);
        $this->ensureMember($request, $league);
        if ($resp = $this->ensureAdmin($request, $league)) return $resp;

        $data = $request->validated();

        // Nombre único por liga (case-insensitive)
        $exists = DB::table('league_teams as lt')
            ->join('teams as t', 't.id', '=', 'lt.team_id')
            ->where('lt.league_id', $league->id)
            ->whereRaw('LOWER(t.name) = LOWER(?)', [$data['name']])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Validación incorrecta.',
                'errors'  => ['name' => ['Ya existe un equipo con ese nombre en esta liga.']],
            ], 422);
        }

        $team = Team::create([
            'name'       => $data['name'],
            'short_name' => $data['short_name'] ?? null,
            'city'       => $data['city'] ?? null,
            'crest_url'  => $data['crest_url'] ?? null,
        ]);

        DB::table('league_teams')->insert([
            'league_id'   => $league->id,
            'team_id'     => $team->id,
            'group_name'  => $data['group_name'] ?? null,
        ]);

        return response()->json([
            'message' => 'Equipo creado.',
            'data' => [
                'id'         => $team->id,
                'name'       => $team->name,
                'short_name' => $team->short_name,
                'city'       => $team->city,
                'crest_url'  => $team->crest_url,
                'group_name' => $data['group_name'] ?? null,
            ],
        ], 201);
    }

    /**
     * PUT /api/private/leagues/{league}/teams/{team}
     * (Solo admin-liga)
     */
    public function update(PrivateLeagueTeamUpdateRequest $request, League $league, Team $team): JsonResponse
    {
        $this->ensurePrivateLeague($league);
        $this->ensureMember($request, $league);
        if ($resp = $this->ensureAdmin($request, $league)) return $resp;

        $this->ensureTeamInLeague($league, $team);

        $data = $request->validated();

        // Si cambian el nombre: validar único por liga (excluyendo este team)
        if (isset($data['name'])) {
            $exists = DB::table('league_teams as lt')
                ->join('teams as t', 't.id', '=', 'lt.team_id')
                ->where('lt.league_id', $league->id)
                ->where('t.id', '<>', $team->id)
                ->whereRaw('LOWER(t.name) = LOWER(?)', [$data['name']])
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Validación incorrecta.',
                    'errors'  => ['name' => ['Ya existe un equipo con ese nombre en esta liga.']],
                ], 422);
            }
        }

        // Update team fields (si vienen)
        $team->fill([
            'name'       => $data['name'] ?? $team->name,
            'short_name' => array_key_exists('short_name', $data) ? $data['short_name'] : $team->short_name,
            'city'       => array_key_exists('city', $data) ? $data['city'] : $team->city,
            'crest_url'  => array_key_exists('crest_url', $data) ? $data['crest_url'] : $team->crest_url,
        ])->save();

        // Update pivot group_name si viene
        if (array_key_exists('group_name', $data)) {
            DB::table('league_teams')
                ->where('league_id', $league->id)
                ->where('team_id', $team->id)
                ->update(['group_name' => $data['group_name']]);
        }

        $groupName = DB::table('league_teams')
            ->where('league_id', $league->id)
            ->where('team_id', $team->id)
            ->value('group_name');

        return response()->json([
            'message' => 'Equipo actualizado.',
            'data' => [
                'id'         => $team->id,
                'name'       => $team->name,
                'short_name' => $team->short_name,
                'city'       => $team->city,
                'crest_url'  => $team->crest_url,
                'group_name' => $groupName,
            ],
        ]);
    }

    /**
     * DELETE /api/private/leagues/{league}/teams/{team}
     * (Solo admin-liga) -> elimina de la liga (detach). Borra el team si queda huérfano y sin partidos.
     */
    public function destroy(Request $request, League $league, Team $team): JsonResponse
    {
        $this->ensurePrivateLeague($league);
        $this->ensureMember($request, $league);
        if ($resp = $this->ensureAdmin($request, $league)) return $resp;

        $this->ensureTeamInLeague($league, $team);

        // Si ya hay partidos en ESTA liga con ese equipo, no dejar borrar
        $hasMatches = DB::table('matches')
            ->where('league_id', $league->id)
            ->where(function ($q) use ($team) {
                $q->where('home_team_id', $team->id)
                  ->orWhere('away_team_id', $team->id);
            })
            ->exists();

        if ($hasMatches) {
            return response()->json([
                'message' => 'No se puede eliminar un equipo con partidos asociados en esta liga.',
            ], 409);
        }

        // Detach (eliminar relación)
        DB::table('league_teams')
            ->where('league_id', $league->id)
            ->where('team_id', $team->id)
            ->delete();

        // Si el equipo ya no está en ninguna liga y no está referenciado por partidos, borrarlo
        $stillInAnyLeague = DB::table('league_teams')->where('team_id', $team->id)->exists();
        $inAnyMatch = DB::table('matches')
            ->where(function ($q) use ($team) {
                $q->where('home_team_id', $team->id)
                  ->orWhere('away_team_id', $team->id);
            })
            ->exists();

        if (! $stillInAnyLeague && ! $inAnyMatch) {
            $team->delete();
        }

        return response()->json([
            'message' => 'Equipo eliminado de la liga.',
        ]);
    }
}
