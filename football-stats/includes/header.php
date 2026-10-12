<?php
require_once __DIR__ . '/table-view.php';
require_once __DIR__ . '/grouped-navigation.php';

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
$dashboardAccent = preg_match('/^#[0-9A-Fa-f]{6}$/', (string)($dashboardPreferences['accent_color'] ?? '')) ? $dashboardPreferences['accent_color'] : '#FFD700';
$dashboardTitle = trim((string)($dashboardSettings['dashboard_title'] ?? 'Football Stats Dashboard'));
$dashboardSubtitle = trim((string)($dashboardSettings['dashboard_subtitle'] ?? ''));
$compactNavigation = !empty($dashboardPreferences['compact_navigation']);
$hideUpdateControls = ($dashboardSettings['show_update_controls'] ?? '1') !== '1' || !$dashboardCanUpdate;
?>
<style>
:root { --dashboard-accent: <?php echo htmlspecialchars($dashboardAccent, ENT_QUOTES, 'UTF-8'); ?>; }
.site-header {
    background: #1a237e; /* Match the dark blue in your screenshot */
    padding: 30px 20px;
    text-align: center;
    border-bottom: 2px solid var(--dashboard-accent); /* Gold accent */
}

.site-title {
    font-size: 2.5rem;
    color: #ffffff;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    margin-bottom: 10px;
}

.site-subtitle {
    font-size: 1.1rem;
    color: var(--dashboard-accent); /* Matching the yellow text in image_e19c41.png */
    margin: 5px 0;
    font-weight: 600;
}

