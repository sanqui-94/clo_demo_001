<?php
require_once __DIR__ . '/config.php';

/**
 * Returns a shared PDO connection to the SQLite database.
 * Only database/init.php passes $create = true; everything else expects the database to exist.
 */
function db(bool $create = false): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // SQLite would silently create an empty file here, which fails later with "no such table".
        if (!$create && (!is_file(DB_PATH) || filesize(DB_PATH) === 0)) {
            throw new RuntimeException('Database not found at ' . DB_PATH . '. Run: php database/init.php');
        }

        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // SQLite ignores foreign keys unless enabled per connection.
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    return $pdo;
}
