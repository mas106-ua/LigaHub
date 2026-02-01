<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrivateLeagueSchedulePreviewRequest;
use App\Models\League;
use App\Services\RoundRobinScheduler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivateLeagueScheduleController extends Controller
{
    public function __construct(private readonly RoundRobinScheduler $scheduler)
    {
    }

    public function preview(PrivateLeagueSchedulePreviewRequest $request, League $league): JsonResponse
    {
        $this->ensurePrivateLeague($league);
        $this->ensureMemberOrOwner($request, $league);

        // Solo admin/owner
        if (! $request->user()->canManageLeague($league)) {
            return response()->json([
                'message' => 'No tienes permisos para generar el calendario de esta liga.',
            ], 403);
        }

        $type = $request->validated()['type'] ?? 'single';
        $rounds = $type === 'double' ? 2 : 1;

        // Equipos de la liga (orden estable)
        $teams = DB::table('league_teams as lt')
            ->join('teams as t', 't.id', '=', 'lt.team_id')
            ->where('lt.league_id', $league->id)
            ->orderBy('t.id')
            ->get([
                't.id as id',
                't.name as name',
                't.short_name as short_name',
                't.crest_url as crest_url',
            ])
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'name' => $r->name,
                'short_name' => $r->short_name,
                'crest_url' => $r->crest_url,
            ])
            ->values()
            ->all();

        if (count($teams) < 2) {
            return response()->json([
                'message' => 'Validación incorrecta.',
                'errors'  => [
                    'teams' => ['Debes tener al menos 2 equipos en la liga para generar el calendario.'],
                ],
            ], 422);
        }

        // Índice por ID
        $teamsById = [];
        $teamIds = [];
        foreach ($teams as $t) {
            $teamsById[$t['id']] = $t;
            $teamIds[] = $t['id'];
        }

        // Pairings deterministas
        $pairings = $this->scheduler->build($teamIds, $rounds);

        // Construcción de matchdays
        $matchdays = [];
        foreach ($pairings as $matchdayNumber => $pairs) {
            $matches = [];
            $seen = [];

            foreach ($pairs as [$homeId, $awayId]) {
                // COS: no solapes por jornada (defensivo)
                if (isset($seen[$homeId]) || isset($seen[$awayId])) {
                    return response()->json([
                        'message' => 'Error generando calendario (solape detectado).',
                    ], 500);
                }
                $seen[$homeId] = true;
                $seen[$awayId] = true;

                $matches[] = [
                    'id' => null, // preview
                    'matchday_number' => (int) $matchdayNumber,
                    'scheduled_at' => null,
                    'status' => 'scheduled',
                    'home_team' => $this->teamPayload($teamsById[$homeId]),
                    'away_team' => $this->teamPayload($teamsById[$awayId]),
                    'score' => ['home' => null, 'away' => null],
                ];
            }

            // Orden estable dentro de jornada
            usort($matches, function ($m1, $m2) {
                $h1 = (int) $m1['home_team']['id'];
                $h2 = (int) $m2['home_team']['id'];
                if ($h1 !== $h2) return $h1 <=> $h2;

                $a1 = (int) $m1['away_team']['id'];
                $a2 = (int) $m2['away_team']['id'];
                return $a1 <=> $a2;
            });

            $matchdays[] = [
                'number' => (int) $matchdayNumber,
                'matches' => $matches,
            ];
        }

        // Orden estable de jornadas
        usort($matchdays, fn($a, $b) => $a['number'] <=> $b['number']);

        return response()->json([
            'data' => [
                'league_id' => (int) $league->id,
                'type' => $type,
                'rounds' => $rounds,
                'teams_count' => count($teams),
                'matchdays' => $matchdays,
            ],
        ]);
    }

    private function teamPayload(array $t): array
    {
        return [
            'id' => (int) $t['id'],
            'name' => $t['name'],
            'short_name' => $t['short_name'],
            'crest_url' => $t['crest_url'],
        ];
    }

    private function ensurePrivateLeague(League $league): void
    {
        if ($league->type !== 'private') {
            abort(404);
        }
    }

    /**
     * Anti-enumeración: 404 si no es miembro/owner.
     */
    private function ensureMemberOrOwner(Request $request, League $league): void
    {
        $user = $request->user();

        $isOwner = $league->owner_user_id && ((int)$league->owner_user_id === (int)$user->id);
        if ($isOwner) return;

        $isMember = DB::table('league_memberships')
            ->where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isMember) {
            abort(404);
        }
    }
}
