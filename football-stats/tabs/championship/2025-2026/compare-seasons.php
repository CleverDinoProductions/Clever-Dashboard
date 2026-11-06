<?php
/** championship/2025-2026: Configures the competition for the shared side-by-side season comparison. */
$comparisonLeague = [
    'code' => 'ELC',
    'name' => 'Championship',
    'live_table' => 'league_table_ELC',
];
require dirname(__DIR__, 3) . '/includes/season-comparison.php';
