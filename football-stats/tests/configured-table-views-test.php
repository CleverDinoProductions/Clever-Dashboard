<?php
require_once __DIR__ . '/../includes/table-view.php';
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
clever_migrate_football_settings($db);
$db->exec('CREATE TABLE live_table_metadata (competition_code TEXT, season_label TEXT, matchweek INTEGER, updated_at INTEGER)');
$columns = 'team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER';
$db->exec('CREATE TABLE league_table_snapshots (competition_code TEXT, season_label TEXT, matchweek INTEGER, ' . $columns . ', source_updated_at INTEGER, archived_at INTEGER)');
$db->exec('CREATE TABLE league_table_snapshots_by_date (competition_code TEXT, season_label TEXT, snapshot_date TEXT, ' . $columns . ', source_updated_at INTEGER, archived_at INTEGER)');
$db->exec('CREATE TABLE matches (id INTEGER PRIMARY KEY, competition_code TEXT, season_label TEXT, matchweek INTEGER, match_date TEXT, match_timestamp TEXT, home_team TEXT, away_team TEXT, home_goals INTEGER, away_goals INTEGER)');
foreach (clever_competitions() as $code => $label) {
    $db->exec('CREATE TABLE league_table_' . $code . ' (' . $columns . ')');
    $db->exec("INSERT INTO league_table_$code VALUES ('', 'Leeds United', 1, 10, 5, 2, 3, 12, 10, 2, 17, 0)");
    $db->prepare("INSERT INTO live_table_metadata VALUES (?, '2026-2027', 10, 0)")->execute([$code]);
    $rules = clever_bundled_competition_rules($code);
    $rules['total_games'] = 42; $rules['regular_matchweeks'] = 42; $rules['halfway_games'] = 21;
    $rules['quarter_boundaries'] = '11,21,32'; $rules['safety_target_halfway'] = 24;
    $rules['comparison_target_one'] = 44; $rules['comparison_target_two'] = 48;
    $rules['zones'] = [['key'=>'custom', 'label'=>'Configured finish', 'from'=>1, 'to'=>1, 'color'=>'#123456']];
    clever_save_competition_rules($db, $code, '2026-2027', $rules);
}
$_GET = [];
$currentMainTab = '2026-2027';
foreach (['premier-league','championship','league-one','league-two','national-league','division-one'] as $league) {
    $currentLeague = $league;
    foreach (['table.php','table2.php'] as $filename) {
        $currentSubTab = $filename === 'table.php' ? 'table' : 'table-2';
        ob_start(); include __DIR__ . '/../tabs/' . $league . '/2025-2026/' . $filename; $html = ob_get_clean();
        if (!str_contains($html, '--season-zone-color: #123456') || !str_contains($html, 'Configured finish (1)')) {
            throw new RuntimeException("$league/$filename must render saved zone styling and labels.");
        }
        if ($league === 'championship' && $filename === 'table2.php' && !str_contains($html, 'table_filter=q4')) throw new RuntimeException('Championship displays quarter filter controls.');
        if ($games_remaining !== 32) throw new RuntimeException("$league/$filename must use configured games remaining.");
        if ($filename === 'table2.php' && !str_contains($html, '(44)')) throw new RuntimeException("$league/Table 2 must display the configured points target.");
    }
}
restore_error_handler();
echo "Configured table view tests passed.\n";
