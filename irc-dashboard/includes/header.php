<!DOCTYPE html>
<html>
<head>
    <title>MAM IRC Analytics Dashboard</title>
    <meta http-equiv="refresh" content="60">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="header">
        <h1>🔍 IRC Analytics Dashboard</h1>
        <div class="subtitle">Live data from CleverLounge • Auto-refresh every 60s • <?= date('Y-m-d H:i:s T') ?></div>
        <div class="tabs">
            <a href="?tab=general" class="tab <?= $tab == 'general' ? 'active' : '' ?>">📊 General Stats</a>
            <a href="?tab=channels" class="tab <?= $tab == 'channels' ? 'active' : '' ?>">💬 Channel Stats</a>
            <a href="?tab=interviews" class="tab <?= $tab == 'interviews' ? 'active' : '' ?>">📝 Interview Tracking</a>
            <a href="?tab=invitations" class="tab <?= $tab == 'invitations' ? 'active' : '' ?>">🧀 Successful Invitation Tracking</a>
            <a href="?tab=interview-history" class="tab <?= $tab == 'interview-history' ? 'active' : '' ?>">📋 Interview History</a>
            <a href="?tab=invitation-history" class="tab <?= $tab == 'invitation-history' ? 'active' : '' ?>">📬 Invitation History</a>
            <a href="?tab=windows" class="tab <?= $tab == 'windows' ? 'active' : '' ?>">🎫 Invite Windows</a>
            <a href="?tab=timezones" class="tab <?= $tab == 'timezones' ? 'active' : '' ?>">🌍 Timezones</a>
        </div>
    </div>
    
    <div class="container">
