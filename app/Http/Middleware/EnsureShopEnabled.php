<?php

namespace App\Http\Middleware;

use App\Services\ShopService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The shop pages exist only while the shop is switched on and Paystack is set up. */
class EnsureShopEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app(ShopService::class)->enabled(), 404);

        return $next($request);
    }
}
