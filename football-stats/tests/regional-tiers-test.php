<?php
require_once __DIR__ . '/../includes/table-view.php';
require_once __DIR__ . '/../includes/grouped-navigation.php';
require_once __DIR__ . '/../includes/simulation-config.php';
function regional_check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$leagues = clever_regional_competitions();
regional_check(array_count_values(array_column($leagues, 'tier')) === [6=>2, 7=>4, 8=>8], 'Include all fourteen divisions in the correct tiers.');
foreach (['premier-league'=>'[Tier 1]', 'championship'=>'[Tiers 2–4]', 'national-league'=>'[Tier 5]'] as $slug=>$tierLabel) {
    regional_check(str_contains(football_stats_navigation_league_group($slug),$tierLabel), 'Include bracketed tiers in existing league section headers.');
}
$source = file_get_contents(__DIR__ . '/../index.php');
$start = strpos($source, '$seasonLeagueConfigs =');
$end = strpos($source, '$hasExplicitDestination', $start);
eval(substr($source, $start, $end - $start));
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
clever_migrate_football_settings($db);
foreach ($leagues as $slug => $regional) {
    $code = $regional['code'];
    regional_check(clever_regional_league_codes()[$slug] === $code, 'Keep division slug/code mappings consistent.');
    regional_check(clever_competitions()[$code] === $regional['label'], 'Expose divisions to Admin.');
    regional_check(str_contains(football_stats_navigation_league_group($slug), '[Tier ' . $regional['tier'] . ']'), 'Group by tier.');
    foreach (['2025-2026', '2026-2027'] as $season) {
        $tabs = $seasonLeagueConfigs[$season][$slug]['tabs'];
        regional_check(array_keys($tabs) === array_keys($seasonLeagueConfigs['2025-2026']['national-league']['tabs']), 'Expose every National League tool for both seasons.');
        foreach ($tabs as $tab) regional_check(is_file(__DIR__ . '/../' . $tab['file']), 'Every route must exist.');
        $rules = clever_competition_rules($db, $code, $season);
        $count = $regional['tier'] === 6 ? 24 : 22;
        regional_check($rules['team_count'] === $count && $rules['total_games'] === 2 * ($count - 1), 'Use regional team/game counts.');
        regional_check($rules['zones'][1]['to'] === ($regional['tier'] === 6 ? 7 : 5), 'Use the correct playoff positions.');
        $simulation = football_stats_simulation_config($db, $code, $season, $regional['label']);
        regional_check($simulation['comp_code'] === $code && $simulation['total_games'] === $rules['total_games'], 'Simulations must use their own division rules.');
    }
    $currentLeague = $slug;
    $currentMainTab = '2025-2026';
    $currentSubTab = 'table';
    ob_start();
    include __DIR__ . '/../tabs/shared/regional.php';
    $html = ob_get_clean();
    regional_check(str_contains($html, $regional['label']) && str_contains($html, 'No data has been imported'), 'Missing data must preserve division context with a clear empty state.');
}
// A division-specific override must not affect its neighbour or another season.
$db->prepare('INSERT INTO competition_rule_overrides VALUES (?, ?, ?)')->execute(['NPLE', '2026-2027', json_encode(['team_count'=>20, 'regular_matchweeks'=>38, 'total_games'=>38])]);
regional_check(clever_competition_rules($db, 'NPLE', '2026-2027')['total_games'] === 38, 'Apply season-specific Admin overrides.');
regional_check(clever_competition_rules($db, 'NPLW', '2026-2027')['total_games'] === 42, 'Keep neighbouring divisions independent.');
regional_check(clever_competition_rules($db, 'NPLE', '2025-2026')['total_games'] === 42, 'Keep seasons independent.');
echo "Regional tier tests passed.\n";
