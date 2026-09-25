<?php

require_once __DIR__ . '/../includes/table-view.php';

function assert_crest_same($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, sprintf(
            "%s\nExpected: %s\nActual: %s\n",
            $message,
            var_export($expected, true),
            var_export($actual, true)
        ));
        exit(1);
    }
}

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE league_table_TEST (team_name TEXT, team_crest TEXT)');
$db->exec('CREATE TABLE league_table_snapshots (competition_code TEXT, season_label TEXT, matchweek INTEGER, team_name TEXT, team_crest TEXT)');
$db->exec('CREATE TABLE league_table_snapshots_by_date (competition_code TEXT, season_label TEXT, snapshot_date TEXT, team_name TEXT, team_crest TEXT)');
$db->exec('CREATE TABLE matches (id INTEGER PRIMARY KEY, competition_code TEXT, season_label TEXT, matchweek INTEGER, match_date TEXT, match_timestamp TEXT, home_team TEXT, away_team TEXT, home_goals INTEGER, away_goals INTEGER)');

$db->exec("INSERT INTO league_table_TEST VALUES
    ('Historic FC', 'https://badges.test/current-historic.png'),
    ('Current FC', 'https://badges.test/current.png')");
$db->exec("INSERT INTO league_table_snapshots VALUES
    ('TEST', '2024-2025', 1, 'Historic FC', 'https://badges.test/historic-old.png'),
    ('TEST', '2024-2025', 2, 'Historic FC', 'https://badges.test/historic-new.png'),
    ('TEST', '2025-2026', 1, 'Historic FC', 'https://badges.test/new-season.png')");
$db->exec("INSERT INTO league_table_snapshots_by_date VALUES
    ('TEST', '2024-2025', '2024-08-01', 'Date Only FC', 'https://badges.test/date-only.png')");
$db->exec("INSERT INTO matches VALUES
    (1, 'TEST', '2024-2025', 1, '2024-08-01', '2024-08-01 15:00:00', 'Historic FC', 'Date Only FC', 1, 0),
    (2, 'TEST', '2024-2025', 2, '2024-08-08', '2024-08-08 15:00:00', 'Current FC', 'Historic FC', 0, 0)");

$crestMap = football_stats_get_team_crest_map($db, 'TEST', '2024-2025', 'league_table_TEST');
assert_crest_same(
    'https://badges.test/historic-new.png',
    $crestMap['Historic FC'] ?? null,
    'The latest badge from the selected historic season should take priority over the live table.'
);
assert_crest_same(
    'https://badges.test/date-only.png',
    $crestMap['Date Only FC'] ?? null,
    'A badge stored only in date snapshots should be available to every calculation mode.'
);
assert_crest_same(
    'https://badges.test/current.png',
    $crestMap['Current FC'] ?? null,
    'The live table should fill badge gaps in the selected season archives.'
);

$standings = football_stats_add_team_crests([
    ['team_name' => 'Historic FC', 'team_crest' => 'https://badges.test/standing-specific.png'],
    ['team_name' => 'Date Only FC'],
    ['team_name' => 'Current FC', 'team_crest' => ''],
], $crestMap);
assert_crest_same(
    'https://badges.test/standing-specific.png',
    $standings[0]['team_crest'],
    'A badge already attached to a snapshot standing should not be replaced.'
);
assert_crest_same('https://badges.test/date-only.png', $standings[1]['team_crest'], 'Missing date-mode badges should be hydrated.');
assert_crest_same('https://badges.test/current.png', $standings[2]['team_crest'], 'Missing computed-mode badges should be hydrated.');

$customStandings = football_stats_compute_custom_match_standings(
    $db,
    'TEST',
    '2024-2025',
    'league_table_TEST',
    []
);
$customCrests = array_column($customStandings, 'team_crest', 'team_name');
assert_crest_same(
    'https://badges.test/historic-new.png',
    $customCrests['Historic FC'] ?? null,
    'Custom-match calculations should use badges from their selected season.'
);
assert_crest_same(
    'https://badges.test/date-only.png',
    $customCrests['Date Only FC'] ?? null,
    'Computed standings should share the complete seasonal badge map.'
);

echo "Team crest calculations passed.\n";
