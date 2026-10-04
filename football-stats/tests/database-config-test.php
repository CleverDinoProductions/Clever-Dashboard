<?php

declare(strict_types=1);

$directory = sys_get_temp_dir() . '/clever-football-config-' . bin2hex(random_bytes(5));
mkdir($directory, 0700, true);
$legacy = $directory . '/football-stats.db';
(new PDO('sqlite:' . $legacy))->exec('CREATE TABLE marker (id INTEGER)');
putenv('CLEVER_FOOTBALL_DB=' . $legacy);
putenv('CLEVER_WORLD_CUP_DB=' . $legacy);

require dirname(__DIR__) . '/config.php';

function config_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

config_assert(football_database_path('CLEVER_TEST_UNSET', [$directory . '/missing.sqlite3', $legacy]) === $legacy, 'An existing legacy database should be selected.');
putenv('CLEVER_TEST_DB=' . $directory . '/configured.sqlite3');
config_assert(football_database_path('CLEVER_TEST_DB', [$legacy]) === $directory . '/configured.sqlite3', 'An environment override should take priority.');
config_assert($world_cup_db === $db, 'The main database should be reused when it is the World Cup fallback.');

@unlink($legacy);
@rmdir($directory);
putenv('CLEVER_FOOTBALL_DB');
putenv('CLEVER_WORLD_CUP_DB');
echo "Football database configuration tests passed.\n";
