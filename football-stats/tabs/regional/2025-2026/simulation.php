<?php
require_once __DIR__ . '/../../../includes/simulation-config.php';
$league_config = football_stats_simulation_config($db, $regionalCode, $currentMainTab ?? '2025-2026', $regionalName);
require_once __DIR__ . '/../../../includes/simulation-view.php';
