<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Only active staff with dashboard access may enter /admin. Customers and guests get 403. */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && $user->isStaff() && $user->is_active && $user->hasPermission('dashboard.view'), 403);

        return $next($request);
    }
}
