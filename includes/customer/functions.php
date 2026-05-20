<?php
/**
 * Helpers for customer PHP pages.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function customer_h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function customer_url(string $path = ''): string
{
    $base = rtrim(CUSTOMER_APP_BASE, '/');
    $path = ltrim($path, '/');
    if ($path === '') {
        return $base !== '' ? $base . '/' : '/';
    }

    return ($base !== '' ? $base : '') . '/' . $path;
}

function customer_asset(string $path): string
{
    return customer_url(ltrim($path, '/'));
}

function customer_money(float $amount): string
{
    return '£' . number_format($amount, 2);
}

function customer_format_date(mixed $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    try {
        if ($value instanceof DateTimeInterface) {
            return $value->format('d/m/Y');
        }
        $ts = is_numeric($value) ? (int) $value : strtotime((string) $value);

        return $ts ? date('d/m/Y', $ts) : '—';
    } catch (Throwable) {
        return '—';
    }
}
