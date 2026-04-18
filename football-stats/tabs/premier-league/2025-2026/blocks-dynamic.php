<?php
/**
 * Dynamic Blocks - Live Predictions & Projections
 * Real-time analysis with PPG-based season projections
 */

$tableView = football_stats_get_table_view($db, 'PL', 'league_table_PL', $currentMainTab ?? '2025-2026');
$allTeams = $tableView['standings'];
$currentMatchday = $tableView['active_matchweek'] ?? (!empty($allTeams) ? max(array_map('intval', array_column($allTeams, 'played'))) : 1);
$last_update = $tableView['last_update'];
$tableViewNavParams = football_stats_get_current_table_view_params();

// Calculate projections for all teams
$projections = [];
foreach ($allTeams as $team) {
    $ppg = $team['played'] > 0 ? round($team['points'] / $team['played'], 2) : 0;
    $projectedPoints = round($ppg * 38, 0);
    $remainingGames = 38 - $team['played'];
    
    // Determine predicted block based on projected points
    $predictedBlock = 5; // Default to worst case
    if ($projectedPoints >= 70) $predictedBlock = 1;
    elseif ($projectedPoints >= 60) $predictedBlock = 2;
    elseif ($projectedPoints >= 38) $predictedBlock = 3;
    elseif ($projectedPoints >= 37) $predictedBlock = 4;
    
    // Current block
    $currentBlock = 5;
    if ($team['position'] <= 4) $currentBlock = 1;
    elseif ($team['position'] <= 7) $currentBlock = 2;
    elseif ($team['position'] <= 14) $currentBlock = 3;
    elseif ($team['position'] <= 17) $currentBlock = 4;
    
    // Calculate various targets
    $pointsFor40 = max(0, 40 - $team['points']);
    $pointsFor70 = max(0, 70 - $team['points']);
    $ppgFor40 = $remainingGames > 0 ? round($pointsFor40 / $remainingGames, 2) : 0;
    $ppgFor70 = $remainingGames > 0 ? round($pointsFor70 / $remainingGames, 2) : 0;
    
    // Risk assessment
    if ($projectedPoints >= 38) {
        $risk = 'Safe';
        $riskColor = '#4CAF50';
    } elseif ($projectedPoints >= 37) {
        $risk = 'Caution';
        $riskColor = '#FFA500';
    } else {
        $risk = 'Danger';
        $riskColor = '#F44336';
    }
    
    $projections[] = [
        'team' => $team,
        'ppg' => $ppg,
        'projected' => $projectedPoints,
        'remaining' => $remainingGames,
        'currentBlock' => $currentBlock,
        'predictedBlock' => $predictedBlock,
        'blockChange' => $predictedBlock - $currentBlock,
        'pointsFor40' => $pointsFor40,
        'pointsFor70' => $pointsFor70,
        'ppgFor40' => $ppgFor40,
        'ppgFor70' => $ppgFor70,
        'risk' => $risk,
        'riskColor' => $riskColor
    ];
}

// Sort by projected points descending
usort($projections, function($a, $b) {
    return $b['projected'] - $a['projected'];
});

// Block definitions
$blockColors = [
    1 => '#FFD700',
    2 => '#5865F2',
    3 => '#99AAB5',
    4 => '#FFA500',
    5 => '#F44336'
];

$blockNames = [
    1 => 'Title Contenders',
    2 => 'European Zone',
    3 => 'Mid-Table',
    4 => 'Danger Zone',
    5 => 'Relegation'
];
?>

<style>
.team-name {
    position: relative;
    cursor: help;
    display: inline-block;
    transition: color 0.2s ease;
}

.team-name:hover {
    color: #FFCD00;
}

.team-official {
    color: #dcddde;
}

.team-common {
    color: #888;
    font-size: 12px;
    margin-left: 6px;
    font-weight: normal;
}

