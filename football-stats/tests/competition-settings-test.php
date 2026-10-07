<?php
require_once __DIR__ . '/../includes/table-view.php';

function settings_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function settings_input(array $rules): array {
    $rules['quarter_boundaries'] = implode(',', $rules['quarter_boundaries']);
    foreach ($rules['zones'] as &$zone) $zone['color'] ??= football_stats_table_zone_colors($zone)[0];
    return $rules;
}
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
settings_assert(clever_competition_rules($db, 'PL', '2025-2026')['zones'][0]['to'] === 5, 'Unmigrated databases keep bundled season rules.');
clever_migrate_football_settings($db);
clever_migrate_football_settings($db);
$defaults = settings_input(clever_competition_rules($db, 'ELC'));
$defaults['zones'][1]['to'] = 8;
clever_save_competition_rules($db, 'ELC', '', $defaults);
settings_assert(football_stats_get_position_zone('ELC', '2026-2027', 8)['key'] === 'playoffs', 'League defaults apply to seasons without overrides.');
$season = $defaults;
$season['team_count'] = 22;
$season['regular_matchweeks'] = 42;
$season['total_games'] = 42;
$season['halfway_games'] = 21;
$season['quarter_boundaries'] = '11,21,32';
$season['comparison_target_one'] = 44;
$season['comparison_target_two'] = 48;
$season['zones'] = [['key'=>'playoffs', 'label'=>'Expanded playoffs', 'from'=>3, 'to'=>10, 'color'=>'#123456']];
clever_save_competition_rules($db, 'ELC', '2026-2027', $season);
settings_assert(football_stats_get_final_matchweek('ELC', '2026-2027', $db) === 42, 'Season matchweek limits come from saved settings.');
settings_assert(football_stats_limit_matchweeks_to_regular_season([0, 1, 42, 43, 46], 'ELC', '2026-2027') === [1, 42], 'Selectors exclude postseason matchweeks with the selected season limit.');
settings_assert(football_stats_get_final_matchweek('ELC', '2025-2026', $db) === 46, 'Another season retains its own format.');
settings_assert(football_stats_get_final_matchweek('PL', '2026-2027', $db) === 38, 'Another competition stays independent.');
$attributes = football_stats_table_row_attributes('ELC', '2026-2027', 8);
settings_assert(str_contains($attributes, '--default-zone-color: #5865F2') && str_contains($attributes, '--season-zone-color: #123456'), 'Rows distinguish default and season colours.');
settings_assert(str_contains($attributes, 'rgba(18, 52, 86, 0.15)'), 'Saved colour drives season fill.');
ob_start(); football_stats_render_table_zone_legend('ELC', '2026-2027'); $legend = ob_get_clean();
settings_assert(str_contains($legend, 'Expanded playoffs (3–10)'), 'Legends use saved labels and position ranges.');
foreach (['overlap', 'range', 'colour', 'quarter', 'number', 'halfway', 'target', 'label'] as $invalid) {
    $bad = $season;
    switch ($invalid) {
        case 'overlap': $bad['zones'][] = ['key'=>'custom','label'=>'Overlap','from'=>8,'to'=>12,'color'=>'#ABCDEF']; break;
        case 'range': $bad['zones'][0]['to'] = 23; break;
        case 'colour': $bad['zones'][0]['color'] = 'red; background:url(x)'; break;
        case 'quarter': $bad['quarter_boundaries'] = '11,11,42'; break;
        case 'number': $bad['total_games'] = '42.5'; break;
        case 'halfway': $bad['halfway_games'] = 42; break;
        case 'target': $bad['comparison_target_one'] = 127; break;
        case 'label': $bad['zones'][0]['label'] = ''; break;
    }
    try { clever_save_competition_rules($db, 'ELC', '2026-2027', $bad); throw new RuntimeException("Accepted invalid $invalid"); }
    catch (InvalidArgumentException $expected) {}
    settings_assert(clever_competition_rules($db, 'ELC', '2026-2027')['comparison_target_one'] === 44, 'Invalid saves preserve the complete prior rule set.');
}
foreach ([['INVALID','2026-2027'], ['ELC','2026-2028'], ['ELC','2026-2027<script>']] as [$code,$label]) {
    try { clever_save_competition_rules($db, $code, $label, $season); throw new RuntimeException('Accepted invalid identity'); }
    catch (InvalidArgumentException $expected) {}
}
$empty = $season; $empty['zones'] = [];
clever_save_competition_rules($db, 'ELC', '2026-2027', $empty);
settings_assert(football_stats_get_position_zone('ELC', '2026-2027', 3) === null, 'An empty zone list removes all season highlights.');
clever_reset_competition_rules($db, 'ELC', '2026-2027');
settings_assert(football_stats_get_position_zone('ELC', '2026-2027', 8)['key'] === 'playoffs', 'Reset restores inherited default rules.');
clever_reset_competition_rules($db, 'ELC', '');
settings_assert(football_stats_get_position_zone('ELC', '2026-2027', 8) === null, 'Reset of league defaults restores bundled rules.');
$pl = settings_input(clever_competition_rules($db, 'PL', '2025-2026')); $pl['zones'] = [];
clever_save_competition_rules($db, 'PL', '2025-2026', $pl);
clever_reset_competition_rules($db, 'PL', '2025-2026');
settings_assert(football_stats_get_position_zone('PL', '2025-2026', 5)['key'] === 'champions-league', 'Reset restores bundled season exceptions.');

// Quarter filters use saved boundaries and reject postseason results.
clever_save_competition_rules($db, 'ELC', '2026-2027', $season);
$db->exec('CREATE TABLE matches (id INTEGER PRIMARY KEY, competition_code TEXT, season_label TEXT, matchweek INTEGER, match_date TEXT, match_timestamp TEXT, home_team TEXT, away_team TEXT, home_goals INTEGER, away_goals INTEGER)');
$db->exec('CREATE TABLE league_table_ELC (team_name TEXT, team_crest TEXT)');
$stmt = $db->prepare("INSERT INTO matches VALUES (?, 'ELC', '2026-2027', ?, '2026-08-01', '', 'Leeds United', 'Test FC', 1, 0)");
foreach ([11, 12, 21, 22, 32, 33, 42, 43] as $index => $week) $stmt->execute([$index + 1, $week]);
foreach (['q1'=>1, 'q2'=>2, 'q3'=>2, 'q4'=>2] as $quarter=>$played) {
    $rows = football_stats_compute_filtered_standings($db, 'ELC', '2026-2027', $quarter, 21, 'league_table_ELC');
    settings_assert($rows[0]['played'] === $played, "$quarter uses saved quarter boundaries.");
}
$rows = football_stats_compute_custom_match_standings($db, 'ELC', '2026-2027', 'league_table_ELC', []);
settings_assert($rows[0]['played'] === 7, 'Custom result tables use the selected season cutoff.');
echo "Competition settings tests passed.\n";
