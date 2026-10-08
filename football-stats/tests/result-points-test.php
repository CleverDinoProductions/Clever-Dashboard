<?php
require_once __DIR__ . '/../includes/table-view.php';

function points_assert($expected, $actual, string $message): void {
    if ($expected !== $actual) throw new RuntimeException($message . ': ' . var_export($actual, true));
}
function points_by_team(array $rows): array {
    return array_column($rows, 'points', 'team_name');
}
function points_rule_input(array $rules): array {
    $rules['quarter_boundaries'] = implode(',', $rules['quarter_boundaries']);
    foreach ($rules['zones'] as &$zone) $zone['color'] ??= '#123456';
    return $rules;
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
$_GET = [];
points_assert(['win'=>3,'draw'=>1,'loss'=>0], football_stats_get_result_points('PL','2026-2027',$db), 'Defaults are 3/1/0');
$rules = points_rule_input(clever_competition_rules($db, 'PL'));
$rules['win_points'] = 5; $rules['draw_points'] = 2; $rules['loss_points'] = 1;
clever_save_competition_rules($db, 'PL', '', $rules);
points_assert(['win'=>5,'draw'=>2,'loss'=>1], football_stats_get_result_points('PL','2026-2027',$db), 'League scoring is inherited');
$season = $rules; $season['win_points'] = 4;
clever_save_competition_rules($db, 'PL', '2025-2026', $season);
points_assert(4, football_stats_get_result_points('PL','2025-2026',$db)['win'], 'Season scoring takes precedence');
points_assert(5, football_stats_get_result_points('PL','2026-2027',$db)['win'], 'Other seasons stay independent');
// Legacy saved JSON still inherits standard scoring fields.
$db->exec("INSERT INTO competition_rule_overrides VALUES ('ELC', '', '{\"team_count\":24}')");
points_assert(['win'=>3,'draw'=>1,'loss'=>0], football_stats_get_result_points('ELC','2026-2027',$db), 'Old overrides retain defaults');
foreach ([-1, 101, '2.5', [], 'invalid'] as $invalid) {
    $bad = $rules; $bad['loss_points'] = $invalid;
    try { clever_validate_competition_rules($bad); throw new RuntimeException('Accepted invalid result points'); }
    catch (InvalidArgumentException $expected) {}
    $_GET = ['custom_win_points'=>$invalid];
    points_assert(5, football_stats_get_result_points('PL','2026-2027',$db,true)['win'], 'Invalid URL values fall back');
}
$_GET = ['custom_win_points'=>'0', 'custom_draw_points'=>'0', 'custom_loss_points'=>'5'];
points_assert(['win'=>0,'draw'=>0,'loss'=>5], football_stats_get_result_points('PL','2026-2027',$db,true), 'Zero points are valid');
points_assert(5, football_stats_get_result_points('PL','2026-2027',$db)['win'], 'Custom scoring cannot change competition defaults');
points_assert(true, str_contains(football_stats_build_table_view_url('2026-2027','premier-league','table'), 'custom_loss_points=5'), 'Custom scoring persists in navigation');
$_GET = [];
$rows = football_stats_compute_custom_match_standings($db,'PL','2026-2027','league_table_PL',[]);
points_assert(['B'=>8,'A'=>7,'C'=>1], points_by_team($rows), 'Scoring includes losses and changes rank');
$rows = football_stats_compute_custom_match_standings($db,'PL','2026-2027','league_table_PL',['a1']);
points_assert(7, points_by_team($rows)['B'], 'Excluded losses receive no points');
$rows = football_stats_compute_custom_match_standings($db,'PL','2026-2027','league_table_PL',[],[4=>'home']);
points_assert(6, points_by_team($rows)['C'], 'Unplayed simulated wins receive configured points');
$adjusted = football_stats_apply_points_deductions($rows,[['team_name'=>'C','points'=>2]]);
points_assert(4, points_by_team($adjusted)['C'], 'Deductions apply after scoring');
$custom = ['win'=>0,'draw'=>0,'loss'=>5];
$rows = football_stats_compute_custom_match_standings($db,'PL','2026-2027','league_table_PL',[],[],$custom);
points_assert(['B'=>5,'C'=>5,'A'=>0], points_by_team($rows), 'Explicit what-if scoring controls ranks');
$home = football_stats_compute_filtered_standings($db,'PL','2026-2027','home',19,'league_table_PL');
points_assert(7, points_by_team($home)['A'], 'Home filters use competition scoring');
$away = football_stats_compute_filtered_standings($db,'PL','2026-2027','away',19,'league_table_PL');
points_assert(3, points_by_team($away)['B'], 'Away filters award loss and draw points');
$_GET = ['match_id'=>3];
points_assert(['B'=>8,'A'=>7,'C'=>1], points_by_team(football_stats_get_table_view_by_match($db,'PL','league_table_PL','2026-2027')['standings']), 'After-match tables use scoring');
points_assert(3, points_by_team(football_stats_get_table_view_by_match_before($db,'PL','league_table_PL','2026-2027')['standings'])['B'], 'Before-match tables use scoring');
$_GET = [];
points_assert(['B'=>8,'A'=>7,'C'=>1], points_by_team(football_stats_get_table_view($db,'PL','league_table_PL','2026-2027')['standings']), 'Live imported standings are reweighted');
$db->exec('INSERT INTO league_table_snapshots SELECT *, "PL", "2026-2027", 3, 1, 1 FROM league_table_PL');
$db->exec('INSERT INTO league_table_snapshots_by_date SELECT *, "PL", "2026-2027", "2026-08-03", 1 FROM league_table_PL');
$_GET = ['table_view'=>'snapshot','matchweek'=>3];
points_assert(8, points_by_team(football_stats_get_table_view($db,'PL','league_table_PL','2026-2027')['standings'])['B'], 'Matchweek snapshots are reweighted');
$_GET = ['snapshot_date'=>'2026-08-03'];
points_assert(8, points_by_team(football_stats_get_table_view_by_date($db,'PL','league_table_PL','2026-2027')['standings'])['B'], 'Date snapshots are reweighted');
$db->exec("UPDATE league_table_PL SET points=2 WHERE team_name='A'");
$_GET = [];
points_assert(5, points_by_team(football_stats_get_table_view($db,'PL','league_table_PL','2026-2027')['standings'])['A'], 'Imported points adjustments are preserved');
$_GET = ['calc_mode'=>'custom_matches','custom_win_points'=>'0','custom_draw_points'=>'0','custom_loss_points'=>'5'];
$view = football_stats_get_table_view_combined($db,'PL','league_table_PL','2026-2027');
points_assert(0, points_by_team($view['standings'])['A'], 'Custom mode applies URL scoring');
points_assert(7, points_by_team($view['completed_standings'])['A'], 'Actual completed baseline keeps competition scoring');
points_assert(0, points_by_team($view['custom_selected_original_standings'])['A'], 'Selected-results baseline uses custom scoring');
points_assert(0, points_by_team($view['custom_all_overridden_standings'])['A'], 'Altered-results baseline uses custom scoring');
$GLOBALS['db'] = $db;
ob_start(); football_stats_render_table_view_controls($view,'2026-2027','premier-league','table'); $html = ob_get_clean();
points_assert(true, str_contains($html, 'data-result-points="loss" data-default-points="1"'), 'Custom inputs show competition defaults');
points_assert(true, str_contains($html, 'Win 0 / Draw 0 / Loss 5'), 'Applied rules explain scoring');
$zero = $rules;
$zero['win_points'] = $zero['draw_points'] = $zero['loss_points'] = 0;
$zero['safety_target_halfway'] = $zero['comparison_target_one'] = $zero['comparison_target_two'] = 0;
points_assert(0, clever_validate_competition_rules($zero)['win_points'], 'Admin accepts all-zero scoring with zero targets');
$zero['comparison_target_one'] = 1;
try { clever_validate_competition_rules($zero); throw new RuntimeException('Accepted unattainable target'); }
catch (InvalidArgumentException $expected) {}
$rules['win_points'] = 2;
$rules['draw_points'] = 4;
$rules['comparison_target_one'] = 140;
points_assert(140, clever_validate_competition_rules($rules)['comparison_target_one'], 'Target limits follow the highest outcome points');
echo "Result points tests passed.\n";
