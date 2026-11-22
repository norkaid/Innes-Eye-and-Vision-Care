<?php

use App\Http\Middleware\CustomerAccountsEnabled;
use App\Http\Middleware\EnsureCustomer;
use App\Http\Middleware\EnsureShopEnabled;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\LogoutInactiveUsers;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'staff' => EnsureStaff::class,
            'customer' => EnsureCustomer::class,
            'customer.accounts' => CustomerAccountsEnabled::class,
            'shop' => EnsureShopEnabled::class,
        ]);
        // Paystack's webhook is authenticated by its HMAC signature instead of a CSRF token.
        $middleware->validateCsrfTokens(except: ['paystack/webhook']);
        $middleware->web(append: [
            LogoutInactiveUsers::class,
            SecurityHeaders::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->isStaff() ? route('admin.dashboard') : (config('innes.features.customer_accounts') ? route('account.index') : route('home')));
        // TRUSTED_PROXIES: "*" trusts any proxy (e.g. the Docker HTTPS proxy), or a comma-separated list of IPs.
        $proxies = env('TRUSTED_PROXIES');
        $middleware->trustProxies(at: $proxies === '*' ? '*' : ($proxies ? explode(',', $proxies) : null));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
