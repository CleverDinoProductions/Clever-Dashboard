<?php
/** league-two/2025-2026: Configures the competition for the shared side-by-side season comparison. */
$comparisonLeague = [
    'code' => 'L2',
    'name' => 'League Two',
    'live_table' => 'league_table_L2',
];
require dirname(__DIR__, 3) . '/includes/season-comparison.php';
