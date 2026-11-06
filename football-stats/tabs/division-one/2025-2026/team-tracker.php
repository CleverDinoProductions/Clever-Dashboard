<?php
/** division-one/2025-2026: Supplies competition context to the shared team-tracker implementation. */
// Competition context; all tracker panels share one implementation.
$trackerCode = 'D1';
$trackerTable = 'league_table_D1';
$trackerName = 'Division One';
$trackerDefaultTeam = 'Leeds United';
include dirname(__DIR__, 3) . '/includes/team-tracker-view.php';
