<?php
// Router for local development only:  php -S localhost:8000 router.php
//
// Makes PHP's built-in server behave like Apache/LiteSpeed on Hostinger:
//   - /admin  is redirected to  /admin/  (otherwise relative links and redirects break)
//   - includes/ and database/ are blocked, like their .htaccess files do in production
//
// Production never uses this file. If it is requested directly on a real server, it returns 404.

if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// Resolve the real file so tricks like /admin/../database/ can't get around the check.
// Lowercase because macOS file systems ignore case: /Database/ is the same folder as /database/.
$realPath = realpath(__DIR__ . $path);
if ($realPath !== false) {
    $relative = strtolower(substr($realPath, strlen(__DIR__)));
    $firstSegment = explode('/', trim($relative, '/'))[0];

    if (in_array($firstSegment, ['includes', 'database'], true)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

if (!str_ends_with($path, '/') && is_dir(__DIR__ . $path)) {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . $path . '/' . ($query !== '' ? '?' . $query : ''), true, 301);
    exit;
}

// Anything else: let the built-in server handle the request as usual.
return false;
