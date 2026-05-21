<?php
/**
 * Blocks Overview - League Two Blocks Framework
 * Comprehensive overview showing all 5 blocks with live data
 */

$tableView = football_stats_get_table_view_combined($db, 'L2', 'league_table_L2', $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$allTeams = $tableView['standings'];
$currentMatchday = $tableView['active_matchweek'] ?? (!empty($allTeams) ? max(array_map('intval', array_column($allTeams, 'played'))) : 1);
$last_update = $tableView['last_update'];
$tableViewNavParams = football_stats_get_current_table_view_params();

// Organize teams into blocks
$blocks = [
    1 => ['teams' => [], 'range' => '1-3', 'title' => 'Auto Promotion', 'emoji' => '🏆', 'color' => '#FFD700', 'desc' => 'Automatic Promotion to League One'],
    2 => ['teams' => [], 'range' => '4-7', 'title' => 'Playoff Contenders', 'emoji' => '🎯', 'color' => '#5865F2', 'desc' => 'Playoff spots for promotion to League One'],
    3 => ['teams' => [], 'range' => '8-19', 'title' => 'Mid-Table Security', 'emoji' => '⚖️', 'color' => '#99AAB5', 'desc' => 'Safe & Stable'],
    4 => ['teams' => [], 'range' => '20-23', 'title' => 'Relegation Battle', 'emoji' => '⚠️', 'color' => '#FFA500', 'desc' => 'Danger Zone'],
    5 => ['teams' => [], 'range' => '24', 'title' => 'Relegation Zone', 'emoji' => '🔴', 'color' => '#F44336', 'desc' => 'Drop Zone']
];

// Assign teams to blocks
foreach ($allTeams as $team) {
    $pos = $team['position'];
    if ($pos >= 1 && $pos <= 3) {
        $blocks[1]['teams'][] = $team;
    }
    if ($pos >= 4 && $pos <= 7) {
        $blocks[2]['teams'][] = $team;
    }
    if ($pos >= 8 && $pos <= 19) {
        $blocks[3]['teams'][] = $team;
    }
    if ($pos >= 20 && $pos <= 23) {
        $blocks[4]['teams'][] = $team;
    }
    if ($pos >= 24 && $pos <= 24) {
        $blocks[5]['teams'][] = $team;
    }
}
?>

<div class="blocks-overview-page">
    <!-- Page Header -->
    <div class="panel" style="border-left: 4px solid #5865F2;">
        <div class="overview-header">
            <h2>📊 League Two Blocks Framework Overview</h2>
            <span class="season-badge">2025/26 Season</span>
        </div>
        <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', 'league-two', $currentSubTab ?? 'blocks-overview'); ?>
        <p style="margin-top: 15px; font-size: 1.1em; color: #ddd;">
            The League Two is divided into 5 strategic blocks, each representing distinct competitive zones
            with unique targets, challenges, and psychological boundaries.
        </p>
        <p style="margin-top: 10px; color: #888; font-size: 0.9em;">
            📅 Current Matchday: <strong><?php echo $currentMatchday; ?></strong> of 46 •
            <?php if ($currentMatchday < 23): ?>
                First half of season
            <?php elseif ($currentMatchday == 23): ?>
                🎯 Halfway point!
            <?php elseif ($currentMatchday > 23 && $currentMatchday <= 29): ?>
                Mid-season phase
            <?php elseif ($currentMatchday > 29 && $currentMatchday < 46): ?>
                Final stretch
            <?php endif; ?>
            <?php if ($last_update && isset($last_update['ts'])): ?>
            <span style="margin-left: 15px;">
                Last updated: <?= date('Y-m-d H:i:s', $last_update['ts'] / 1000) ?>
            </span>
            <?php endif; ?>
        </p>
    </div>

    <!-- Live Blocks Summary -->
    <?php foreach ($blocks as $blockNum => $block): ?>
    <div class="panel" style="border-left: 4px solid <?php echo $block['color']; ?>;">
        <div class="block-summary-header">
            <div class="block-title-section">
                <h3 style="color: <?php echo $block['color']; ?>;">
                    <?php echo $block['emoji']; ?> Block <?php echo $blockNum; ?>: <?php echo $block['title']; ?>
                </h3>
                <span class="positions-badge" style="background: rgba(<?php
                    echo $blockNum == 1 ? '255, 215, 0' :
                        ($blockNum == 2 ? '88, 101, 242' :
                        ($blockNum == 3 ? '153, 170, 181' :
                        ($blockNum == 4 ? '255, 165, 0' : '244, 67, 54')));
                ?>, 0.2); color: <?php echo $block['color']; ?>; border: 1px solid <?php echo $block['color']; ?>;">
                    Positions <?php echo $block['range']; ?>
                </span>
            </div>
            <p style="color: #888; margin: 10px 0 15px 0;">
                <strong style="color: <?php echo $block['color']; ?>;"><?php echo $block['desc']; ?></strong>
            </p>
        </div>

        <?php if (!empty($block['teams'])): ?>
        <div class="mini-table-wrapper">
            <table class="mini-block-table">
                <thead>
                    <tr style="background: rgba(<?php
                        echo $blockNum == 1 ? '255, 215, 0' :
                            ($blockNum == 2 ? '88, 101, 242' :
                            ($blockNum == 3 ? '153, 170, 181' :
                            ($blockNum == 4 ? '255, 165, 0' : '244, 67, 54')));
                    ?>, 0.1);">
                        <th>Pos</th>
                        <th>Team</th>
                        <th>Pld</th>
                        <th>W</th>
                        <th>D</th>
                        <th>L</th>
                        <th>GD</th>
                        <th>Pts</th>
                        <th>PPG</th>
                        <th>Form</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($block['teams'] as $team):
                        $ppg = $team['played'] > 0 ? round($team['points'] / $team['played'], 2) : 0;
                        $projectedPoints = round($ppg * 46, 0);
                    ?>
                    <tr>
                        <td style="color: <?php echo $block['color']; ?>; font-weight: bold;"><?php echo $team['position']; ?></td>
                        <td style="text-align: left;"><strong><?php echo htmlspecialchars($team['team_name']); ?></strong></td>
                        <td><?php echo $team['played']; ?></td>
                        <td style="color: #4CAF50;"><?php echo $team['won']; ?></td>
                        <td style="color: #888;"><?php echo $team['drawn']; ?></td>
                        <td style="color: #F44336;"><?php echo $team['lost']; ?></td>
                        <td style="color: <?php echo $team['gd'] > 0 ? '#4CAF50' : ($team['gd'] < 0 ? '#F44336' : '#888'); ?>;">
                            <?php echo ($team['gd'] > 0 ? '+' : '') . $team['gd']; ?>
                        </td>
                        <td style="background: rgba(<?php
                            echo $blockNum == 1 ? '255, 215, 0' :
                                ($blockNum == 2 ? '88, 101, 242' :
                                ($blockNum == 3 ? '153, 170, 181' :
                                ($blockNum == 4 ? '255, 165, 0' : '244, 67, 54')));
                        ?>, 0.2); font-weight: bold;">
                            <?php echo $team['points']; ?>
                        </td>
                        <td style="color: <?php echo $block['color']; ?>; font-weight: bold;"><?php echo $ppg; ?></td>
                        <td style="font-family: monospace; font-size: 0.85em;"><?php echo $team['form'] ?? '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Block Stats Summary -->
        <div class="block-stats-summary">
            <?php
                $totalPoints = array_sum(array_column($block['teams'], 'points'));
                $avgPoints = count($block['teams']) > 0 ? round($totalPoints / count($block['teams']), 1) : 0;
                $avgPPG = count($block['teams']) > 0 && $currentMatchday > 0 ? round($avgPoints / $currentMatchday, 2) : 0;
                $totalGD = array_sum(array_column($block['teams'], 'gd'));

                // Calculate block-specific targets
                $blockTargets = [
                    1 => ['target' => '85-95 pts', 'ppg' => '2.0-2.4', 'status' => 'Automatic promotion'],
                    2 => ['target' => '72-84 pts', 'ppg' => '1.7-2.0', 'status' => 'Playoff qualification'],
                    3 => ['target' => '48-71 pts', 'ppg' => '1.0-1.6', 'status' => 'Safe mid-table'],
                    4 => ['target' => '38-47 pts', 'ppg' => '0.8-1.0', 'status' => 'Survival battle'],
                    5 => ['target' => '<38 pts', 'ppg' => '<0.8', 'status' => 'Relegation danger']
                ];
            ?>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">Avg Points:</span>
                    <span class="stat-value"><?php echo $avgPoints; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Avg PPG:</span>
                    <span class="stat-value" style="color: <?php echo $block['color']; ?>;"><?php echo $avgPPG; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Block GD:</span>
                    <span class="stat-value" style="color: <?php echo $totalGD > 0 ? '#4CAF50' : ($totalGD < 0 ? '#F44336' : '#888'); ?>;">
                        <?php echo ($totalGD > 0 ? '+' : '') . $totalGD; ?>
                    </span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Target:</span>
                    <span class="stat-value"><?php echo $blockTargets[$blockNum]['target']; ?></span>
                </div>
            </div>
            <p style="margin-top: 10px; color: #888; font-size: 0.9em;">
                <strong>Target PPG:</strong> <?php echo $blockTargets[$blockNum]['ppg']; ?> •
                <strong>Status:</strong> <?php echo $blockTargets[$blockNum]['status']; ?>
            </p>
        </div>

        <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'league-two', 'blocks-' . $blockNum, $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="view-detail-btn" style="border-color: <?php echo $block['color']; ?>; color: <?php echo $block['color']; ?>;">
            View Detailed Block <?php echo $blockNum; ?> Analysis →
        </a>
        <?php else: ?>
        <p style="color: #888; padding: 20px; text-align: center;">No teams currently in this block</p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- Framework Explanation -->
    <div class="panel">
        <h3>🎯 Understanding the Blocks Framework</h3>
        <div class="framework-grid">
            <div class="framework-card">
                <h4>📏 Why Blocks?</h4>
                <ul>
                    <li>24 teams across 5 distinct zones</li>
                    <li>Creates clear competitive boundaries</li>
                    <li>Psychological barriers matter</li>
                    <li>Tactical approaches differ per block</li>
                </ul>
            </div>
            <div class="framework-card">
                <h4>📊 Points Per Game (PPG)</h4>
                <ul>
                    <li>Key metric: Points ÷ Games Played</li>
                    <li>Multiply by 46 for season projection</li>
                    <li>More reliable than current points</li>
                    <li>Shows true performance level</li>
                </ul>
            </div>
            <div class="framework-card">
                <h4>🔄 Block Movement</h4>
                <ul>
                    <li>Early season: Highly fluid (MD 1-10)</li>
                    <li>Mid season: Stabilizing (MD 11-25)</li>
                    <li>Late season: Locked in (MD 26+)</li>
                    <li>Giant killings become rarer</li>
                </ul>
            </div>
            <div class="framework-card">
                <h4>📈 Historical Accuracy</h4>
                <ul>
                    <li>90%+ teams stay in their block (MD15+)</li>
                    <li>Block 5 at Christmas: 80% relegated</li>
                    <li>Block 1 at Christmas: 75% finish top 3</li>
                    <li>Math beats hope in football</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- League Two Structure -->
    <div class="panel">
        <h3>🎖️ League Two 2025/26 – Zone Breakdown</h3>
        <div class="pyramid-grid">
            <div class="league-block" style="border-left: 3px solid #FFD700;">
                <h4>🏆 Block 1: Auto Promotion (1-3)</h4>
                <p><strong>Top 3 promoted automatically</strong></p>
                <ul>
                    <li>Target: 85-95 points</li>
                    <li>PPG required: 2.0-2.4</li>
                    <li>Win rate needed: 60%+</li>
                    <li>Halfway target: ~43-48 pts</li>
                </ul>
            </div>
            <div class="league-block" style="border-left: 3px solid #5865F2;">
                <h4>🎯 Block 2: Playoff Contenders (4-7)</h4>
                <p><strong>Playoff spots for promotion</strong></p>
                <ul>
                    <li>Target: 72-84 points</li>
                    <li>PPG required: 1.7-2.0</li>
                    <li>Positions 4-7 enter playoffs</li>
                    <li>Halfway target: ~36-42 pts</li>
                </ul>
            </div>
            <div class="league-block" style="border-left: 3px solid #99AAB5;">
                <h4>⚖️ Block 3: Mid-Table (8-19)</h4>
                <p><strong>Safe and stable mid-table</strong></p>
                <ul>
                    <li>Target: 48-71 points</li>
                    <li>PPG required: 1.0-1.6</li>
                    <li>No pressure, no reward</li>
                    <li>Halfway target: ~24-36 pts</li>
                </ul>
            </div>
            <div class="league-block" style="border-left: 3px solid #FFA500;">
                <h4>⚠️ Block 4: Relegation Battle (20-23)</h4>
                <p><strong>Danger zone – fighting for survival</strong></p>
                <ul>
                    <li>Target: 38-47 points</li>
                    <li>PPG required: 0.8-1.0</li>
                    <li>Must stay above bottom 1</li>
                    <li>Halfway target: ~19-24 pts</li>
                </ul>
            </div>
            <div class="league-block" style="border-left: 3px solid #F44336;">
                <h4>🔴 Block 5: Relegation Zone (24)</h4>
                <p><strong>The drop – National League bound</strong></p>
                <ul>
                    <li>Target: escape to 47+ pts</li>
                    <li>PPG currently: &lt;0.8</li>
                    <li>1 team relegated to National League</li>
                    <li>Financial devastation awaits</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Key Insights -->
    <div class="panel">
        <h3>💡 Key Statistical Insights</h3>
        <div class="insights-grid">
            <div class="insight-card" style="border-left: 3px solid #4CAF50;">
                <h4>✅ Safety Threshold</h4>
                <p class="big-stat">47 Points</p>
                <p>Teams with 47+ points are virtually safe from relegation in League Two</p>
            </div>
            <div class="insight-card" style="border-left: 3px solid #FFD700;">
                <h4>🏆 Auto Promotion</h4>
                <p class="big-stat">85-95 Points</p>
                <p>Typical points total for automatic promotion from League Two</p>
            </div>
            <div class="insight-card" style="border-left: 3px solid #5865F2;">
                <h4>🎯 Playoff Spot</h4>
                <p class="big-stat">72-84 Points</p>
                <p>Typical range for securing a League Two playoff spot (positions 4-7)</p>
            </div>
            <div class="insight-card" style="border-left: 3px solid #F44336;">
                <h4>🔴 Danger Zone</h4>
                <p class="big-stat">&lt;38 Points</p>
                <p>Teams finishing below 38 points are at serious relegation risk</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="panel">
        <h3>🧭 Detailed Block Analysis</h3>
        <div class="block-nav-grid">
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'league-two', 'blocks-1', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="block-nav-btn" style="border-left-color: #FFD700; background: rgba(255, 215, 0, 0.05);">
                <span class="nav-emoji">🏆</span>
                <span class="nav-title">Block 1</span>
                <span class="nav-subtitle">Auto Promotion</span>
            </a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'league-two', 'blocks-2', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="block-nav-btn" style="border-left-color: #5865F2; background: rgba(88, 101, 242, 0.05);">
                <span class="nav-emoji">🎯</span>
                <span class="nav-title">Block 2</span>
                <span class="nav-subtitle">Playoff Contenders</span>
            </a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'league-two', 'blocks-3', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="block-nav-btn" style="border-left-color: #99AAB5; background: rgba(153, 170, 181, 0.05);">
                <span class="nav-emoji">⚖️</span>
                <span class="nav-title">Block 3</span>
                <span class="nav-subtitle">Mid-Table Security</span>
            </a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'league-two', 'blocks-4', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="block-nav-btn" style="border-left-color: #FFA500; background: rgba(255, 165, 0, 0.05);">
                <span class="nav-emoji">⚠️</span>
                <span class="nav-title">Block 4</span>
                <span class="nav-subtitle">Relegation Battle</span>
            </a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'league-two', 'blocks-5', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="block-nav-btn" style="border-left-color: #F44336; background: rgba(244, 67, 54, 0.05);">
                <span class="nav-emoji">🔴</span>
                <span class="nav-title">Block 5</span>
                <span class="nav-subtitle">Relegation Zone</span>
            </a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'league-two', 'blocks-dynamic', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="block-nav-btn" style="border-left-color: #00D9FF; background: linear-gradient(135deg, rgba(0, 217, 255, 0.1), rgba(255, 107, 107, 0.1));">
                <span class="nav-emoji">🔥</span>
                <span class="nav-title">Live Data</span>
                <span class="nav-subtitle">Dynamic Predictions</span>
            </a>
        </div>
    </div>
