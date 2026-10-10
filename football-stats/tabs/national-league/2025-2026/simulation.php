<?php
require_once __DIR__ . '/../../../includes/simulation-config.php';
$league_config = football_stats_simulation_config($db, 'NL', $currentMainTab ?? '2025-2026', 'National League');
require_once __DIR__ . '/../../../includes/simulation-view.php';
