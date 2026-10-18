<?php
// Competition context; all tracker panels share one implementation.
$trackerCode = 'PL';
$trackerTable = 'league_table_PL';
$trackerName = 'Premier League';
$trackerDefaultTeam = 'Leeds United';
include dirname(__DIR__, 3) . '/includes/team-tracker-view.php';
