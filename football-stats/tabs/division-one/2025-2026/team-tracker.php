<?php
// Competition context; all tracker panels share one implementation.
$trackerCode = 'D1';
$trackerTable = 'league_table_D1';
$trackerName = 'Division One';
$trackerDefaultTeam = 'Leeds United';
include dirname(__DIR__, 3) . '/includes/team-tracker-view.php';
