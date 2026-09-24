<?php
$comparisonLeague = [
    'code' => 'PL',
    'name' => 'Premier League',
    'live_table' => 'league_table_PL',
    'total_games' => 38,
    'halfway_games' => 19,
];
require dirname(__DIR__, 3) . '/includes/season-comparison.php';
