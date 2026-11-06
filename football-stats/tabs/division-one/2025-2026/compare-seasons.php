<?php
/** division-one/2025-2026: Configures the competition for the shared side-by-side season comparison. */
$comparisonLeague = [
    'code' => 'D1',
    'name' => 'Division One',
    'live_table' => 'league_table_D1',
];
require dirname(__DIR__, 3) . '/includes/season-comparison.php';
