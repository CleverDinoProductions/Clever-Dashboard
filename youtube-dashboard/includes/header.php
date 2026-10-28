<?php
$navigation = ['timeline' => 'Upload timeline', 'channels' => 'Channels', 'analytics' => 'Analytics', 'gaps' => 'Upload gaps', 'locations' => 'Locations', 'frequency' => 'Frequency', 'trends' => 'Trends', 'tags' => 'Tags', 'calendar' => 'Upload calendar', 'health' => 'Feed health', 'search' => 'Search videos'];
$lastChecked = getValue("SELECT MIN(last_checked) FROM dashboard_channels");
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
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header class="header">
        <div class="brand"><span class="brand-icon" aria-hidden="true">▶</span><div><h1>YouTube Monitor</h1><p class="subtitle">Your channels. Every upload. One place.</p></div></div>
        <a class="back-link" href="../">← All dashboards</a>
    </header>
    <div class="app-layout">
    <aside class="sidebar">
        <p class="nav-label">WORKSPACE</p>
        <nav class="tabs" aria-label="Dashboard views">
        <?php foreach ($navigation as $key => $label): ?>
            <a href="<?= dashboard_url(['tab' => $key]) ?>" class="tab <?= $tab === $key ? 'active' : '' ?>" <?= $tab === $key ? 'aria-current="page"' : '' ?>><?= $label ?></a>
        <?php endforeach; ?>
        </nav>
        <div class="source-note"><strong>Powered by public RSS</strong><p>Recent uploads, cached locally. No API key required.</p></div>
    </aside>
    <main id="main-content" class="container">
        <div class="feed-status <?= $isStale ? 'stale' : '' ?>"><span><strong><?= $isStale ? 'Feed check overdue' : 'Feeds checked recently' ?></strong> · <?= $checkedTimestamp ? 'Oldest channel check: ' . date('j M Y, H:i T', $checkedTimestamp) : 'No recorded feed checks' ?></span><span>Refresh imports with the scheduled fetcher</span></div>

<?php dashboard_filters(); ?>
