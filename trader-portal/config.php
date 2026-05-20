<?php
/**
 * Shared team config — same paths/credentials on every dev machine.
 * Oracle login: project root db.php
 */

declare(strict_types=1);

define('APP_BASE', '/GG-TP');

define('PORTAL_BASE', '/GG-TP/trader-portal');

define('PORTAL_DIAGNOSE_ENABLED', true);

/** When false, PHP/OCI errors are logged only (not printed on pages). */
define('PORTAL_DISPLAY_ERRORS', false);

define('SESSION_NAME', 'GG_TRADER_PORTAL');

define('MAX_UPLOAD_MB', 5);
define('ALLOWED_IMAGE_MIME', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
