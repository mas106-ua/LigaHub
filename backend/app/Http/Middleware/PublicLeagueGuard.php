<?php

namespace App\Http\Middleware;

use App\Models\League;
use App\Models\MatchModel;
use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicLeagueGuard
{
    public function handle(Request $request, Closure $next)
    {
        $league = $this->resolveLeagueFromRoute($request);

        if ($league && $this->isBlockedPrivateLeague($league)) {
            abort(404);
        }

        // Si no hay league directa, puede venir por team (players públicos)
        $team = $request->route('team');
        if ($team) {
            $teamId = $team instanceof Team ? $team->id : (int) $team;

            $isPublicTeam = DB::table('league_teams as lt')
                ->join('leagues as l', 'l.id', '=', 'lt.league_id')
                ->where('lt.team_id', $teamId)
                ->where(function ($q) {
                    $q->where('l.type', '!=', 'private')
                      ->orWhere('l.visibility', '=', 'public');
                })
                ->exists();

            // Si el team NO pertenece a ninguna liga "publicable", no se expone
            if (! $isPublicTeam) {
                abort(404);
            }
        }

        return $next($request);
    }

    private function resolveLeagueFromRoute(Request $request): ?League
    {
        // /leagues/{league}/...
        $leagueParam = $request->route('league');
        if ($leagueParam) {
            return $leagueParam instanceof League
                ? $leagueParam
                : League::query()->find((int) $leagueParam);
        }

        // /matches/{match} o /matches/{match}/report
        $matchParam = $request->route('match');
        if ($matchParam) {
            $match = $matchParam instanceof MatchModel
                ? $matchParam
                : MatchModel::query()->with('league')->find((int) $matchParam);

            return $match?->league;
        }

        return null;
    }

    private function isBlockedPrivateLeague(League $league): bool
    {
        // Regla: si es privada y NO está marcada como public => no se puede ver en endpoints públicos
        return $league->type === 'private' && $league->visibility !== 'public';
    }
}
