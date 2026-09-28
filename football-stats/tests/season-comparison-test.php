<?php

function assert_contains($needle, $haystack, $message)
{
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, $message . "\nMissing: " . $needle . "\n");
        exit(1);
    }
}

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE live_table_metadata (competition_code TEXT, season_label TEXT, matchweek INTEGER, updated_at INTEGER)');
$db->exec('CREATE TABLE league_table_TEST (team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER)');
$db->exec('CREATE TABLE league_table_snapshots (competition_code TEXT, season_label TEXT, matchweek INTEGER, team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, source_updated_at INTEGER, archived_at INTEGER)');
$db->exec('CREATE TABLE league_table_snapshots_by_date (competition_code TEXT, season_label TEXT, snapshot_date TEXT, team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, source_updated_at INTEGER, archived_at INTEGER)');
$db->exec('CREATE TABLE matches (id INTEGER PRIMARY KEY, competition_code TEXT, season_label TEXT, matchweek INTEGER, match_date TEXT, match_timestamp TEXT, home_team TEXT, away_team TEXT, home_goals INTEGER, away_goals INTEGER)');
$db->exec('CREATE TABLE points_deductions (competition_code TEXT, season_label TEXT, team_name TEXT, points INTEGER, reason TEXT)');
$db->exec("INSERT INTO matches VALUES
    (1, 'TEST', '2024-2025', 1, '2024-08-01', '2024-08-01 15:00:00', 'Old Alpha', 'Old Beta', 2, 0),
    (2, 'TEST', '2025-2026', 1, '2025-08-01', '2025-08-01 15:00:00', 'New Alpha', 'New Beta', 0, 0),
    (3, 'TEST', '2025-2026', 2, '2025-08-08', '2025-08-08 15:00:00', 'New Beta', 'New Alpha', 0, 1)");

$comparisonLeague = [
    'code' => 'TEST',
    'name' => 'Test League',
    'live_table' => 'league_table_TEST',
    'total_games' => 2,
    'halfway_games' => 1,
];
$currentMainTab = '2025-2026';
$currentLeague = 'test-league';

$renderComparison = static function (array $query) use ($db, $comparisonLeague, $currentMainTab, $currentLeague) {
    $_GET = $query;
    ob_start();
    include __DIR__ . '/../includes/season-comparison.php';
    return ob_get_clean();
};

$markup = $renderComparison([
    'compare_calc_mode' => 'by_match',
]);
assert_contains('<td class="team">Old Alpha</td>', $markup, 'Match comparison should calculate the older season on its first request.');
assert_contains('<td class="team">New Alpha</td>', $markup, 'Match comparison should calculate the newer season on its first request.');
assert_contains('value="1" selected', $markup, 'The older season should select its available match.');
assert_contains('value="3" selected', $markup, 'The newer season should select its newest available match.');

$staleMarkup = $renderComparison([
    'compare_calc_mode' => 'by_match_before',
    'compare_season_left' => '2024-2025',
    'compare_season_right' => '2025-2026',
    'compare_match_left' => '3',
    'compare_match_right' => '1',
]);
assert_contains('value="1" selected', $staleMarkup, 'A stale left match must be replaced by a match from the selected season.');
assert_contains('value="3" selected', $staleMarkup, 'A stale right match must be replaced by a match from the selected season.');

echo "Season comparison tests passed.\n";
