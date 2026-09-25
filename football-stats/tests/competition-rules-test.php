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
rules_assert('relegation', football_stats_get_position_zone('L2', '2025-2026', 24)['key'], 'League Two has one relegation place.');

echo "Competition rule tests passed.\n";
