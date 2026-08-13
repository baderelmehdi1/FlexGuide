<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Gate a route on a minimum role rank.
     *
     * Usage: ->middleware('ensure.role:approver')
     *
     * This is a coarse route-level gate. Anything that depends on the
     * specific resource (its owner, its status) belongs in a Policy instead.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $required = Role::tryFrom($role);

        if (! $required) {
            throw new \InvalidArgumentException("Unknown role [{$role}] in route middleware.");
        }

        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        abort_unless($user->hasAtLeastRole($required), 403, 'Your role does not allow access to this area.');

        return $next($request);
    }
}