</div>

<style>
.blocks-overview-page { max-width: 1400px; margin: 0 auto; }
.overview-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
.overview-header h2 { margin: 0; font-size: 2em; }
.season-badge { padding: 6px 12px; background: rgba(88, 101, 242, 0.2); color: #5865F2; border-radius: 5px; font-size: 0.9em; font-weight: bold; }
.block-summary-header { margin-bottom: 20px; }
.block-title-section { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.block-title-section h3 { margin: 0; font-size: 1.5em; }
.positions-badge { padding: 6px 12px; border-radius: 5px; font-size: 0.85em; font-weight: bold; }
.mini-table-wrapper { overflow-x: auto; margin: 15px 0; }
.mini-block-table { width: 100%; border-collapse: collapse; }
.mini-block-table th, .mini-block-table td { padding: 10px 6px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 0.9em; }
.mini-block-table th { font-weight: 600; text-transform: uppercase; font-size: 0.85em; }
.block-stats-summary { background: rgba(255, 255, 255, 0.03); padding: 15px; border-radius: 5px; margin: 15px 0; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; }
.stat-item { display: flex; flex-direction: column; gap: 5px; }
.stat-label { color: #888; font-size: 0.85em; }
.stat-value { font-weight: bold; font-size: 1.1em; }
.view-detail-btn { display: block; text-align: center; padding: 10px; background: rgba(255, 255, 255, 0.03); border: 2px solid; border-radius: 6px; text-decoration: none; font-weight: bold; transition: all 0.2s; margin-top: 15px; }
.view-detail-btn:hover { background: rgba(255, 255, 255, 0.08); transform: translateY(-2px); }
.framework-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 20px; }
.framework-card { background: rgba(255, 255, 255, 0.03); padding: 15px; border-radius: 8px; border-left: 3px solid #5865F2; }
.framework-card h4 { margin: 0 0 10px 0; color: #5865F2; }
.framework-card ul { list-style: none; padding: 0; margin: 0; }
.framework-card ul li { padding: 5px 0; color: #bbb; font-size: 0.9em; }
.framework-card ul li:before { content: "→ "; color: #666; }
.pyramid-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 20px; }
.league-block { background: rgba(255, 255, 255, 0.03); padding: 15px; border-radius: 8px; }
.league-block h4 { margin: 0 0 5px 0; }
.league-block p { margin: 5px 0; color: #888; font-size: 0.9em; }
.league-block ul { list-style: none; padding: 0; margin: 10px 0 0 0; }
.league-block ul li { padding: 4px 0; font-size: 0.9em; color: #bbb; }
.insights-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
.insight-card { background: rgba(255, 255, 255, 0.03); padding: 20px; border-radius: 8px; text-align: center; }
.insight-card h4 { margin: 0 0 10px 0; font-size: 1em; }
.big-stat { font-size: 2em; font-weight: bold; margin: 10px 0; color: #fff; }
.insight-card p:last-child { color: #888; font-size: 0.85em; margin: 10px 0 0 0; }
.block-nav-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-top: 20px; }
.block-nav-btn { display: flex; flex-direction: column; align-items: center; padding: 20px; border-radius: 8px; border-left: 4px solid; text-decoration: none; transition: all 0.2s; }
.block-nav-btn:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); }
.nav-emoji { font-size: 2em; margin-bottom: 10px; }
.nav-title { font-weight: bold; font-size: 1.1em; color: #fff; margin-bottom: 5px; }
.nav-subtitle { font-size: 0.85em; color: #888; }
@media (max-width: 768px) {
    .overview-header, .block-title-section { flex-direction: column; align-items: flex-start; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .framework-grid, .pyramid-grid, .insights-grid { grid-template-columns: 1fr; }
    .block-nav-grid { grid-template-columns: repeat(2, 1fr); }
    .mini-block-table { font-size: 0.8em; }
    .mini-block-table th, .mini-block-table td { padding: 6px 3px; }
}
</style>
