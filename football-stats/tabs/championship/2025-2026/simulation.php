<?php
/** championship/2025-2026: Prepares competition inputs and renders projected tournament or league outcomes. */
require_once __DIR__ . '/../../../includes/simulation-config.php';
$league_config = football_stats_simulation_config($db, 'ELC', $currentMainTab ?? '2025-2026', 'Championship');
require_once __DIR__ . '/../../../includes/simulation-view.php';
