<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        if ($url = config('app.url')) {
            $root = rtrim((string) $url, '/');
            URL::forceRootUrl($root);

            // XAMPP subdirectory: REQUEST_URI is stripped in public/index.php, so paginator
            // must not build links from Request::url() alone (would drop /GG-TP).
            $basePath = parse_url($root, PHP_URL_PATH) ?: '';
            if (is_string($basePath) && $basePath !== '' && $basePath !== '/') {
                Paginator::currentPathResolver(function () use ($root): string {
                    $path = trim(Request::path(), '/');

                    return $path === '' ? $root : $root.'/'.$path;
                });
            }
        }

        Paginator::defaultView('partials.pagination');
        Paginator::defaultSimpleView('partials.pagination');
    }
}
