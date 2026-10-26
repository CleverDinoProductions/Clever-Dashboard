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

// Shared channel settings, editable in the administration panel.
require_once dirname(__DIR__) . '/lib/youtube-settings.php';
$CHANNELS = clever_youtube_channels();
