<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Roles admitidos en la ruta.
     * Uso en ruta: ->middleware('role:superadmin')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // si el usuario tiene uno de los roles permitidos
        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        // autenticado pero sin permiso
        return response()->json(['message' => 'Forbidden.'], 403);
    }
}
