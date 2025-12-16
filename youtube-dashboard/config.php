<?php
// YouTube Dashboard Configuration

// Database settings
define('DB_PATH', __DIR__ . '/youtube-dashboard.sqlite3');

// Timezone
date_default_timezone_set('Europe/London');

// Display settings
define('VIDEOS_PER_PAGE', 100);
define('CRITICAL_GAP_DAYS', 14);
define('WARNING_GAP_DAYS', 7);

// Channels to monitor (also used by Python)
$CHANNELS = [
    'Family Fun Pack' => 'UCvthuVsurPaVz2a7_4LepGg',
    "Theme Park Worldwide (TPWW)" => 'UCwqDAwhG1JCxTNr6HnB7gnQ',
];
?>
