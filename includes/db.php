<?php
require_once __DIR__ . '/config.php';

/**
 * Returns a shared PDO connection to the SQLite database.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // SQLite ignores foreign keys unless enabled per connection.
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    return $pdo;
}
