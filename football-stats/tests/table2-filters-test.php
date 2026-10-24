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
$leagues = [
    'premier-league' => 'PL', 'championship' => 'ELC', 'league-one' => 'L1',
    'league-two' => 'L2', 'national-league' => 'NL', 'division-one' => 'D1',
    'regional' => 'NLN',
];
foreach ($leagues as $code) {
    $db->exec('CREATE TABLE league_table_' . $code . ' (' . $columns . ')');
    $db->exec("INSERT INTO league_table_$code VALUES ('', 'Leeds United', 1, 10, 5, 2, 3, 12, 10, 2, 17, 0)");
    $db->prepare("INSERT INTO live_table_metadata VALUES (?, '2025-2026', 10, 0)")->execute([$code]);
    $db->prepare("INSERT INTO matches (competition_code, season_label, matchweek, home_team, away_team, home_goals, away_goals) VALUES (?, '2025-2026', 1, 'Leeds United', 'Burnley', 2, 0)")->execute([$code]);
}
$currentMainTab = '2025-2026';
$currentSubTab = 'table-2';
$regionalCode = 'NLN';
$regionalTable = 'league_table_NLN';
$regionalName = 'National League North';
foreach ($leagues as $league => $code) {
    $currentLeague = $league;
    $filters = ['all', 'first_half', 'second_half', 'home', 'away'];
    if ($league === 'championship') $filters = array_merge($filters, ['q1', 'q2', 'q3', 'q4']);
    foreach ($filters as $filter) {
        $_GET = ['table_filter' => $filter];
        ob_start();
        include __DIR__ . '/../tabs/' . $league . '/2025-2026/table2.php';
        $html = ob_get_clean();
        $shouldBeEmpty = in_array($filter, ['second_half', 'q2', 'q3', 'q4'], true);
        if ($shouldBeEmpty !== empty($standings)) {
            throw new RuntimeException("$league/$filter must use only the selected period's standings.");
        }
        if ($shouldBeEmpty !== str_contains($html, 'No completed matches available for this selection.')) {
            throw new RuntimeException("$league/$filter must show an empty state only when no standings exist.");
        }
        if (!$shouldBeEmpty) {
            $expectedPoints = $filter === 'all' ? 17 : ($filter === 'away' ? 0 : 3);
            $pointsByTeam = array_column($standings, 'points', 'team_name');
            if ($pointsByTeam['Leeds United'] !== $expectedPoints) {
                throw new RuntimeException("$league/$filter must retain the correct points for populated selections.");
            }
        }
    }
}
restore_error_handler();
echo "Table 2 filter rendering tests passed.\n";