.site-subtitle a {
    color: #5865f2; /* Discord-ish blue for the TSDB link */
    text-decoration: underline;
}
.dashboard-account-bar { display:flex; justify-content:flex-end; align-items:center; gap:10px; padding:9px 18px; background:#202225; color:#c8c9cc; }
.dashboard-account-bar a { color:#fff; text-decoration:none; font-weight:600; }
.compact-navigation .pill-nav { gap:4px; padding-top:6px; padding-bottom:6px; }
.compact-navigation .pill-tab { padding:6px 10px; font-size:.88rem; }
.compact-navigation .nav-icon { display:none; }
.hide-update-controls #update-el-data-btn, .hide-update-controls #update-wc-data-btn, .hide-update-controls #update-el-status, .hide-update-controls #update-wc-status { display:none !important; }
</style>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($currentTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { corePlugins: { preflight: false } };</script>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚽</text></svg>">
</head>
<body class="<?php echo trim(($compactNavigation ? 'compact-navigation ' : '') . ($hideUpdateControls ? 'hide-update-controls' : '')); ?>">
    <div class="site-container">
        <div class="dashboard-account-bar">
            <?php if ($dashboardUser): ?>
                <span>Signed in as <?php echo htmlspecialchars($dashboardUser['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="/account/">Customise</a>
                <?php if ($dashboardUser['is_admin']): ?><a href="/admin/admin.php">Admin</a><?php endif; ?>
            <?php else: ?>
                <a href="/account/login.php?return=<?php echo rawurlencode($_SERVER['REQUEST_URI'] ?? '/football-stats/'); ?>">Sign in to customise</a>
            <?php endif; ?>
        </div>
        <!-- Header Section -->
        <header class="site-header">
            <div class="header-content">
                <h1 class="site-title">⚽ <?php echo htmlspecialchars($dashboardTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                <?php if ($dashboardSubtitle !== ''): ?><p class="site-subtitle"><?php echo htmlspecialchars($dashboardSubtitle, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                <p class="site-subtitle">Entire Football Pyramid Past & Present</p>
                <p class="site-subtitle">English League Data provided by <a href="https://www.thesportsdb.com/" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($dashboardSettings['english_data_provider'] ?? 'SportsDB API', ENT_QUOTES, 'UTF-8'); ?></a></p>
                <p class="site-subtitle">World Cup Data provided by <a href="https://www.football-data.org/" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($dashboardSettings['world_cup_data_provider'] ?? 'Football-Data API', ENT_QUOTES, 'UTF-8'); ?></a></p>
            </div>
        </header>
        
        <!-- Info Bar (like CleverLounge) -->
        <div class="info-bar" style="display:flex;align-items:center;gap:18px;">
            <span>Live data • Updates Daily • <?php echo date('Y-m-d H:i:s'); ?> UTC</span>
            <button id="update-el-data-btn" style="margin-left:auto;padding:6px 16px;font-size:1em;cursor:pointer;">🔄 Update English League Data</button>
            <button id="update-wc-data-btn" style="margin-left:auto;padding:6px 16px;font-size:1em;cursor:pointer;">🔄 Update World Cup Data</button>
            <span id="update-el-status" style="font-size:0.95em;color:#2a8c2a;display:none;"></span>
            <span id="update-wc-status" style="font-size:0.95em;color:#2a8c2a;display:none;"></span>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var btn = document.getElementById('update-el-data-btn');
                var status = document.getElementById('update-el-status');
                btn.addEventListener('click', function() {
                    btn.disabled = true;
                    status.style.display = 'inline';
                    status.style.color = '#888';
                    status.textContent = 'Updating...';
                    fetch('update-el-data.php', {method: 'POST', headers: {'X-CSRF-Token': <?php echo json_encode(clever_csrf_token()); ?>}})
                    .then(r => r.json())
                    .then(data => {
                        status.style.color = data.success ? '#2a8c2a' : '#c00';
                        status.textContent = data.message;
                        setTimeout(function() {
                            status.style.display = 'none';
                            btn.disabled = false;
                        }, 3500);
                    })
                    .catch(() => {
                        status.style.color = '#c00';
                        status.textContent = 'Error starting update.';
                        setTimeout(function() {
                            status.style.display = 'none';
                            btn.disabled = false;
                        }, 3500);
                    });
                });
            });
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var btn = document.getElementById('update-wc-data-btn');
                var status = document.getElementById('update-wc-status');
                btn.addEventListener('click', function() {
                    btn.disabled = true;
                    status.style.display = 'inline';
                    status.style.color = '#888';
                    status.textContent = 'Updating...';
                    fetch('update-wc-data.php', {method: 'POST', headers: {'X-CSRF-Token': <?php echo json_encode(clever_csrf_token()); ?>}})
                    .then(r => r.json())
                    .then(data => {
                        status.style.color = data.success ? '#2a8c2a' : '#c00';
                        status.textContent = data.message;
                        setTimeout(function() {
                            status.style.display = 'none';
                            btn.disabled = false;
                        }, 3500);
                    })
                    .catch(() => {
                        status.style.color = '#c00';
                        status.textContent = 'Error starting update.';
                        setTimeout(function() {
                            status.style.display = 'none';
                            btn.disabled = false;
                        }, 3500);
                    });
                });
            });
        </script>
        
        <!-- Main Navigation Pills -->
        <nav class="pill-nav main-pills">
            <a href="<?php echo htmlspecialchars(build_tab_url('2025-2026', 'premier-league', 'table'), ENT_QUOTES, 'UTF-8'); ?>"
               class="pill-tab <?php echo ($currentMainTab !== 'world-cup') ? 'active' : ''; ?>">
                <span class="nav-icon">🏴</span> English Leagues
            </a>
            <a href="<?php echo htmlspecialchars(build_tab_url('world-cup', null, 'groups'), ENT_QUOTES, 'UTF-8'); ?>"
               class="pill-tab <?php echo ($currentMainTab === 'world-cup') ? 'active' : ''; ?>">
                <span class="nav-icon">🌍</span> World Cup 2026
            </a>
        </nav>
        
        <?php
        $tableViewParams = football_stats_get_current_table_view_params();
        $trackerTeamParam = isset($_GET['tracker_team']) && is_string($_GET['tracker_team']) && $_GET['tracker_team'] !== '' ? ['tracker_team'=>$_GET['tracker_team']] : [];
        football_stats_render_grouped_navigation($seasonLeagueConfigs,$worldCupTabs,$currentMainTab,$currentLeague,$currentSubTab,$tableViewParams,$trackerTeamParam,(string)($dashboardUser['id']??'guest'));
        ?>

        <!-- Main Content Area -->
        <main class="main-content">
            <?php
            if ($currentMainTab === 'world-cup') {
                // World Cup content
            } else {
                // Season content
            }
            ?>
        </main>
    </div>
</body>
</html>
