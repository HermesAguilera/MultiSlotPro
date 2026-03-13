<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            $appUrl = trim((string) config('app.url'));

            if ($appUrl !== '') {
                // Normalize values like https://///domain into https://domain.
                $normalizedUrl = preg_replace('#^(https?:)/+#i', '$1//', $appUrl) ?? $appUrl;
                URL::forceRootUrl(rtrim($normalizedUrl, '/'));
            }
        }
    }
}
