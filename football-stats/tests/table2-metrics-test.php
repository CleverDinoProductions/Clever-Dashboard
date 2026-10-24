<?php
require_once __DIR__ . '/../includes/table2-metrics.php';

function assert_table2($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ': ' . var_export($actual, true));
    }
}

$scoring = ['win' => 3, 'draw' => 1, 'loss' => 0];
$metrics = football_stats_table2_metrics(['played' => 7, 'points' => 1], 46, $scoring, [38, 40]);
assert_table2(7.0, $metrics['projection'], 'Forecasts must use unrounded PPG');
assert_table2(39, $metrics['remaining'], 'Remaining matches follow the period length');

$metrics = football_stats_table2_metrics(['played' => 0, 'points' => 0], 38, $scoring, [38, 40]);
assert_table2(null, $metrics['ppg'], 'A team with no games has no measured PPG');
assert_table2(null, $metrics['projection'], 'A team with no games has no pace forecast');
assert_table2(114.0, $metrics['wins'], 'Pre-season scenarios remain available');

$metrics = football_stats_table2_metrics(['played' => 38, 'points' => 39], 38, $scoring, [38, 40]);
assert_table2('Reached', $metrics['targets'][0]['status'], 'Reached targets must be explicit');
assert_table2('Out of reach', $metrics['targets'][1]['status'], 'A missed target at season end must not show zero required PPG');
assert_table2(39.0, $metrics['projection'], 'Finished seasons project the actual points');
assert_table2(null, $metrics['targets'][1]['ppg'], 'No division by zero at season end');

$metrics = football_stats_table2_metrics(['played' => 9, 'points' => 10], 10, ['win' => 2, 'draw' => 4, 'loss' => 0], [14, 15]);
assert_table2('In reach', $metrics['targets'][0]['status'], 'Reachability must use the highest active result points');
assert_table2('Out of reach', $metrics['targets'][1]['status'], 'Targets above the scoring ceiling must be explicit');
assert_table2(12.0, $metrics['wins'], 'Win scenarios must use configured scoring');
assert_table2(14.0, $metrics['draws'], 'Draw scenarios must use configured scoring');
assert_table2(13.0, $metrics['mixed'], 'Mixed scenarios use a rounded estimate for odd remaining games');

$metrics = football_stats_table2_metrics(['played' => 12, 'points' => -2], 10, $scoring, [0, 10]);
assert_table2(0, $metrics['remaining'], 'Overplayed periods must not have negative games remaining');
assert_table2('Out of reach', $metrics['targets'][0]['status'], 'Negative points must retain the target gap');
echo "Table 2 metric tests passed.\n";
