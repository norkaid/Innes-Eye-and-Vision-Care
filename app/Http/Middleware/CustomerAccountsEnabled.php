<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerAccountsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('innes.features.customer_accounts'), 404);

        return $next($request);
    }
}