.team-tooltip {
    visibility: hidden;
    opacity: 0;
    position: absolute;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #2e3136, #40444b);
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    white-space: nowrap;
    z-index: 1000;
    font-size: 13px;
    border: 2px solid;
    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.team-tooltip::after {
    content: "";
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-top-color: inherit;
}

.team-name:hover .team-tooltip {
    visibility: visible;
    opacity: 1;
}

.tooltip-nickname {
    display: block;
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 3px;
}

.tooltip-short {
    display: block;
    font-size: 11px;
    color: #dcddde;
}

/* 1. Target the header cells specifically */
th {
    position: sticky;
    top: 0;
    z-index: 10; /* Keeps the header above the scrolling body rows */
    background-color: #222; /* Use your team/site brand color here */
    color: white;
    white-space: nowrap; /* Prevents long titles from breaking the layout */
    border-bottom: 2px solid #444;
}

/* 2. Important fix for tables */
table {
    border-collapse: collapse; /* Required for sticky borders to show up correctly */
}
</style>

<div class="dynamic-blocks-page">
    <!-- Header -->
    <div class="panel" style="border-left: 4px solid #00D9FF;">
        <div class="dynamic-header">
            <h2>🔥 Dynamic Blocks - Live Predictions</h2>
            <span class="live-badge">LIVE DATA</span>
        </div>
        <?php football_stats_render_table_view_controls($tableView, $currentMainTab ?? '2025-2026', $currentLeague ?? 'premier-league', $currentSubTab ?? 'blocks-dynamic'); ?>
        <p style="margin-top: 15px; font-size: 1.1em; color: #ddd;">
            Real-time PPG (Points Per Game) projections showing where teams are likely to finish based on 
            current form. Updated after every matchday.
        </p>
        <p style="margin-top: 10px; color: #888; font-size: 0.9em;">
            📅 Matchday: <strong><?php echo $currentMatchday; ?></strong> of 38 • 
            Games Remaining: <strong><?php echo 38 - $currentMatchday; ?></strong> • 
            Season Progress: <strong><?php echo round(($currentMatchday / 38) * 100, 1); ?>%</strong>
            <?php if ($last_update && isset($last_update['ts'])): ?>
            <span style="margin-left: 15px;">
                Last updated: <?= date('Y-m-d H:i:s', $last_update['ts'] / 1000) ?>
            </span>
            <?php endif; ?>
        </p>
    </div>

    <!-- Methodology Explanation -->
    <div class="panel">
        <h3>📊 How Predictions Work</h3>
        <div class="methodology-grid">
            <div class="method-card">
                <h4>1️⃣ Calculate PPG</h4>
                <p><strong>Current Points ÷ Games Played</strong></p>
                <p class="note">This shows a team's true performance level independent of games played</p>
            </div>
            <div class="method-card">
                <h4>2️⃣ Project Season End</h4>
                <p><strong>PPG × 38 Games</strong></p>
                <p class="note">Extrapolates current form across entire 38-game season</p>
            </div>
            <div class="method-card">
                <h4>3️⃣ Assign Predicted Block</h4>
                <p><strong>Based on Historical Ranges</strong></p>
                <p class="note">70+ pts = Block 1, 60-69 = Block 2, 45-59 = Block 3, 38-44 = Block 4, <38 = Block 5</p>
            </div>
            <div class="method-card">
                <h4>4️⃣ Risk Assessment</h4>
                <p><strong>Survival Probability</strong></p>
                <p class="note">Safe (40+), Caution (37-39), Danger (<37) based on relegation history</p>
            </div>
        </div>
    </div>

    <!-- Full Projections Table -->
    <div class="panel">
        <h3>🎯 Complete Season Projections</h3>
        <div class="table-wrapper">
            <table class="projections-table">
                <thead>
                    <tr>
                        <th>Pos</th>
                        <th>Team</th>
                        <th>Pld</th>
                        <th>Pts</th>
                        <th>PPG</th>
                        <th>Projected</th>
                        <th>Current Block</th>
                        <th>Predicted Block</th>
                        <th>Movement</th>
                        <th>Risk Status</th>
                        <th>Form</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projections as $idx => $proj): 
                        $team = $proj['team'];
                        $projectedPos = $idx + 1;
                    ?>
                    <tr>
                        <td style="font-weight: bold; color: <?php echo $blockColors[$proj['predictedBlock']]; ?>;">
                            <?php echo $projectedPos; ?>
                        </td>
                        <td style="text-align: left;">
                            <strong><?php echo htmlspecialchars($team['team_name']); ?></strong>
                            <?php if ($projectedPos != $team['position']): ?>
                            <span style="color: <?php echo $projectedPos < $team['position'] ? '#4CAF50' : '#F44336'; ?>; font-size: 0.85em; margin-left: 5px;">
                                (<?php echo $projectedPos < $team['position'] ? '↑' : '↓'; ?><?php echo abs($projectedPos - $team['position']); ?>)
                            </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $team['played']; ?></td>
                        <td style="font-weight: bold;"><?php echo $team['points']; ?></td>
                        <td style="color: <?php echo $blockColors[$proj['predictedBlock']]; ?>; font-weight: bold;">
                            <?php echo $proj['ppg']; ?>
                        </td>
                        <td style="background: rgba(<?php 
                            $rgb = $proj['predictedBlock'] == 1 ? '255,215,0' : 
                                   ($proj['predictedBlock'] == 2 ? '88,101,242' : 
                                   ($proj['predictedBlock'] == 3 ? '153,170,181' : 
                                   ($proj['predictedBlock'] == 4 ? '255,165,0' : '244,67,54')));
                            echo $rgb;
                        ?>, 0.2); font-weight: bold; font-size: 1.1em;">
                            <?php echo $proj['projected']; ?> pts
                        </td>
                        <td>
                            <span class="block-badge" style="background: rgba(<?php 
                                $rgb = $proj['currentBlock'] == 1 ? '255,215,0' : 
                                       ($proj['currentBlock'] == 2 ? '88,101,242' : 
                                       ($proj['currentBlock'] == 3 ? '153,170,181' : 
                                       ($proj['currentBlock'] == 4 ? '255,165,0' : '244,67,54')));
                                echo $rgb;
                            ?>, 0.3); color: <?php echo $blockColors[$proj['currentBlock']]; ?>;">
                                Block <?php echo $proj['currentBlock']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="block-badge" style="background: rgba(<?php 
                                $rgb = $proj['predictedBlock'] == 1 ? '255,215,0' : 
                                       ($proj['predictedBlock'] == 2 ? '88,101,242' : 
                                       ($proj['predictedBlock'] == 3 ? '153,170,181' : 
                                       ($proj['predictedBlock'] == 4 ? '255,165,0' : '244,67,54')));
                                echo $rgb;
                            ?>, 0.3); color: <?php echo $blockColors[$proj['predictedBlock']]; ?>;">
                                Block <?php echo $proj['predictedBlock']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($proj['blockChange'] == 0): ?>
                                <span style="color: #888;">—</span>
                            <?php elseif ($proj['blockChange'] > 0): ?>
                                <span style="color: #F44336; font-weight: bold;">↓ <?php echo abs($proj['blockChange']); ?></span>
                            <?php else: ?>
                                <span style="color: #4CAF50; font-weight: bold;">↑ <?php echo abs($proj['blockChange']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="risk-badge" style="background: rgba(<?php 
                                echo $proj['risk'] == 'Safe' ? '76,175,129' : 
                                     ($proj['risk'] == 'Caution' ? '255,165,0' : '244,67,54');
                            ?>, 0.2); color: <?php echo $proj['riskColor']; ?>; border: 1px solid <?php echo $proj['riskColor']; ?>;">
                                <?php echo $proj['risk']; ?>
                            </span>
                        </td>
                        <td style="font-family: monospace; font-size: 0.85em;"><?php echo $team['form'] ?? '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Predicted Blocks Breakdown -->
    <div class="panel">
        <h3>📦 Predicted Blocks Distribution</h3>
        <div class="predicted-blocks-grid">
            <?php 
            // Group teams by predicted block
            $predictedBlockGroups = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];
            foreach ($projections as $proj) {
                $predictedBlockGroups[$proj['predictedBlock']][] = $proj;
            }
            
            foreach ($predictedBlockGroups as $blockNum => $teams):
                if (empty($teams)) continue;
            ?>
            <div class="predicted-block-card" style="border-left: 4px solid <?php echo $blockColors[$blockNum]; ?>;">
                <h4 style="color: <?php echo $blockColors[$blockNum]; ?>;">
                    Block <?php echo $blockNum; ?>: <?php echo $blockNames[$blockNum]; ?>
                </h4>
                <p style="color: #888; margin: 5px 0 15px 0;"><?php echo count($teams); ?> teams projected</p>
                <ul class="predicted-teams-list">
                    <?php foreach ($teams as $proj): ?>
                    <li>
                        <span class="team-name"><?php echo htmlspecialchars($proj['team']['team_name']); ?></span>
                        <span class="team-projection"><?php echo $proj['projected']; ?> pts</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Survival & Target Analysis -->
    <div class="panel">
        <h3>🎯 Key Targets & Survival Scenarios</h3>
        <div class="targets-grid">
            <?php foreach ($projections as $proj): 
                $team = $proj['team'];
                // Only show teams in blocks 3-5 or teams needing analysis
                if ($proj['currentBlock'] < 3 && $proj['predictedBlock'] < 3) continue;
            ?>
            <div class="target-card" style="border-left: 3px solid <?php echo $proj['riskColor']; ?>;">
                <h4><?php echo htmlspecialchars($team['team_name']); ?></h4>
                <div class="target-stats">
                    <div class="target-row">
                        <span class="target-label">Current Status:</span>
                        <span class="target-value" style="color: <?php echo $proj['riskColor']; ?>;">
                            <?php echo $proj['risk']; ?> (<?php echo $team['points']; ?> pts)
                        </span>
                    </div>
                    <div class="target-row">
                        <span class="target-label">Projected Finish:</span>
                        <span class="target-value"><strong><?php echo $proj['projected']; ?> pts</strong></span>
                    </div>
                    
                    <?php if ($proj['projected'] < 40): ?>
                    <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 10px 0; padding-top: 10px;">
                        <div class="target-row">
                            <span class="target-label">🚨 For 40pt Safety:</span>
                            <span class="target-value" style="color: #F44336;">
                                Need <?php echo $proj['pointsFor40']; ?> pts
                            </span>
                        </div>
                        <div class="target-row">
                            <span class="target-label">Required PPG:</span>
                            <span class="target-value" style="color: #F44336; font-weight: bold;">
                                <?php echo $proj['ppgFor40']; ?>
                            </span>
                        </div>
                        <p style="font-size: 0.85em; color: #888; margin-top: 5px;">
                            From <?php echo $proj['remaining']; ?> remaining games
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($proj['projected'] < 70 && $proj['currentBlock'] <= 2): ?>
                    <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 10px 0; padding-top: 10px;">
                        <div class="target-row">
                            <span class="target-label">⚽ For 70pt CL Spot:</span>
                            <span class="target-value" style="color: #FFD700;">
                                Need <?php echo $proj['pointsFor70']; ?> pts
                            </span>
                        </div>
                        <div class="target-row">
                            <span class="target-label">Required PPG:</span>
                            <span class="target-value" style="color: #FFD700; font-weight: bold;">
                                <?php echo $proj['ppgFor70']; ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Key Insights -->
    <div class="panel">
        <h3>💡 Dynamic Insights</h3>
        <div class="insights-dynamic-grid">
            <?php
            $safeTeams = count(array_filter($projections, fn($p) => $p['projected'] >= 40));
            $dangerTeams = count(array_filter($projections, fn($p) => $p['projected'] < 40));
            $blockMovers = count(array_filter($projections, fn($p) => $p['blockChange'] != 0));
            $avgPPG = round(array_sum(array_column($projections, 'ppg')) / count($projections), 2);
            ?>
            <div class="insight-dynamic-card" style="border-left: 3px solid #4CAF50;">
                <h4>✅ Projected Safe</h4>
                <p class="big-stat"><?php echo $safeTeams; ?> Teams</p>
                <p>Currently on track for 40+ points (safety threshold)</p>
            </div>
            <div class="insight-dynamic-card" style="border-left: 3px solid #F44336;">
                <h4>🚨 In Danger</h4>
                <p class="big-stat"><?php echo $dangerTeams; ?> Teams</p>
                <p>Projected to finish below 40 points (relegation risk)</p>
            </div>
            <div class="insight-dynamic-card" style="border-left: 3px solid #FFA500;">
                <h4>🔄 Block Movers</h4>
                <p class="big-stat"><?php echo $blockMovers; ?> Teams</p>
                <p>Predicted to change blocks by season end</p>
            </div>
            <div class="insight-dynamic-card" style="border-left: 3px solid #5865F2;">
                <h4>📊 League Average</h4>
                <p class="big-stat"><?php echo $avgPPG; ?> PPG</p>
                <p>Average points per game across all teams</p>
            </div>
        </div>
    </div>

    <!-- Accuracy Notice -->
    <div class="panel" style="background: rgba(255, 152, 0, 0.1); border-left: 3px solid #FF9800;">
        <h3>⚠️ Prediction Accuracy & Limitations</h3>
        <p style="margin: 10px 0; line-height: 1.6;">
            These predictions are based purely on <strong>current PPG (Points Per Game)</strong> and assume teams maintain 
            their current form level. Actual results will vary due to:
        </p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Injuries and suspensions affecting key players</li>
            <li>January transfer window activity</li>
            <li>Manager changes and tactical adjustments</li>
            <li>Fixture difficulty (easier/harder runs of games)</li>
            <li>Cup competitions affecting focus and energy</li>
            <li>Psychological factors (pressure, momentum, confidence)</li>
        </ul>
        <p style="margin: 10px 0; color: #888; font-style: italic;">
            <strong>Historical Accuracy:</strong> PPG-based projections after Matchday 10 have approximately 
            <strong>75-80% accuracy</strong> for final block placement. Accuracy increases significantly after Matchday 20 (85-90%).
        </p>
    </div>

    <!-- Navigation -->
    <div class="panel">
        <div class="block-navigation">
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'premier-league', 'blocks-overview', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="nav-btn">← Blocks Overview</a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'premier-league', 'table', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="nav-btn">Full Table</a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'premier-league', 'blocks-1', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="nav-btn">Detailed Blocks →</a>
        </div>
    </div>
