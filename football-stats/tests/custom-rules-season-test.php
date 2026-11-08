<?php
/** Custom season selection must not fall back to the live season or week. */
require_once __DIR__ . '/../includes/table-view.php';
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
function season_assert($expected, $actual, string $message): void {
    if ($expected !== $actual) throw new RuntimeException($message . ': ' . var_export($actual, true));
}
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
clever_migrate_football_settings($db);
$db->exec('CREATE TABLE matches (id INTEGER PRIMARY KEY, competition_code TEXT, season_label TEXT, matchweek INTEGER, match_date TEXT, match_timestamp TEXT, home_team TEXT, away_team TEXT, home_goals INTEGER, away_goals INTEGER)');
$db->exec('CREATE TABLE live_table_metadata (competition_code TEXT, season_label TEXT, matchweek INTEGER, updated_at INTEGER)');
$db->exec('CREATE TABLE league_table_PL (position INTEGER, team_name TEXT, team_crest TEXT, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER)');
$db->exec('CREATE TABLE league_table_snapshots AS SELECT *, "PL" AS competition_code, "2026-2027" AS season_label, CAST(3 AS INTEGER) AS matchweek, 1 AS archived_at, 1 AS source_updated_at FROM league_table_PL WHERE 0');
$db->exec('CREATE TABLE league_table_snapshots_by_date AS SELECT *, "PL" AS competition_code, "2026-2027" AS season_label, "2026-08-03" AS snapshot_date, 1 AS archived_at FROM league_table_PL WHERE 0');
$db->exec("INSERT INTO live_table_metadata VALUES ('PL', '2026-2027', 3, 1)");
$db->exec("INSERT INTO matches VALUES
    (1,'PL','2026-2027',1,'2026-08-01','','A','B',1,0),
    (2,'PL','2026-2027',2,'2026-08-02','','A','B',0,0),
    (3,'PL','2026-2027',3,'2026-08-03','','B','C',1,0),
    (4,'PL','2026-2027',4,'2026-08-04','','C','A',NULL,NULL)");
$db->exec("INSERT INTO league_table_PL VALUES
    (1,'A','',2,1,1,0,1,0,1,4,1),
    (2,'B','',3,1,1,1,1,1,0,4,1),
    (3,'C','',1,0,0,1,0,1,-1,0,1)");

$GLOBALS['db'] = $db;
$db->exec("INSERT INTO matches VALUES
    (11,'PL','2025-2026',1,'2025-08-01','','Old A','Old B',0,2),
    (12,'PL','2025-2026',2,'2025-08-08','','Old A','Old B',1,0),
    (21,'PL','2024-2025',1,'2024-08-01','','Older A','Older B',0,0)");
$db->exec("INSERT INTO league_table_snapshots VALUES
    (1,'Old B','old-b.png',1,1,0,0,2,0,2,3,100,'PL','2025-2026',1,200,100),
    (2,'Old A','old-a.png',1,0,0,1,0,2,-2,0,100,'PL','2025-2026',1,200,100)");
foreach (['live', 'snapshot'] as $staleView) {
    $_GET = ['calc_mode' => 'custom_matches', 'snapshot_season' => '2025-2026', 'table_view' => $staleView, 'matchweek' => 3, 'custom_through_matchweek' => 1];
    $view = football_stats_get_table_view_combined($db, 'PL', 'league_table_PL', '2026-2027');
    season_assert('2025-2026', $view['active_season_label'], 'Selected historic season drives calculation');
    season_assert(1, $view['active_matchweek'], 'Selected custom week replaces stale live/snapshot week');
    season_assert(['Old B' => 3, 'Old A' => 0], array_column($view['standings'], 'points', 'team_name'), 'Historic results and roster are used');
    season_assert(200, $view['last_update']['ts'], 'Timestamp belongs to selected archive');
    season_assert(['Old B', 'Old A'], array_column($view['completed_standings'], 'team_name'), 'Comparison uses selected season');
    ob_start(); football_stats_render_table_view_controls($view, '2026-2027', 'premier-league', 'table'); $html = ob_get_clean();
    season_assert(true, str_contains($html, 'data-points-advantage="Old A"'), 'Controls use historic roster');
    season_assert(false, str_contains($html, 'data-points-advantage="A"'), 'Live roster is absent');
}
$_GET = ['calc_mode' => 'custom_matches', 'snapshot_season' => '2024-2025', 'custom_through_matchweek' => 1];
$view = football_stats_get_table_view_combined($db, 'PL', 'league_table_PL', '2026-2027');
season_assert('2024-2025', $view['active_season_label'], 'Match-only season needs no archived snapshot');
season_assert(['Older A' => 1, 'Older B' => 1], array_column($view['standings'], 'points', 'team_name'), 'Match-only historic season is calculated');
season_assert(true, in_array('2024-2025', $view['available_seasons'], true), 'Match-only season remains selectable');
season_assert(null, $view['last_update']['ts'], 'Missing archive does not show live timestamp');
$_GET['custom_through_matchweek'] = 0;
$view = football_stats_get_table_view_combined($db, 'PL', 'league_table_PL', '2026-2027');
season_assert(0, $view['active_matchweek'], 'Historic pre-season retains week zero');
season_assert(['Older A' => 0, 'Older B' => 0], array_column($view['standings'], 'points', 'team_name'), 'Historic pre-season keeps historic roster');
unset($_GET['custom_through_matchweek']);
$view = football_stats_get_table_view_combined($db, 'PL', 'league_table_PL', '2026-2027');
season_assert(null, $view['active_matchweek'], 'All weeks does not inherit a live week cutoff');
$_GET = ['calc_mode' => 'custom_matches'];
$view = football_stats_get_table_view_combined($db, 'PL', 'league_table_PL', '2026-2027');
season_assert('2026-2027', $view['active_season_label'], 'Default custom calculation still uses live season');
season_assert(['A' => 4, 'B' => 4, 'C' => 0], array_column($view['standings'], 'points', 'team_name'), 'Default live results are preserved');
restore_error_handler();
echo "Custom rules historical season tests passed.\n";
