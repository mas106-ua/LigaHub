<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Competition;

class EnsureCompetitionScope
{
    /**
     * Middleware BE-REF-13:
     * Limita acceso a /api/admin/competitions/{competition}/...
     * usando scopes + league_memberships.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        if (($user->role ?? null) !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $param = $request->route('competition');
        $competition = $param instanceof Competition
            ? $param
            : Competition::query()->find($param);

        if (!$competition) {
            return response()->json(['message' => 'Competition not found'], 404);
        }

        if (!method_exists($user, 'canManageCompetition') || !$user->canManageCompetition($competition)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
