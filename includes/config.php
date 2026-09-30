<?php
// Application configuration. Loaded by every page via includes/db.php.

define('APP_NAME', 'Clinic Demo');

// 'development' shows PHP errors in the browser; 'production' hides them.
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Interface languages. The first visit uses DEFAULT_LANG; visitors can switch with the ES | EN links.
define('SUPPORTED_LANGS', ['es', 'en']);
define('DEFAULT_LANG', 'es');

// Set to the clinic's local timezone, e.g. 'Europe/Madrid'.
define('APP_TIMEZONE', 'UTC');

// Override with the DB_PATH environment variable, e.g. to point tests at a throwaway database.
define('DB_PATH', getenv('DB_PATH') ?: __DIR__ . '/../database/clinic.sqlite');

// Username of the admin account created by database/init.php.
// Its password is generated randomly and printed once when init.php runs.
define('DEFAULT_ADMIN_USERNAME', 'admin');

// Admins are logged out after this many seconds without loading a page.
define('SESSION_TIMEOUT', 30 * 60);

date_default_timezone_set(APP_TIMEZONE);

if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}
