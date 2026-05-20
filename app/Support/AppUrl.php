<?php

namespace App\Support;

final class AppUrl
{
    /**
     * Fix URLs missing the APP_URL path prefix (e.g. /GG-TP) on subdirectory installs.
     */
    public static function fixApplicationUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $appBase = rtrim((string) parse_url((string) config('app.url'), PHP_URL_PATH), '/');
        if ($appBase === '' || $appBase === '/') {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $path = $parts['path'] ?? '/';
        if ($path === $appBase || str_starts_with($path, $appBase.'/')) {
            return $url;
        }

        $newPath = $appBase.($path === '/' ? '' : $path);
        $scheme = $parts['scheme'] ?? parse_url((string) config('app.url'), PHP_URL_SCHEME) ?? 'http';
        $host = $parts['host'] ?? parse_url((string) config('app.url'), PHP_URL_HOST) ?? 'localhost';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $scheme.'://'.$host.$port.$newPath.$query.$fragment;
    }

    /**
     * Standalone PHP invoice page (project root or public/invoice.php).
     *
     * @param  array<string, scalar|null>  $query
     */
    public static function invoicePageUrl(array $query = []): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $url = ($base !== '' ? $base : '').'/invoice.php';
        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }
}
