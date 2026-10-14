<?php
$comparisonLeague = [
    'code' => $regionalCode,
    'name' => $regionalName,
    'live_table' => $regionalTable,
];
require dirname(__DIR__, 3) . '/includes/season-comparison.php';
