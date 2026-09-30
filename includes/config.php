<?php
// Application configuration. Loaded by every page via includes/db.php.

define('APP_NAME', 'Clinic Demo');

// 'development' shows PHP errors in the browser; 'production' hides them.
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Set to the clinic's local timezone, e.g. 'Europe/Madrid'.
define('APP_TIMEZONE', 'UTC');

define('DB_PATH', __DIR__ . '/../database/clinic.sqlite');

// Default admin account created by database/init.php. Change it after first login.
define('DEFAULT_ADMIN_USERNAME', 'admin');
define('DEFAULT_ADMIN_PASSWORD', 'changeme');

date_default_timezone_set(APP_TIMEZONE);

if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}
