<?php
// Creates the SQLite database, its tables, and the default admin user.
// Usage: php database/init.php
// Safe to run more than once: existing tables and users are left untouched.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/../includes/db.php';

$pdo = db();
$pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
echo "Schema applied to " . realpath(DB_PATH) . "\n";

$stmt = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE username = ?');
$stmt->execute([DEFAULT_ADMIN_USERNAME]);

if ((int) $stmt->fetchColumn() === 0) {
    $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
    $stmt->execute([DEFAULT_ADMIN_USERNAME, password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT)]);
    echo "Created admin user '" . DEFAULT_ADMIN_USERNAME . "'.\n";
} else {
    echo "Admin user '" . DEFAULT_ADMIN_USERNAME . "' already exists; skipped.\n";
}
