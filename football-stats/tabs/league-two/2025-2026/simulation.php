<?php
/** league-two/2025-2026: Prepares competition inputs and renders projected tournament or league outcomes. */
require_once __DIR__ . '/../../../includes/simulation-config.php';
$league_config = football_stats_simulation_config($db, 'L2', $currentMainTab ?? '2025-2026', 'League Two');
require_once __DIR__ . '/../../../includes/simulation-view.php';
