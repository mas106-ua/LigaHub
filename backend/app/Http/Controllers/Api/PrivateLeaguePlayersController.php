<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrivateLeaguePlayerStoreRequest;
use App\Http\Requests\PrivateLeaguePlayerUpdateRequest;
use App\Models\League;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivateLeaguePlayersController extends Controller
{
    private function ensurePrivateLeague(League $league): void
    {
        if ($league->type !== 'private') abort(404);
    }

    private function ensureMember(Request $request, League $league): void
    {
        $user = $request->user();

        $isOwner = $league->owner_user_id && ((int)$league->owner_user_id === (int)$user->id);

        $isMember = DB::table('league_memberships')
            ->where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isOwner && ! $isMember) abort(404); // anti-enumeración
    }

    private function ensureAdmin(Request $request, League $league): ?JsonResponse
    {
        if (! $request->user()->canManageLeague($league)) {
            return response()->json(['message' => 'No tienes permisos para gestionar esta liga.'], 403);
        }
        return null;
    }

    private function ensureTeamInLeague(League $league, int $teamId): void
    {
        $ok = DB::table('league_teams')
            ->where('league_id', $league->id)
            ->where('team_id', $teamId)
            ->exists();

        if (! $ok) {
            abort(422, 'team_id no pertenece a esta liga');
        }
    }

    private function ensurePlayerInLeague(League $league, Player $player): void
    {
        $ok = DB::table('team_players as tp')
            ->join('league_teams as lt', 'lt.team_id', '=', 'tp.team_id')
            ->where('lt.league_id', $league->id)
            ->where('tp.player_id', $player->id)
            ->exists();

        if (! $ok) abort(404);
    }

    /**
     * GET /api/private/leagues/{league}/players?team_id=
     * Lectura para miembros.
     */
    public function index(Request $request, League $league): JsonResponse
    {
        $this->ensurePrivateLeague($league);
        $this->ensureMember($request, $league);

        $teamId = $request->query('team_id');
        if ($teamId !== null) {
            $this->ensureTeamInLeague($league, (int)$teamId);
        }

        $q = DB::table('players as p')
            ->join('team_players as tp', 'tp.player_id', '=', 'p.id')
            ->join('league_teams as lt', 'lt.team_id', '=', 'tp.team_id')
            ->join('teams as t', 't.id', '=', 'tp.team_id')
            ->where('lt.league_id', $league->id)
            ->select([
                'p.id',
                'p.full_name',
                'p.position',
                'p.date_of_birth',
                'p.doc_number',
                't.id as team_id',
                't.name as team_name',
                'tp.shirt_number',
            ])
            ->orderBy('t.name')
            ->orderBy('p.full_name');

        if ($teamId !== null) {
            $q->where('t.id', (int)$teamId);
        }

        return response()->json(['data' => $q->get()]);
    }

    /**
     * POST /api/private/leagues/{league}/players
     * Solo admin-liga. Crea player y lo asigna a un team de la liga.
     */
    public function store(PrivateLeaguePlayerStoreRequest $request, League $league): JsonResponse
    {
        $this->ensurePrivateLeague($league);
        $this->ensureMember($request, $league);
        if ($resp = $this->ensureAdmin($request, $league)) return $resp;

        $data = $request->validated();
        $this->ensureTeamInLeague($league, (int)$data['team_id']);

        // Duplicado mínimo: mismo full_name (case-insensitive) dentro de la liga
        $dup = DB::table('players as p')
            ->join('team_players as tp', 'tp.player_id', '=', 'p.id')
            ->join('league_teams as lt', 'lt.team_id', '=', 'tp.team_id')
            ->where('lt.league_id', $league->id)
            ->whereRaw('LOWER(p.full_name) = LOWER(?)', [$data['full_name']])
            ->exists();

        if ($dup) {
            return response()->json([
                'message' => 'Validación incorrecta.',
                'errors'  => ['full_name' => ['Ya existe un jugador con ese nombre en esta liga.']],
            ], 422);
        }

        $player = DB::transaction(function () use ($data) {
            $player = Player::create([
                'full_name'     => $data['full_name'],
                'position'      => $data['position'] ?? 'NA',
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'doc_number'    => $data['doc_number'] ?? null,
            ]);

            DB::table('team_players')->insert([
                'team_id'      => (int) $data['team_id'],
                'player_id'    => $player->id,
                'shirt_number' => $data['shirt_number'] ?? null,
                'from_date'    => null,
                'to_date'      => null,
            ]);

            return $player;
        });

        return response()->json([
            'message' => 'Jugador creado.',
            'data' => [
                'id'           => $player->id,
                'full_name'    => $player->full_name,
                'position'     => $player->position,
                'date_of_birth'=> $player->date_of_birth,
                'doc_number'   => $player->doc_number,
                'team_id'      => (int) $data['team_id'],
                'shirt_number' => $data['shirt_number'] ?? null,
            ],
        ], 201);
    }

    /**
     * PUT /api/private/leagues/{league}/players/{player}
     * Solo admin-liga. Edita y/o reasigna a otro team.
     */
    public function update(PrivateLeaguePlayerUpdateRequest $request, League $league, Player $player): JsonResponse
    {
        $this->ensurePrivateLeague($league);
        $this->ensureMember($request, $league);
        if ($resp = $this->ensureAdmin($request, $league)) return $resp;

        $this->ensurePlayerInLeague($league, $player);

        $data = $request->validated();

        if (isset($data['full_name'])) {
            $dup = DB::table('players as p')
                ->join('team_players as tp', 'tp.player_id', '=', 'p.id')
                ->join('league_teams as lt', 'lt.team_id', '=', 'tp.team_id')
                ->where('lt.league_id', $league->id)
                ->where('p.id', '<>', $player->id)
                ->whereRaw('LOWER(p.full_name) = LOWER(?)', [$data['full_name']])
                ->exists();

            if ($dup) {
                return response()->json([
                    'message' => 'Validación incorrecta.',
                    'errors'  => ['full_name' => ['Ya existe un jugador con ese nombre en esta liga.']],
                ], 422);
            }
        }

        DB::transaction(function () use ($league, $player, $data) {
            // 1) Update player fields
            $player->fill([
                'full_name'     => $data['full_name'] ?? $player->full_name,
                'position'      => array_key_exists('position', $data) ? ($data['position'] ?? 'NA') : $player->position,
                'date_of_birth' => array_key_exists('date_of_birth', $data) ? $data['date_of_birth'] : $player->date_of_birth,
                'doc_number'    => array_key_exists('doc_number', $data) ? $data['doc_number'] : $player->doc_number,
            ])->save();

            // 2) Teams de la liga donde está este player (para forzar 1 equipo por liga)
            $leagueTeamIds = DB::table('league_teams')
                ->where('league_id', $league->id)
                ->pluck('team_id')
                ->all();

            // Reasignación (team_id)
            if (array_key_exists('team_id', $data)) {
                if ($data['team_id'] !== null) {
                    $this->ensureTeamInLeague($league, (int)$data['team_id']);
                }

                // Elimina asignaciones actuales dentro de esta liga
                DB::table('team_players')
                    ->where('player_id', $player->id)
                    ->whereIn('team_id', $leagueTeamIds)
                    ->delete();

                // Si team_id viene null => queda sin equipo en la liga (permitido)
                if ($data['team_id'] !== null) {
                    DB::table('team_players')->insert([
                        'team_id'      => (int) $data['team_id'],
                        'player_id'    => $player->id,
                        'shirt_number' => $data['shirt_number'] ?? null,
                        'from_date'    => null,
                        'to_date'      => null,
                    ]);
                }
            }

            // Actualizar dorsal si se envía y el jugador sigue asignado a algún equipo en la liga
            if (array_key_exists('shirt_number', $data) && !array_key_exists('team_id', $data)) {
                $currentTeamId = DB::table('team_players as tp')
                    ->join('league_teams as lt', 'lt.team_id', '=', 'tp.team_id')
                    ->where('lt.league_id', $league->id)
                    ->where('tp.player_id', $player->id)
                    ->value('tp.team_id');

                if ($currentTeamId) {
                    DB::table('team_players')
                        ->where('team_id', $currentTeamId)
                        ->where('player_id', $player->id)
                        ->update(['shirt_number' => $data['shirt_number']]);
                }
            }
        });

        // Respuesta con team actual (si tiene)
        $current = DB::table('team_players as tp')
            ->join('league_teams as lt', 'lt.team_id', '=', 'tp.team_id')
            ->where('lt.league_id', $league->id)
            ->where('tp.player_id', $player->id)
            ->select(['tp.team_id', 'tp.shirt_number'])
            ->first();

        return response()->json([
            'message' => 'Jugador actualizado.',
            'data' => [
                'id'            => $player->id,
                'full_name'     => $player->full_name,
                'position'      => $player->position,
                'date_of_birth' => $player->date_of_birth,
                'doc_number'    => $player->doc_number,
                'team_id'       => $current?->team_id,
                'shirt_number'  => $current?->shirt_number,
            ],
        ]);
    }

    /**
     * DELETE /api/private/leagues/{league}/players/{player}
     * Solo admin-liga. Lo “elimina” de la liga (detach). Borra player si queda huérfano.
     */
    public function destroy(Request $request, League $league, Player $player): JsonResponse
    {
        $this->ensurePrivateLeague($league);
        $this->ensureMember($request, $league);
        if ($resp = $this->ensureAdmin($request, $league)) return $resp;

        $this->ensurePlayerInLeague($league, $player);

        DB::transaction(function () use ($league, $player) {
            $leagueTeamIds = DB::table('league_teams')
                ->where('league_id', $league->id)
                ->pluck('team_id')
                ->all();

            // Quitar del/los equipos de ESTA liga
            DB::table('team_players')
                ->where('player_id', $player->id)
                ->whereIn('team_id', $leagueTeamIds)
                ->delete();

            // Si ya no pertenece a ningún equipo, y no está referenciado por eventos, lo borramos
            $stillAssigned = DB::table('team_players')->where('player_id', $player->id)->exists();

            $hasEvents = DB::table('match_events')
                ->where('player_id', $player->id)
                ->orWhere('related_player_id', $player->id)
                ->exists();

            if (! $stillAssigned && ! $hasEvents) {
                $player->delete();
            }
        });

        return response()->json(['message' => 'Jugador eliminado de la liga.']);
    }
}
