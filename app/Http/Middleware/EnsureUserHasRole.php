<?php

namespace App\Http\Middleware;

use App\Domain\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// used in the routes like ->middleware('role:consultant')
// or with several roles: ->middleware('role:project_lead,purchasing')
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = array_map(fn (string $role) => Role::from($role), $roles);

        // this runs on the server, so hiding a button is never the only protection
        if (! in_array($request->user()?->role, $allowed, true)) {
            abort(403, 'Your role is not allowed to open this page.');
        }

        return $next($request);
    }
}
