<?php

require_once __DIR__ . '/../includes/table-view.php';

function assert_same($expected, $actual, $message)
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
$db->exec('CREATE TABLE live_table_metadata (competition_code TEXT, season_label TEXT, matchweek INTEGER, updated_at INTEGER)');
$db->exec('CREATE TABLE league_table_TEST (team_name TEXT, position INTEGER, updated_at INTEGER)');
$db->exec('CREATE TABLE league_table_snapshots (competition_code TEXT, season_label TEXT, matchweek INTEGER, team_name TEXT, position INTEGER, archived_at INTEGER, source_updated_at INTEGER)');
$db->exec('CREATE TABLE league_table_snapshots_by_date (competition_code TEXT, season_label TEXT, snapshot_date TEXT, team_name TEXT, position INTEGER, archived_at INTEGER)');
$db->exec('CREATE TABLE matches (id INTEGER PRIMARY KEY, competition_code TEXT, season_label TEXT, matchweek INTEGER, match_date TEXT, match_timestamp TEXT, home_team TEXT, away_team TEXT, home_goals INTEGER, away_goals INTEGER)');

$db->exec("INSERT INTO live_table_metadata VALUES ('TEST', '2025-2026', 3, 1)");
$db->exec("INSERT INTO league_table_TEST VALUES ('Alpha', 1, 1), ('Beta', 2, 1)");
$db->exec("INSERT INTO league_table_snapshots VALUES
    ('TEST', '2025-2026', 1, 'Alpha', 2, 1, 1),
    ('TEST', '2025-2026', 1, 'Beta', 1, 1, 1),
    ('TEST', '2025-2026', 2, 'Alpha', 2, 1, 1),
    ('TEST', '2025-2026', 2, 'Beta', 1, 1, 1)");
$db->exec("INSERT INTO league_table_snapshots_by_date VALUES
    ('TEST', '2025-2026', '2025-08-01', 'Alpha', 2, 1),
    ('TEST', '2025-2026', '2025-08-01', 'Beta', 1, 1),
    ('TEST', '2025-2026', '2025-08-02', 'Alpha', 1, 1),
    ('TEST', '2025-2026', '2025-08-02', 'Beta', 2, 1),
    ('TEST', '2025-2026', '2025-08-03', 'Alpha', 2, 1),
    ('TEST', '2025-2026', '2025-08-03', 'Beta', 1, 1)");
$db->exec("INSERT INTO matches VALUES
    (1, 'TEST', '2025-2026', 1, '2025-08-03', '2025-08-03 15:00:00', 'Alpha', 'Beta', 0, 1),
    (2, 'TEST', '2025-2026', 46, '2026-05-02', '2026-05-02 15:00:00', 'Alpha', 'Beta', 3, 0)");

$_GET = [
    'calc_mode' => 'by_date',
    'snapshot_season' => '2025-2026',
    'snapshot_date' => '2025-08-02',
];
$dateView = football_stats_get_table_view_combined($db, 'TEST', 'league_table_TEST', '2025-2026');
assert_same(1, $dateView['position_movements']['Alpha'] ?? null, 'An older By Date snapshot should compare with its preceding date.');
assert_same(-1, $dateView['position_movements']['Beta'] ?? null, 'An older By Date snapshot should include downward movement.');
assert_same('since 2025-08-01', $dateView['movement_comparison_label'] ?? null, 'By Date movement should identify its comparison date.');

$_GET = ['calc_mode' => 'by_matchweek'];
$liveView = football_stats_get_table_view_combined($db, 'TEST', 'league_table_TEST', '2025-2026');
assert_same(1, $liveView['position_movements']['Alpha'] ?? null, 'The live matchweek table should compare with the preceding archived matchweek.');
assert_same(-1, $liveView['position_movements']['Beta'] ?? null, 'The live matchweek table should include downward movement.');

$_GET = [
    'calc_mode' => 'by_match_before',
    'match_filter_mode' => 'matchweek',
    'matchweek' => '46',
    'match_id' => '1',
];
$beforeFinalMatch = football_stats_get_table_view_combined($db, 'TEST', 'league_table_TEST', '2025-2026');
assert_same(2, $beforeFinalMatch['selected_match_id'] ?? null, 'Changing to the final matchweek should replace a stale match selection.');
assert_same(1, $beforeFinalMatch['position_movements']['Beta'] ?? null, 'By Match (Before) should show the movement leading into the final matchweek fixture.');

$_GET['calc_mode'] = 'by_match';
$afterFinalMatch = football_stats_get_table_view_combined($db, 'TEST', 'league_table_TEST', '2025-2026');
assert_same(2, $afterFinalMatch['selected_match_id'] ?? null, 'By Match (After) should use the fixture shown by the final-matchweek filter.');
assert_same(1, $afterFinalMatch['position_movements']['Alpha'] ?? null, 'By Match (After) should show movement caused by the final matchweek fixture.');

$filteredView = football_stats_add_filtered_position_movements(
    ['position_movements' => ['Alpha' => 99], 'movement_comparison_label' => 'old comparison'],
    [
        ['team_name' => 'Alpha', 'position' => 1],
        ['team_name' => 'Beta', 'position' => 2],
    ],
    [
        ['team_name' => 'Beta', 'position' => 1],
        ['team_name' => 'Alpha', 'position' => 2],
    ]
);
assert_same(1, $filteredView['position_movements']['Alpha'] ?? null, 'A filter should show how far a team rises relative to completed matches.');
assert_same(-1, $filteredView['position_movements']['Beta'] ?? null, 'A filter should show how far a team falls relative to completed matches.');
assert_same('compared with all completed matches', $filteredView['movement_comparison_label'] ?? null, 'Filtered movement should explain its baseline.');

$_GET = ['movement_compare' => 'off'];
$hiddenMovementView = football_stats_apply_movement_preference(
    $filteredView,
    [['team_name' => 'Alpha', 'position' => 1]],
    [['team_name' => 'Alpha', 'position' => 2]],
    true
);
assert_same([], $hiddenMovementView['position_movements'], 'The movement toggle should be able to hide arrows.');

$_GET = ['movement_compare' => 'completed'];
$completedMovementView = football_stats_apply_movement_preference(
    ['completed_standings' => [
        ['team_name' => 'Beta', 'position' => 1],
        ['team_name' => 'Alpha', 'position' => 2],
    ]],
    [
        ['team_name' => 'Alpha', 'position' => 1],
        ['team_name' => 'Beta', 'position' => 2],
    ],
    [],
    false
);
assert_same(1, $completedMovementView['position_movements']['Alpha'] ?? null, 'Completed-match comparison should work in every calculation mode.');
assert_same('compared with all completed matches', $completedMovementView['movement_comparison_label'] ?? null, 'Completed-match movement should identify its baseline.');

$_GET = [];
$relevantMovementView = football_stats_apply_movement_preference(
    [],
    [['team_name' => 'Alpha', 'position' => 1]],
    [['team_name' => 'Alpha', 'position' => 2]],
    true
);
assert_same(1, $relevantMovementView['position_movements']['Alpha'] ?? null, 'Filtered relevant movement should compare with the unfiltered calculation.');
assert_same('compared with the unfiltered calculation', $relevantMovementView['movement_comparison_label'] ?? null, 'Filtered relevant movement should describe its calculation baseline.');

$dateOptions = football_stats_get_movement_preference_options('by_date');
assert_same('Previous date (default)', $dateOptions['relevant']['label'] ?? null, 'By Date should name its original movement comparison.');
$matchOptions = football_stats_get_movement_preference_options('by_match');
assert_same('Before selected match (default)', $matchOptions['relevant']['label'] ?? null, 'By Match should name its original movement comparison.');
$filteredOptions = football_stats_get_movement_preference_options('by_matchweek', true);
assert_same('Unfiltered calculation (default)', $filteredOptions['relevant']['label'] ?? null, 'Filtered tables should offer their relevant unfiltered baseline.');
$customOptions = football_stats_get_movement_preference_options('custom_matches');
assert_same(false, isset($customOptions['completed']), 'Custom rules should not duplicate its completed-matches default.');

echo "Movement calculations passed.\n";
