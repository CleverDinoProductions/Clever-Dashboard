<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/football-settings.php';

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
clever_migrate_football_settings($db);

$required = [
    'team_metadata', 'points_deductions', 'matches', 'live_table_metadata',
    'league_table_snapshots', 'league_table_snapshots_by_date',
    'league_table_D1', 'league_table_PL', 'league_table_ELC',
    'league_table_L1', 'league_table_L2', 'league_table_NL',
];
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($required as $table) {
    if (!in_array($table, $tables, true)) {
        throw new RuntimeException("Missing dashboard table: $table");
    }
}

$standings = $db->query('SELECT * FROM league_table_D1 ORDER BY position')->fetchAll(PDO::FETCH_ASSOC);
if ($standings !== []) {
    throw new RuntimeException('A newly initialized dashboard table should be empty.');
}

echo "Football settings tests passed.\n";
