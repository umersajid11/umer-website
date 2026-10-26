<?php
// Returns a PDO connection, or null if the database is not connected yet.
// Pages must handle a null return gracefully — never crash on a missing DB.
function get_db_connection() {
    static $pdo = null;
    static $tried = false;
    if ($tried) {
        return $pdo;
    }
    $tried = true;
    $config = require __DIR__ . '/config.php';
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['name'],
        $config['charset']
    );
    try {
        $pdo = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        // Intentionally silent: callers show a "database not connected yet" notice.
        $pdo = null;
    }
    return $pdo;
}

function db_is_connected() {
    return get_db_connection() !== null;
}
