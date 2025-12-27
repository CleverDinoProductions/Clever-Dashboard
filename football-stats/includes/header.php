<?php
// Get current tab from URL, default to premier-league
$currentMainTab = isset($_GET['tab']) ? $_GET['tab'] : 'premier-league';
$currentSubTab = isset($_GET['subtab']) ? $_GET['subtab'] : 'table';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Football Stats Dashboard - <?php echo ucfirst(str_replace('-', ' ', $currentMainTab)); ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="site-container">
        <!-- Header Section -->
        <header class="site-header">
            <div class="header-content">
                <h1 class="site-title">⚽ Football Stats Tracker</h1>
                <p class="site-subtitle">Premier League & World Cup Analytics</p>
            </div>
        </header>
        
        <!-- Info Bar (like CleverLounge) -->
        <div class="info-bar">
            Live data • Auto-refresh every 60s • <?php echo date('Y-m-d H:i:s'); ?> UTC
        </div>
        
        <!-- Main Navigation Pills -->
        <nav class="pill-nav main-pills">
            <a href="?tab=premier-league&subtab=table" 
               class="pill-tab <?php echo ($currentMainTab == 'premier-league') ? 'active' : ''; ?>">
                🏴󠁧󠁢󠁥󠁮󠁧󠁿 Premier League
            </a>
            <a href="?tab=world-cup&subtab=groups" 
               class="pill-tab <?php echo ($currentMainTab == 'world-cup') ? 'active' : ''; ?>">
                🌍 World Cup 2026
            </a>
        </nav>
        
        <!-- Sub Navigation Pills -->
        <nav class="pill-nav sub-pills">
            <?php if ($currentMainTab == 'premier-league'): ?>
                <a href="?tab=premier-league&subtab=table" 
                   class="pill-tab <?php echo ($currentSubTab == 'table') ? 'active' : ''; ?>">
                    📊 Table
                </a>
                <a href="?tab=premier-league&subtab=blocks-overview" 
                   class="pill-tab <?php echo (strpos($currentSubTab, 'blocks') === 0) ? 'active' : ''; ?>">
                    🔢 Blocks of 4
                </a>
                <a href="?tab=premier-league&subtab=relegation" 
                   class="pill-tab <?php echo ($currentSubTab == 'relegation') ? 'active' : ''; ?>">
                    ⚠️ Relegation Battle
                </a>
                <a href="?tab=premier-league&subtab=leeds" 
                   class="pill-tab <?php echo ($currentSubTab == 'leeds') ? 'active' : ''; ?>">
                    💛 Leeds Tracker
                </a>
                <a href="?tab=premier-league&subtab=leeds-2" 
                   class="pill-tab <?php echo ($currentSubTab == 'leeds-2') ? 'active' : ''; ?>">
                    💙 Leeds Dashboard
                </a>
                <a href="?tab=premier-league&subtab=leeds-3" 
                   class="pill-tab <?php echo ($currentSubTab == 'leeds-3') ? 'active' : ''; ?>">
                    🤍 Leeds Classic
                </a>
            <?php elseif ($currentMainTab == 'world-cup'): ?>
                <a href="?tab=world-cup&subtab=groups" 
                   class="pill-tab <?php echo ($currentSubTab == 'groups') ? 'active' : ''; ?>">
                    🔢 Groups
                </a>
                <a href="?tab=world-cup&subtab=third-place" 
                   class="pill-tab <?php echo ($currentSubTab == 'third-place') ? 'active' : ''; ?>">
                    📊 Third-Place Rankings
                </a>
                <a href="?tab=world-cup&subtab=knockout" 
                   class="pill-tab <?php echo ($currentSubTab == 'knockout') ? 'active' : ''; ?>">
                    ⚔️ Knockout Stage
                </a>
                <a href="?tab=world-cup&subtab=predictions"
                    class="pill-tab <?php echo ($currentSubTab == 'predictions') ? 'active' : ''; ?>">
                    🔮 Predictions
                </a>
                <a href="?tab=world-cup&subtab=standings" 
                   class="pill-tab <?php echo ($currentSubTab == 'standings') ? 'active' : ''; ?>">
                    🏆 Overall Rankings
                </a>
            <?php endif; ?>
        </nav>
        
        <?php if ($currentMainTab == 'premier-league' && strpos($currentSubTab, 'blocks') === 0): ?>
        <!-- Blocks Sub-Navigation (only shows when in blocks section) -->
        <nav class="pill-nav tertiary-pills">
            <a href="?tab=premier-league&subtab=blocks-overview" 
               class="pill-tab-small <?php echo ($currentSubTab == 'blocks-overview') ? 'active' : ''; ?>">
                🎯 Overview
            </a>
            <a href="?tab=premier-league&subtab=blocks-dynamic" 
               class="pill-tab-small <?php echo ($currentSubTab == 'blocks-dynamic') ? 'active' : ''; ?>">
                🔥 Live Data
            </a>
            <a href="?tab=premier-league&subtab=blocks-1" 
               class="pill-tab-small <?php echo ($currentSubTab == 'blocks-1') ? 'active' : ''; ?>">
                🏆 Block 1
            </a>
            <a href="?tab=premier-league&subtab=blocks-2" 
               class="pill-tab-small <?php echo ($currentSubTab == 'blocks-2') ? 'active' : ''; ?>">
                🌟 Block 2
            </a>
            <a href="?tab=premier-league&subtab=blocks-3" 
               class="pill-tab-small <?php echo ($currentSubTab == 'blocks-3') ? 'active' : ''; ?>">
                ✅ Block 3
            </a>
            <a href="?tab=premier-league&subtab=blocks-4" 
               class="pill-tab-small <?php echo ($currentSubTab == 'blocks-4') ? 'active' : ''; ?>">
                ⚠️ Block 4
            </a>
            <a href="?tab=premier-league&subtab=blocks-5" 
               class="pill-tab-small <?php echo ($currentSubTab == 'blocks-5') ? 'active' : ''; ?>">
                🔻 Block 5
            </a>
        </nav>
        <?php endif; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">