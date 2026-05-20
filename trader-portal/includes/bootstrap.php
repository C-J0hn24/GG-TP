<?php
/**
 * Common bootstrap: config, session, UTF-8.
 */
declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config.php';
if (!is_readable($configPath)) {
    http_response_code(500);
    exit('Configuration missing. Copy config.example.php to config.php');
}

require_once $configPath;

if (! (defined('PORTAL_DISPLAY_ERRORS') && PORTAL_DISPLAY_ERRORS)) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    $cookiePath = defined('APP_BASE') && APP_BASE !== '' ? APP_BASE : '/';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookiePath,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start(['use_strict_mode' => true]);
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

portal_ensure_upload_dirs();
