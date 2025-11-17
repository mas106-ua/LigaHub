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

        $data = $request->validate([
            'events'                     => ['required', 'array'],
            'events.*.id'                => ['nullable', 'integer', 'exists:match_events,id'],
            'events.*.side'              => ['required', Rule::in(['home', 'away'])],
            'events.*.minute'            => ['required', 'integer', 'min:0', 'max:130'],
            'events.*.extra_minute'      => ['nullable', 'integer', 'min:0', 'max:15'],
            'events.*.type'              => ['required', 'string', 'max:32'],
            'events.*.player_id'         => ['nullable', 'integer', 'exists:players,id'],
            'events.*.related_player_id' => ['nullable', 'integer', 'exists:players,id'],
            'events.*.description'       => ['nullable', 'string'],
        ]);

        $eventsInput = $data['events'];

        $allowedTypes = [
            'goal',
            'own_goal',
            'penalty_goal',
            'penalty_miss',
            'assist',
            'yellow_card',
            'red_card',
            'second_yellow',
            'substitution',
            'var',
            'note',
        ];

        // Validaciones de tipo, jugadores, cambios coherentes, etc.
        foreach ($eventsInput as $idx => $ev) {
            if (!in_array($ev['type'], $allowedTypes, true)) {
                return response()->json([
                    'message' => "Tipo de evento no permitido en index {$idx}: {$ev['type']}",
                ], 422);
            }

            $needsPlayer = in_array($ev['type'], [
                'goal',
                'own_goal',
                'penalty_goal',
                'penalty_miss',
                'assist',
                'yellow_card',
                'red_card',
                'second_yellow',
                'substitution',
            ], true);

            if ($needsPlayer && empty($ev['player_id'])) {
                return response()->json([
                    'message' => "El evento #{$idx} requiere player_id.",
                ], 422);
            }

            if ($ev['type'] === 'substitution') {
                if (empty($ev['related_player_id'])) {
                    return response()->json([
                        'message' => "El evento #{$idx} (cambio) requiere related_player_id.",
                    ], 422);
                }
                if (!empty($ev['player_id']) && $ev['player_id'] === $ev['related_player_id']) {
                    return response()->json([
                        'message' => "El evento #{$idx} (cambio) no puede tener el mismo jugador entrando y saliendo.",
                    ], 422);
                }
            }

            if (in_array($ev['type'], ['var', 'note'], true) && empty($ev['description'])) {
                return response()->json([
                    'message' => "El evento #{$idx} ({$ev['type']}) requiere una descripción.",
                ], 422);
            }
        }

        // Preparamos ids de equipos para validar side/jugadores
        $match->loadMissing(['homeTeam', 'awayTeam']);
        $homeTeamId = $match->home_team_id;
        $awayTeamId = $match->away_team_id;

        DB::transaction(function () use ($match, $eventsInput, $user, $homeTeamId, $awayTeamId) {
            $existing = $match->events()->get()->keyBy('id');
            $keepIds  = [];

            foreach ($eventsInput as $raw) {
                $id = $raw['id'] ?? null;

                $payload = [
                    'side'              => $raw['side'],
                    'minute'            => $raw['minute'],
                    'extra_minute'      => $raw['extra_minute'] ?? 0,
                    'type'              => $raw['type'],
                    'player_id'         => $raw['player_id'] ?? null,
                    'related_player_id' => $raw['related_player_id'] ?? null,
                    'description'       => $raw['description'] ?? null,
                ];

                // Validación simple de que el jugador pertenece al equipo correcto según side
                if (!empty($payload['player_id'])) {
                    $playerTeamId = DB::table('players')
                        ->where('id', $payload['player_id'])
                        ->value('team_id');

                    if ($payload['side'] === 'home' && $playerTeamId !== $homeTeamId) {
                        throw new \RuntimeException("Jugador {$payload['player_id']} no pertenece al equipo local.");
                    }
                    if ($payload['side'] === 'away' && $playerTeamId !== $awayTeamId) {
                        throw new \RuntimeException("Jugador {$payload['player_id']} no pertenece al equipo visitante.");
                    }
                }

                if (!empty($payload['related_player_id'])) {
                    $relTeamId = DB::table('players')
                        ->where('id', $payload['related_player_id'])
                        ->value('team_id');

                    if ($payload['side'] === 'home' && $relTeamId !== $homeTeamId) {
                        throw new \RuntimeException("Jugador relacionado {$payload['related_player_id']} no pertenece al equipo local.");
                    }
                    if ($payload['side'] === 'away' && $relTeamId !== $awayTeamId) {
                        throw new \RuntimeException("Jugador relacionado {$payload['related_player_id']} no pertenece al equipo visitante.");
                    }
                }

                if ($id && $existing->has($id)) {
                    /** @var MatchEvent $ev */
                    $ev = $existing->get($id);
                    $ev->fill($payload);
                    $ev->updated_by = $user->id;
                    $ev->save();
                    $keepIds[] = $ev->id;
                } else {
                    $ev = new MatchEvent($payload);
                    $ev->match_id  = $match->id;
                    $ev->created_by = $user->id;
                    $ev->updated_by = $user->id;
                    $ev->save();
                    $keepIds[] = $ev->id;
                }
            }

            // Borrar los que ya no vengan
            if (!empty($keepIds)) {
                $match->events()
                    ->whereNotIn('id', $keepIds)
                    ->delete();
            } else {
                $match->events()->delete();
            }
        });

        $match->load(['events.player', 'events.relatedPlayer']);

        return response()->json([
            'message' => 'Eventos sincronizados correctamente.',
            'data'    => $match->events,
        ]);
    }
}
