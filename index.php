<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * This file allows us to run Laravel seamlessly in Laragon subfolder environments
 * (e.g. http://localhost/SUITABLE-2026/) as well as virtual hosts.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? ''
);

// Let static files in public be handled if requested directly
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
