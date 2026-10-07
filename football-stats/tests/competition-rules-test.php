<?php
require_once __DIR__ . '/../includes/table-view.php';

function rules_assert($expected, $actual, $message) {
    if ($expected !== $actual) {
        fwrite(STDERR, "$message\nExpected " . var_export($expected, true) . '; got ' . var_export($actual, true) . "\n");
        exit(1);
    }
}

rules_assert(38, football_stats_get_final_matchweek('PL', '2025-2026'), 'Premier League has 38 matchweeks.');
rules_assert(46, football_stats_get_final_matchweek('ELC', '2025-2026'), 'Championship has 46 matchweeks.');
rules_assert(20, football_stats_get_competition_rules('PL', '2025-2026')['team_count'], 'Premier League has 20 clubs.');
rules_assert('champions-league', football_stats_get_position_zone('PL', '2025-2026', 5)['key'], 'Fifth qualifies for the Champions League in 2025/26.');
rules_assert('europa-league', football_stats_get_position_zone('PL', '2024-2025', 5)['key'], 'The default PL rules remain season-specific.');
rules_assert('europa-league', football_stats_get_default_position_zone('PL', 5)['key'], 'The default zone remains available when a season changes the placing.');
rules_assert('relegation', football_stats_get_position_zone('L2', '2025-2026', 24)['key'], 'League Two has one relegation place.');

echo "Competition rule tests passed.\n";

// Row presentation must distinguish default places from the selected season.
$attributes = football_stats_table_row_attributes('PL', '2025-2026', 5);
rules_assert(true, strpos($attributes, '--default-zone-color: #5865F2') !== false, 'Fifth retains the default Europa League edge.');
rules_assert(true, strpos($attributes, '--season-zone-color: #006400') !== false, 'Fifth has the selected-season Champions League edge.');
foreach ([['ELC', 2, '#43b581'], ['ELC', 3, '#5865F2'], ['ELC', 7, 'transparent'], ['ELC', 22, '#f04747'],
          ['L1', 21, '#f04747'], ['L2', 3, '#43b581'], ['L2', 7, '#5865F2'], ['L2', 23, 'transparent'],
          ['L2', 24, '#f04747'], ['NL', 1, '#43b581'], ['NL', 2, '#5865F2'], ['NL', 21, '#f04747'], ['D1', 1, 'transparent']] as [$code, $position, $color]) {
    rules_assert(true, strpos(football_stats_table_row_attributes($code, '2025-2026', $position), '--season-zone-color: ' . $color) !== false, "$code position $position uses its configured zone.");
}
ob_start();
football_stats_render_table_zone_legend('ELC', '2025-2026');
$legend = ob_get_clean();
rules_assert(true, strpos($legend, 'Promotion playoffs (3–6)') !== false, 'Championship legend uses configured playoff places.');
rules_assert(false, strpos($legend, 'Leeds') !== false, 'Championship legend has no Leeds override.');
rules_assert(false, strpos($legend, 'Champions League') !== false, 'Championship legend has no copied Premier League places.');
echo "Table zone presentation tests passed.\n";
