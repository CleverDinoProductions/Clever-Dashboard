<?php
$navigation = ['timeline' => 'Upload timeline', 'channels' => 'Channels', 'analytics' => 'Analytics', 'gaps' => 'Upload gaps', 'locations' => 'Locations', 'frequency' => 'Frequency', 'trends' => 'Trends', 'search' => 'Search videos'];
$lastChecked = getValue("SELECT MIN(last_checked) FROM channels");
$checkedTimestamp = $lastChecked ? strtotime($lastChecked . (preg_match('/(?:Z|[+-]\d{2}:\d{2})$/', $lastChecked) ? '' : ' UTC')) : false;
$isStale = !$checkedTimestamp || time() - $checkedTimestamp > 86400;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $navigation[$tab] ?> · YouTube Monitor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="/assets/dashboard-shell.css">
    <link rel="stylesheet" href="assets/football-theme.css">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <nav class="dashboard-account-bar" aria-label="Site"><a href="/">← All dashboards</a><a href="/account/">My account</a></nav>
    <header class="site-header">
        <h1 class="site-title">▶ YouTube Monitor</h1>
        <p class="site-subtitle">Your channels. Every upload. One place.</p>
    </header>
    <nav class="pill-nav" aria-label="Dashboards">
        <a class="pill-tab" href="/">Home</a>
        <a class="pill-tab" href="/football-stats/">⚽ Football Stats</a>
        <a class="pill-tab active" href="/youtube-dashboard/" aria-current="true">▶ YouTube Monitor</a>
        <a class="pill-tab" href="/irc-dashboard/">💬 IRC Activity</a>
    </nav>
    <nav class="pill-nav sub-pills" aria-label="YouTube views">
        <?php foreach ($navigation as $key => $label): ?>
            <a href="?tab=<?= $key ?>" class="pill-tab <?= $tab === $key ? 'active' : '' ?>" <?= $tab === $key ? 'aria-current="page"' : '' ?>><?= $label ?></a>
        <?php endforeach; ?>
    </nav>
    <main id="main-content" class="container">
        <div class="feed-status <?= $isStale ? 'stale' : '' ?>"><span><strong><?= $isStale ? 'Feed check overdue' : 'Feeds checked recently' ?></strong> · <?= $checkedTimestamp ? 'Oldest channel check: ' . date('j M Y, H:i T', $checkedTimestamp) : 'No recorded feed checks' ?></span><span>Refresh imports with the scheduled fetcher</span></div>
