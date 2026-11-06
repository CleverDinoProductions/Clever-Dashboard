<?php
/** regional/2025-2026: Configures the competition for the shared side-by-side season comparison. */
$comparisonLeague = [
    'code' => $regionalCode,
    'name' => $regionalName,
    'live_table' => $regionalTable,
];
require dirname(__DIR__, 3) . '/includes/season-comparison.php';
