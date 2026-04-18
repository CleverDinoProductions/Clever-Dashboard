<?php
require_once __DIR__ . '/table-view.php';

if (!function_exists('build_tab_url')) {
    function build_tab_url($tab, $league = null, $subtab = null, array $extraParams = [])
    {
        $params = ['tab' => $tab];

        if ($league !== null) {
            $params['league'] = $league;
        }

        if ($subtab !== null) {
            $params['subtab'] = $subtab;
        }

        foreach ($extraParams as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $params[$key] = $value;
        }

        return '?' . http_build_query($params);
    }
}

$currentTitle = 'Football Stats Dashboard';

if ($currentMainTab === 'world-cup') {
    $currentTitle .= ' - World Cup 2026';

    if (isset($worldCupTabs[$currentSubTab]['label'])) {
        $currentTitle .= ' - ' . $worldCupTabs[$currentSubTab]['label'];
    }
} else {
    $currentTitle .= ' - ' . ($seasonLabels[$currentMainTab] ?? $currentMainTab);
    $currentTitle .= ' - ' . ($seasonLeagueConfigs[$currentMainTab][$currentLeague]['label'] ?? ucfirst(str_replace('-', ' ', $currentLeague)));

    if (isset($seasonLeagueConfigs[$currentMainTab][$currentLeague]['tabs'][$currentSubTab]['label'])) {
        $currentTitle .= ' - ' . $seasonLeagueConfigs[$currentMainTab][$currentLeague]['tabs'][$currentSubTab]['label'];
    }
}

$isBlocksSection = $currentMainTab !== 'world-cup' && strpos($currentSubTab, 'blocks') === 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($currentTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚽</text></svg>">
</head>
<body>
    <div class="site-container">
        <!-- Header Section -->
        <header class="site-header">
            <div class="header-content">
                <h1 class="site-title">⚽ Football Stats Tracker</h1>
                <p class="site-subtitle">Premier League, Championship & World Cup Analytics</p>
            </div>
        </header>
        
        <!-- Info Bar (like CleverLounge) -->
        <div class="info-bar">
            Live data • Updates Daily • <?php echo date('Y-m-d H:i:s'); ?> UTC
        </div>
        
        <!-- Main Navigation Pills -->
        <nav class="pill-nav main-pills">
            <a href="<?php echo htmlspecialchars(build_tab_url('2025-2026', 'premier-league', 'table'), ENT_QUOTES, 'UTF-8'); ?>"
               class="pill-tab <?php echo ($currentMainTab === '2025-2026') ? 'active' : ''; ?>">
                🏴 2025/26
            </a>
            <a href="<?php echo htmlspecialchars(build_tab_url('2026-2027', 'premier-league', 'table'), ENT_QUOTES, 'UTF-8'); ?>"
               class="pill-tab <?php echo ($currentMainTab === '2026-2027') ? 'active' : ''; ?>">
                🏴 2026/27
            </a>
            <a href="<?php echo htmlspecialchars(build_tab_url('world-cup', null, 'groups'), ENT_QUOTES, 'UTF-8'); ?>"
               class="pill-tab <?php echo ($currentMainTab === 'world-cup') ? 'active' : ''; ?>">
                🌍 World Cup 2026
            </a>
        </nav>
        
        <!-- Sub Navigation Pills -->
        <nav class="pill-nav sub-pills">
            <?php if ($currentMainTab === 'world-cup'): ?>
                <?php foreach ($worldCupTabs as $tabKey => $tabConfig): ?>
                    <a href="<?php echo htmlspecialchars(build_tab_url('world-cup', null, $tabKey), ENT_QUOTES, 'UTF-8'); ?>"
                       class="pill-tab <?php echo ($currentSubTab === $tabKey) ? 'active' : ''; ?>">
                        <?php echo $tabConfig['icon']; ?> <?php echo htmlspecialchars($tabConfig['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach ($seasonLeagueConfigs[$currentMainTab] as $leagueKey => $leagueConfig): ?>
                    <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab, $leagueKey, $leagueConfig['defaultSubTab']), ENT_QUOTES, 'UTF-8'); ?>"
                       class="pill-tab <?php echo ($currentLeague === $leagueKey) ? 'active' : ''; ?>">
                        🏆 <?php echo htmlspecialchars($leagueConfig['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </nav>

        <?php if ($currentMainTab !== 'world-cup'): ?>
        <?php $tableViewParams = football_stats_get_current_table_view_params(); ?>
        <nav class="pill-nav tertiary-pills">
            <?php foreach ($seasonLeagueConfigs[$currentMainTab][$currentLeague]['tabs'] as $tabKey => $tabConfig): ?>
                <?php $extraParams = football_stats_tab_supports_table_view($tabKey) ? $tableViewParams : []; ?>
                <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab, $currentLeague, $tabKey, $extraParams), ENT_QUOTES, 'UTF-8'); ?>"
                   class="pill-tab <?php echo ($currentSubTab === $tabKey) ? 'active' : ''; ?>">
                    <?php echo $tabConfig['icon']; ?> <?php echo htmlspecialchars($tabConfig['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <?php if ($isBlocksSection): ?>
        <!-- Blocks Sub-Navigation (only shows when in blocks section) -->
        <nav class="pill-nav tertiary-pills">
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab, $currentLeague, 'blocks-overview', $tableViewParams), ENT_QUOTES, 'UTF-8'); ?>"
               class="pill-tab-small <?php echo ($currentSubTab == 'blocks-overview') ? 'active' : ''; ?>">
                🎯 Overview
            </a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab, $currentLeague, 'blocks-dynamic', $tableViewParams), ENT_QUOTES, 'UTF-8'); ?>"
               class="pill-tab-small <?php echo ($currentSubTab == 'blocks-dynamic') ? 'active' : ''; ?>">
                🔥 Live Data
            </a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab, $currentLeague, 'blocks-1', $tableViewParams), ENT_QUOTES, 'UTF-8'); ?>"
               class="pill-tab-small <?php echo ($currentSubTab == 'blocks-1') ? 'active' : ''; ?>">
                🏆 Block 1
            </a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab, $currentLeague, 'blocks-2', $tableViewParams), ENT_QUOTES, 'UTF-8'); ?>"
               class="pill-tab-small <?php echo ($currentSubTab == 'blocks-2') ? 'active' : ''; ?>">
                🌟 Block 2
            </a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab, $currentLeague, 'blocks-3', $tableViewParams), ENT_QUOTES, 'UTF-8'); ?>"
               class="pill-tab-small <?php echo ($currentSubTab == 'blocks-3') ? 'active' : ''; ?>">
                ✅ Block 3
            </a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab, $currentLeague, 'blocks-4', $tableViewParams), ENT_QUOTES, 'UTF-8'); ?>"
               class="pill-tab-small <?php echo ($currentSubTab == 'blocks-4') ? 'active' : ''; ?>">
                ⚠️ Block 4
            </a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab, $currentLeague, 'blocks-5', $tableViewParams), ENT_QUOTES, 'UTF-8'); ?>"
               class="pill-tab-small <?php echo ($currentSubTab == 'blocks-5') ? 'active' : ''; ?>">
                🔻 Block 5
            </a>
        </nav>
        <?php endif; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">