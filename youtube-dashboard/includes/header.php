<!DOCTYPE html>
<html>
<head>
    <title>YouTube RSS Analytics Dashboard</title>
    <meta http-equiv="refresh" content="300">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="header">
        <h1>📺 YouTube RSS Analytics Dashboard</h1>
        <div class="subtitle">RSS Feed Monitoring • No API Quota • Auto-refresh every 5 min • <?= date('Y-m-d H:i:s T') ?></div>
        <div class="tabs">
            <a href="?tab=timeline" class="tab <?= $tab == 'timeline' ? 'active' : '' ?>">📅 Upload Timeline</a>
            <a href="?tab=channels" class="tab <?= $tab == 'channels' ? 'active' : '' ?>">📺 Channel Overview</a>
            <a href="?tab=analytics" class="tab <?= $tab == 'analytics' ? 'active' : '' ?>">📊 Analytics</a>
            <a href="?tab=gaps" class="tab <?= $tab == 'gaps' ? 'active' : '' ?>">⏱️ Upload Gaps</a>
            <a href="?tab=locations" class="tab <?= $tab == 'locations' ? 'active' : '' ?>">🌍 Location Tracking</a>
            <a href="?tab=frequency" class="tab <?= $tab == 'frequency' ? 'active' : '' ?>">📈 Upload Frequency</a>
            <a href="?tab=trends" class="tab <?= $tab == 'trends' ? 'active' : '' ?>">📉 Historical Trends</a>
            <a href="?tab=search" class="tab <?= $tab == 'search' ? 'active' : '' ?>">🔍 Search Videos</a>
        </div>
    </div>
    
    <div class="container">
