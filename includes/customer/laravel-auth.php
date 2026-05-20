<?php
/**
 * Read Laravel customer session for standalone PHP pages (invoice.php).
 */
declare(strict_types=1);

use App\Models\Customer;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

function customer_app(): Application
{
    static $app = null;
    if ($app instanceof Application) {
        return $app;
    }

    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    return $app;
}

/**
 * Boot Laravel web middleware stack so Auth reads the same session cookie as the shop.
 */
function customer_boot_web_session(): void
{
    static $booted = false;
    if ($booted) {
        return;
    }
    $booted = true;

    $app = customer_app();
    $uri = '/invoices';
    $query = $_SERVER['QUERY_STRING'] ?? '';
    if ($query !== '') {
        $uri .= '?' . $query;
    }

    $request = Request::create(
        $uri,
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        $_GET,
        $_COOKIE,
        $_FILES,
        $_SERVER
    );

    /** @var Kernel $kernel */
    $kernel = $app->make(Kernel::class);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
}

function customer_auth_user(): ?User
{
    static $resolved = null;
    static $done = false;
    if ($done) {
        return $resolved;
    }
    $done = true;

    try {
        customer_boot_web_session();
        $user = Auth::guard('web')->user();
        if ($user instanceof User
            && Customer::query()->where('customer_id', $user->user_id)->exists()) {
            $resolved = $user;
        }
    } catch (Throwable) {
        $resolved = null;
    }

    return $resolved;
}

function customer_session(): ?\Illuminate\Session\Store
{
    try {
        customer_boot_web_session();

        return customer_app()->make('session');
    } catch (Throwable) {
        return null;
    }
}

function customer_flash_pull(string $key): ?string
{
    $session = customer_session();
    if ($session === null) {
        return null;
    }
    $value = $session->pull($key);

    return is_string($value) && $value !== '' ? $value : null;
}

function customer_require_auth(): User
{
    $user = customer_auth_user();
    if ($user) {
        return $user;
    }

    $return = (string) ($_SERVER['REQUEST_URI'] ?? '/invoice.php');
    if ($return !== '' && $return[0] !== '/') {
        $return = '/' . $return;
    }

    $loginUrl = rtrim((string) config('app.url'), '/') . '/login?redirect=' . rawurlencode($return);
    header('Location: ' . $loginUrl);
    exit;
}
