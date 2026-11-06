<?php
/** national-league/2025-2026: Configures the competition for the shared side-by-side season comparison. */
$comparisonLeague = [
    'code' => 'NL',
    'name' => 'National League',
    'live_table' => 'league_table_NL',
];
require dirname(__DIR__, 3) . '/includes/season-comparison.php';
