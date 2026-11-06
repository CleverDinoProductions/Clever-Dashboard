<?php
/** league-one/2025-2026: Configures the competition for the shared side-by-side season comparison. */
$comparisonLeague = [
    'code' => 'L1',
    'name' => 'League One',
    'live_table' => 'league_table_L1',
];
require dirname(__DIR__, 3) . '/includes/season-comparison.php';
