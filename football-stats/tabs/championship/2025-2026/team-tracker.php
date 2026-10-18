<?php
// Competition context; all tracker panels share one implementation.
$trackerCode = 'ELC';
$trackerTable = 'league_table_ELC';
$trackerName = 'Championship';
$trackerDefaultTeam = 'Leeds United';
include dirname(__DIR__, 3) . '/includes/team-tracker-view.php';
