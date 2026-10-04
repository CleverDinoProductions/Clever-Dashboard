<?php

declare(strict_types=1);

/**
 * Pick an existing database before falling back to the canonical runtime path.
 *
 * Production databases are intentionally ignored by Git.  A fresh checkout
 * still contains the legacy football-stats.db snapshot, though, and opening a
 * missing .sqlite3 file on a read-only deployment used to turn every request
 * into an HTTP 500 response.
 */
function football_database_path(string $environmentVariable, array $candidates): string
{
    $configured = getenv($environmentVariable);
    if (is_string($configured) && trim($configured) !== '') {
        return $configured;
    }

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return $candidates[0];
}

function football_open_database(string $path): PDO
{
    $connection = new PDO('sqlite:' . $path);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $connection->exec('PRAGMA busy_timeout = 5000');
    return $connection;
}

$db_path = football_database_path('CLEVER_FOOTBALL_DB', [
    __DIR__ . '/football-stats.sqlite3',
    __DIR__ . '/football-stats.db',
]);
$db = football_open_database($db_path);

$world_cup_db_path = football_database_path('CLEVER_WORLD_CUP_DB', [
    __DIR__ . '/world-cup-stats.sqlite3',
    $db_path,
]);
$world_cup_db = $world_cup_db_path === $db_path ? $db : football_open_database($world_cup_db_path);
