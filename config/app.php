<?php
declare(strict_types=1);

/**
 * Application Constants
 * Global settings and configuration for DevLync.com
 */

// Site Identity
define('SITE_NAME', 'DevLync');
define('SITE_DOMAIN', 'devlync.com');
define('SITE_URL', 'https://devlync.com');
define('SITE_TAGLINE', 'Developer Tools Discovery & Reviews');
define('SITE_NICHE', 'Developer Tools, APIs & Software Reviews');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('VIEWS_PATH', ROOT_PATH . '/views');
define('CACHE_PATH', ROOT_PATH . '/cache');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('IMAGES_PATH', ROOT_PATH . '/assets/images/articles');

// Cache TTL (seconds)
define('CACHE_TTL_ARTICLE', 3600);     // 1 hour for single articles
define('CACHE_TTL_LISTING', 300);      // 5 minutes for listing pages
define('CACHE_TTL_HOME', 300);         // 5 minutes for homepage
define('CACHE_TTL_PAGE', 900);         // 15 minutes for category/author pages

// Pagination
define('POSTS_PER_PAGE', 12);

// Image Settings
define('IMAGE_MAX_WIDTH', 1200);
define('IMAGE_QUALITY_WEBP', 85);

// Base Path — auto-detect subfolder (e.g. '/devlync.com' on XAMPP, '' on production)
// Derived from DOCUMENT_ROOT vs ROOT_PATH so it's stable regardless of entry point
$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
$rootPath = str_replace('\\', '/', ROOT_PATH);
$detectedBase = $docRoot ? str_replace($docRoot, '', $rootPath) : '';
define('BASE_PATH', ($detectedBase && $detectedBase !== '/') ? $detectedBase : '');

/**
 * Generates a URL with the correct base path prefix.
 * Use this for all internal links, redirects, and asset paths.
 *
 * @param string $path Path starting with '/', e.g. '/blog', '/assets/css/style.css'
 * @return string Full path with base prefix
 */
function url(string $path = '/'): string
{
    return BASE_PATH . $path;
}

// Environment
define('APP_DEBUG', (bool) (getenv('APP_DEBUG') ?: false));

// Timezone
date_default_timezone_set('UTC');

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/logs/php_errors.log');
}
