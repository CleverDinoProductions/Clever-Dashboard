<?php
// Competition context; all tracker panels share one implementation.
$trackerCode = $regionalCode ?? 'NL';
$trackerTable = $regionalTable ?? 'league_table_NL';
$trackerName = $regionalName ?? 'National League';
$trackerDefaultTeam = null;
include dirname(__DIR__, 3) . '/includes/team-tracker-view.php';
