<?php
require_once __DIR__ . '/../includes/table-view.php';
set_error_handler(function ($severity, $message) { if (error_reporting() & $severity) throw new RuntimeException($message); });
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
clever_migrate_football_settings($db);
$db->exec('CREATE TABLE matches (id INTEGER PRIMARY KEY, competition_code TEXT, season_label TEXT, matchweek INTEGER, match_date TEXT, match_timestamp TEXT, home_team TEXT, away_team TEXT, home_goals INTEGER, away_goals INTEGER)');
$db->exec('CREATE TABLE live_table_metadata (competition_code TEXT, season_label TEXT, matchweek INTEGER, updated_at INTEGER)');
foreach (['PL', 'ELC', 'L1', 'L2', 'D1', 'NL', 'NPLE'] as $code) {
    $db->exec("CREATE TABLE league_table_$code (position INTEGER, team_name TEXT, team_crest TEXT, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER)");
    $db->exec("INSERT INTO live_table_metadata VALUES ('$code', '2025-2026', 2, 1)");
    $stmt = $db->prepare("INSERT INTO league_table_$code VALUES (1, ?, '', 2, 1, 1, 0, 4, 1, 3, 4, 1)");
    $stmt->execute(['Test <Team>']);
    $db->exec("INSERT INTO league_table_$code VALUES (2, 'Other Club', '', 3, 0, 1, 2, 1, 4, -3, 1, 1)");
}
$db->exec('CREATE TABLE league_table_snapshots AS SELECT *, "PL" AS competition_code, "2025-2026" AS season_label, 2 AS matchweek, 1 AS archived_at, 1 AS source_updated_at FROM league_table_PL WHERE 0');
$db->exec('CREATE TABLE league_table_snapshots_by_date AS SELECT *, "PL" AS competition_code, "2025-2026" AS season_label, "2025-08-01" AS snapshot_date, 1 AS archived_at FROM league_table_PL WHERE 0');
function check_tracker_view($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$currentMainTab = '2025-2026'; $currentSubTab = 'leeds';
foreach (['premier-league', 'championship', 'league-one', 'league-two', 'division-one', 'national-league'] as $currentLeague) {
    $_GET = ['calc_mode' => 'by_matchweek', 'table_view' => 'live'];
    ob_start(); include __DIR__ . '/../tabs/' . $currentLeague . '/2025-2026/team-tracker.php'; $html = ob_get_clean();
    foreach (['Season record', 'Milestone progress', 'What if?', 'Competition zone watch', 'Attack and defence', 'Reading this tracker', 'Survival status and probability guide', 'Guaranteed Safety Target Progress', 'Mathematical Safety Target Progress', 'European Target Progress', 'Title Target Progress', 'Scenarios to Halfway', 'Season Projection', 'The 75% Rule for Survival'] as $panel) check_tracker_view(strpos($html, $panel) !== false, "$currentLeague panel: $panel");
    for ($horizon = 1; $horizon <= 6; $horizon++) check_tracker_view(strpos($html, 'Next ' . $horizon . ' ') !== false, 'Every short-horizon panel retained');
    check_tracker_view(strpos($html, 'Test &lt;Team&gt;') !== false, 'Team names escaped');
    check_tracker_view(strpos($html, 'Survival Probability') === false, 'No unlabeled probability badge');
}
$db->exec('UPDATE league_table_PL SET played = 38');
$currentLeague = 'premier-league';
ob_start(); include __DIR__ . '/../tabs/premier-league/2025-2026/team-tracker.php'; $html = ob_get_clean();
check_tracker_view(strpos($html, 'Unavailable: only 0 games remain') !== false, 'Completed season retains scenario panels with clear empty state');
check_tracker_view(strpos($html, 'Historical halfway total unavailable') !== false, 'Later points do not claim halfway achievement');
check_tracker_view(strpos($html, 'legacy heuristic') !== false, 'Probability guide remains visibly identified as heuristic');
$regionalCode = 'NPLE'; $regionalTable = 'league_table_NPLE'; $regionalName = 'Northern Premier East';
ob_start(); include __DIR__ . '/../tabs/national-league/2025-2026/team-tracker.php'; $html = ob_get_clean();
check_tracker_view(strpos($html, 'Northern Premier East') !== false && strpos($html, 'Finish · 42') !== false, 'Regional rules and identity preserved');
$db->exec('DELETE FROM league_table_NPLE');
ob_start(); include __DIR__ . '/../tabs/national-league/2025-2026/team-tracker.php'; $html = ob_get_clean();
check_tracker_view(strpos($html, 'No team data available') !== false && strpos($html, 'Northern Premier East') !== false, 'Empty state retains context');
echo "Team tracker view tests passed.\n";
