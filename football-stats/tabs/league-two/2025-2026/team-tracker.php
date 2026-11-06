<?php
/** league-two/2025-2026: Supplies competition context to the shared team-tracker implementation. */
// Competition context; all tracker panels share one implementation.
$trackerCode = 'L2';
$trackerTable = 'league_table_L2';
$trackerName = 'League Two';
$trackerDefaultTeam = null;
include dirname(__DIR__, 3) . '/includes/team-tracker-view.php';
