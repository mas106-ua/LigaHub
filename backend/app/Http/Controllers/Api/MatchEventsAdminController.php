<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\MatchEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MatchEventsAdminController extends Controller
{
    public function sync(Request $request, MatchModel $match)
    {
        $user = $request->user();

        if (!$user || !$user->canManageMatch($match)) {
            return response()->json([
                'message' => 'No tienes permisos para gestionar los eventos de este partido.',
            ], 403);
        }

        if ($match->edit_status === 'verified' && $user->role !== 'superadmin') {
            return response()->json([
                'message' => 'El partido está verificado y no se pueden editar los eventos.',
            ], 403);
        }

        $data = $request->validate([
            'events'                     => ['required', 'array'],
            'events.*.id'                => ['nullable', 'integer', 'exists:match_events,id'],
            'events.*.side'              => ['required', Rule::in(['home', 'away'])],
            'events.*.minute'            => ['required', 'integer', 'min:0', 'max:130'],
            'events.*.type'              => [
                'required',
                Rule::in(['goal', 'own_goal', 'yellow', 'red', 'sub_in']),
            ],
            'events.*.player_id'         => ['nullable', 'integer', 'exists:players,id'],
            'events.*.related_player_id' => ['nullable', 'integer', 'exists:players,id'],
            'events.*.detail'            => ['nullable', 'string', 'max:255'],
        ]);

        $eventsInput = $data['events'];

        $match->loadMissing(['homeTeam', 'awayTeam']);
        $homeTeamId = $match->home_team_id;
        $awayTeamId = $match->away_team_id;

        // ===== VALIDACIONES EXTRA con team_players =====
        foreach ($eventsInput as $idx => $ev) {
            $side = $ev['side'];
            $teamId = $side === 'home' ? $homeTeamId : $awayTeamId;

            $type = $ev['type'];

            $needsPlayer = in_array($type, ['goal', 'own_goal', 'yellow', 'red', 'sub_in'], true);

            if ($needsPlayer && empty($ev['player_id'])) {
                return response()->json([
                    'message' => "El evento #{$idx} requiere player_id.",
                ], 422);
            }

            // Para cambios: necesitamos los dos jugadores y que sean distintos
            if ($type === 'sub_in') {
                if (empty($ev['player_id']) || empty($ev['related_player_id'])) {
                    return response()->json([
                        'message' => "El evento #{$idx} (cambio) requiere jugador que entra y que sale.",
                    ], 422);
                }
                if ($ev['player_id'] === $ev['related_player_id']) {
                    return response()->json([
                        'message' => "El evento #{$idx} (cambio) no puede tener el mismo jugador entrando y saliendo.",
                    ], 422);
                }
            }

            // Validar pertenencia al equipo mediante team_players
            if (!empty($ev['player_id'])) {
                $belongs = DB::table('team_players')
                    ->where('player_id', $ev['player_id'])
                    ->where('team_id', $teamId)
                    ->exists();

                if (!$belongs) {
                    return response()->json([
                        'message' => "El jugador {$ev['player_id']} no pertenece al equipo del evento (#{$idx}).",
                    ], 422);
                }
            }

            if (!empty($ev['related_player_id'])) {
                $belongs = DB::table('team_players')
                    ->where('player_id', $ev['related_player_id'])
                    ->where('team_id', $teamId)
                    ->exists();

                if (!$belongs) {
                    return response()->json([
                        'message' => "El jugador relacionado {$ev['related_player_id']} no pertenece al equipo del evento (#{$idx}).",
                    ], 422);
                }
            }
        }

        // ===== PERSISTENCIA =====
        DB::transaction(function () use ($match, $eventsInput, $homeTeamId, $awayTeamId) {
            $existing = $match->events()->get()->keyBy('id');
            $keepIds  = [];

            foreach ($eventsInput as $raw) {
                $id = $raw['id'] ?? null;

                $teamId = $raw['side'] === 'home' ? $homeTeamId : $awayTeamId;

                $payload = [
                    'minute'            => $raw['minute'],
                    'type'              => $raw['type'],
                    'player_id'         => $raw['player_id'] ?? null,
                    'related_player_id' => $raw['related_player_id'] ?? null,
                    'team_id'           => $teamId,
                    'detail'            => $raw['detail'] ?? null,
                ];

                if ($id && $existing->has($id)) {
                    /** @var MatchEvent $ev */
                    $ev = $existing->get($id);
                    $ev->fill($payload);
                    $ev->save();
                    $keepIds[] = $ev->id;
                } else {
                    $ev = new MatchEvent($payload);
                    $ev->match_id = $match->id;
                    $ev->save();
                    $keepIds[] = $ev->id;
                }
            }

            if (!empty($keepIds)) {
                $match->events()
                    ->whereNotIn('id', $keepIds)
                    ->delete();
            } else {
                $match->events()->delete();
            }
        });

        $match->load(['events.player', 'events.relatedPlayer', 'events.team']);

        return response()->json([
            'message' => 'Eventos sincronizados correctamente.',
            'data'    => $match->events,
        ]);
    }
}
