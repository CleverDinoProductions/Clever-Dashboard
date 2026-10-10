<?php
require_once __DIR__ . '/../includes/simulation-config.php';
require_once __DIR__ . '/../includes/match-projection-widget.php';
function simulation_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
clever_migrate_football_settings($db);
$teams = [];
for ($i = 1; $i <= 24; $i++) $teams[] = ['team' => 'Club ' . $i, 'current_points' => 40,
    'avg_final_points' => 100.2 - $i * 2, 'avg_final_position' => $i];
$simulation = ['teams' => array_reverse($teams), 'n_sims' => 1000];
foreach (clever_competitions() as $code => $name) {
    $config = football_stats_simulation_config($db, $code, '2025-2026', $name);
    simulation_assert($config['comp_code'] === $code, 'Each league uses its own competition.');
    for ($position = 1; $position <= $config['n_teams']; $position++) {
        $cover = array_filter($config['zones'], static fn($z) => $position >= $z['from'] && $position <= $z['to']);
        simulation_assert(count($cover) === 1, 'Distribution zones partition the table without overlapping title/safe ranges.');
    }
    $outlook = match_projection_parse($simulation, $config['zones']);
    simulation_assert($outlook !== null, 'All leagues have an outlook, including those without zones.');
    if (in_array($code, ['ELC', 'L1', 'L2', 'NL'], true)) {
        simulation_assert(array_column($outlook['targets'], 'key') === ['automatic-promotion', 'playoffs', 'safety'], 'Lower leagues display promotion, playoffs and safety.');
    }
    ob_start(); match_projection_render($simulation, $config['zones']); $html = ob_get_clean();
    simulation_assert(str_contains($html, 'Simulation outlook'), 'Each league renders its outlook.');
}
$pl = football_stats_simulation_config($db, 'PL', '2025-2026', 'Premier League');
simulation_assert($pl['prob_columns'][1]['to'] === 5, 'PL simulation uses bundled season-specific qualification places.');
$rules = clever_competition_rules($db, 'ELC');
$rules['quarter_boundaries'] = implode(',', $rules['quarter_boundaries']);
$rules['zones'] = [
    ['key'=>'automatic-promotion', 'label'=>'Direct ascent', 'from'=>1, 'to'=>3, 'color'=>'#123456'],
    ['key'=>'playoffs', 'label'=>'<Final round>', 'from'=>4, 'to'=>8, 'color'=>'#654321'],
    ['key'=>'relegation', 'label'=>'Drop', 'from'=>20, 'to'=>24, 'color'=>'#112233'],
];
clever_save_competition_rules($db, 'ELC', '2026-2027', $rules);
$config = football_stats_simulation_config($db, 'ELC', '2026-2027', 'Championship');
simulation_assert($config['prob_columns'][2]['to'] === 8 && $config['zones'][0]['color'] === '#123456', 'Saved cutoffs and colours drive simulation probabilities and zones.');
$outlook = match_projection_parse($simulation, $config['zones']);
simulation_assert(array_column($outlook['targets'], 'position') === [3, 8, 19], 'Renamed zones still produce the correct semantic targets.');
simulation_assert($outlook['targets'][0]['target'] === 95, 'Points targets round upward and use sorted projected positions.');
simulation_assert($outlook['rows'][0]['points_needed'][0] === 55, 'Points needed use the current points.');
ob_start(); match_projection_render($simulation, $config['zones']); $html = ob_get_clean();
simulation_assert(str_contains($html, '&lt;Final round&gt;') && !str_contains($html, '<Final round>'), 'Admin labels are escaped.');
simulation_assert(football_stats_simulation_config($db, 'ELC', '2025-2026', 'Championship')['prob_columns'][2]['to'] === 6, 'Season overrides remain isolated.');
$rules['zones'] = [];
clever_save_competition_rules($db, 'ELC', '2026-2027', $rules);
$config = football_stats_simulation_config($db, 'ELC', '2026-2027', 'Championship');
simulation_assert(count($config['prob_columns']) === 1 && match_projection_parse($simulation, $config['zones'])['targets'] === [], 'Removing zones removes stale probabilities and targets.');
simulation_assert(match_projection_parse(['teams'=>[]], []) === null, 'Empty simulations have no outlook.');
$short = ['teams' => array_slice($teams, 0, 2)];
simulation_assert(match_projection_parse($short, [['key'=>'playoffs','name'=>'Playoffs','from'=>3,'to'=>6]])['targets'] === [], 'Unavailable cut lines are not clamped to another team.');
echo "Simulation configuration and outlook tests passed.\n";
