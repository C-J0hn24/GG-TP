<?php
/**
 * Template — committed config.php is used by default on all machines.
 * Copy here only if you need a local override.
 */

declare(strict_types=1);

/** Oracle credentials: edit ../db.php or ../db.local.php at project root. */

/** Laravel customer app URL path (no trailing slash), e.g. '/GG-TP' or '' on :8080 vhost */
define('APP_BASE', '/GG-TP');

/** Trader portal URL path (no trailing slash), e.g. '/GG-TP/trader-portal' */
define('PORTAL_BASE', '/GG-TP/trader-portal');

/** Set true only on local dev to open diagnose.php */
define('PORTAL_DIAGNOSE_ENABLED', true);

/** When false, PHP/OCI errors are logged only (not printed on pages). */
define('PORTAL_DISPLAY_ERRORS', false);

/** CSRF & session */
define('SESSION_NAME', 'GG_TRADER_PORTAL');

/** Upload limits */
define('MAX_UPLOAD_MB', 5);
define('ALLOWED_IMAGE_MIME', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
