<?php
/** premier-league/2025-2026: Configures the competition for the shared side-by-side season comparison. */
$comparisonLeague = [
    'code' => 'PL',
    'name' => 'Premier League',
    'live_table' => 'league_table_PL',
];
require dirname(__DIR__, 3) . '/includes/season-comparison.php';