</div>

<style>
.dynamic-blocks-page { max-width: 1400px; margin: 0 auto; }
.dynamic-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
.dynamic-header h2 { margin: 0; font-size: 2em; }
.live-badge { padding: 6px 12px; background: linear-gradient(135deg, #FF6B6B, #4ECDC4); color: #fff; border-radius: 5px; font-size: 0.85em; font-weight: bold; animation: pulse 2s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
.methodology-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-top: 20px; }
.method-card { background: rgba(255, 255, 255, 0.03); padding: 15px; border-radius: 8px; border-left: 3px solid #5865F2; }
.method-card h4 { margin: 0 0 10px 0; color: #5865F2; }
.method-card p { margin: 5px 0; }
.method-card .note { font-size: 0.85em; color: #888; margin-top: 8px; }
.table-wrapper { overflow-x: auto; margin-top: 20px; }
.projections-table { width: 100%; border-collapse: collapse; }
.projections-table th, .projections-table td { padding: 12px 8px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 0.9em; }
.projections-table th { font-weight: 600; text-transform: uppercase; background: rgba(255,255,255,0.05); }
.block-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; white-space: nowrap; display: inline-block; }
.risk-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; white-space: nowrap; }
.predicted-blocks-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-top: 20px; }
.predicted-block-card { background: rgba(255, 255, 255, 0.03); padding: 15px; border-radius: 8px; }
.predicted-block-card h4 { margin: 0 0 5px 0; }
.predicted-teams-list { list-style: none; padding: 0; margin: 0; }
.predicted-teams-list li { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
.predicted-teams-list li:last-child { border-bottom: none; }
.team-name { color: #ddd; }
.team-projection { color: #888; font-weight: bold; }
.targets-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-top: 20px; }
.target-card { background: rgba(255, 255, 255, 0.03); padding: 15px; border-radius: 8px; }
.target-card h4 { margin: 0 0 15px 0; }
.target-stats { display: flex; flex-direction: column; gap: 8px; }
.target-row { display: flex; justify-content: space-between; align-items: center; }
.target-label { color: #888; font-size: 0.9em; }
.target-value { color: #fff; font-weight: 500; }
.insights-dynamic-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
.insight-dynamic-card { background: rgba(255, 255, 255, 0.03); padding: 20px; border-radius: 8px; text-align: center; }
.insight-dynamic-card h4 { margin: 0 0 10px 0; font-size: 1em; }
.big-stat { font-size: 2em; font-weight: bold; margin: 10px 0; color: #fff; }
.insight-dynamic-card p:last-child { color: #888; font-size: 0.85em; margin: 10px 0 0 0; }
.block-navigation { display: flex; justify-content: space-between; gap: 15px; flex-wrap: wrap; }
.nav-btn { flex: 1; min-width: 150px; padding: 12px 20px; background: rgba(255, 255, 255, 0.05); color: #fff; text-decoration: none; text-align: center; border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.2s; font-weight: 500; }
.nav-btn:hover { background: rgba(255, 255, 255, 0.1); transform: translateY(-2px); }
@media (max-width: 768px) {
    .dynamic-header { flex-direction: column; align-items: flex-start; }
    .methodology-grid, .predicted-blocks-grid, .targets-grid, .insights-dynamic-grid { grid-template-columns: 1fr; }
    .projections-table { font-size: 0.8em; }
    .projections-table th, .projections-table td { padding: 6px 3px; }
}
</style>
