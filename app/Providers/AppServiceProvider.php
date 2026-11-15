<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ImageUploader;
use App\Services\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    public function boot(): void
    {
        if ($this->app->isProduction() && config('app.force_https')) {
            URL::forceScheme('https');
        }

        // Explains why PHP could not receive an uploaded file (size, temp folder, ...).
        Validator::replacer('uploaded', function (string $message, string $attribute, string $rule, array $parameters, $validator) {
            $file = data_get($validator->getData(), $attribute);

            return ImageUploader::uploadErrorMessage($file instanceof UploadedFile ? $file->getError() : null, $message);
        });

        // One Gate per permission in config/innes.php.
        foreach (array_keys(config('innes.permissions')) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }
        Gate::define('settings.access', fn (User $user) => $user->hasPermission('settings.general') || $user->hasPermission('settings.system'));

        Password::defaults(fn () => Password::min(10)->letters()->mixedCase()->numbers());

        RateLimiter::for('appointments', fn (Request $r) => [Limit::perMinute(5)->by($r->ip()), Limit::perHour(20)->by($r->ip())]);
        RateLimiter::for('contact', fn (Request $r) => [Limit::perMinute(5)->by($r->ip()), Limit::perHour(20)->by($r->ip())]);
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));
        RateLimiter::for('register', fn (Request $r) => Limit::perHour(10)->by($r->ip()));
        RateLimiter::for('password-reset', fn (Request $r) => Limit::perMinute(5)->by($r->ip()));

        Paginator::defaultView('components.pagination');

        View::composer('*', function ($view) {
            static $settings;
            $settings ??= app(Settings::class);
            $view->with('site', $settings);
        });
    }
}
