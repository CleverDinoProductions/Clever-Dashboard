<?php
/**
 * Block 4: Relegation Battle (Positions 18-21)
 * The Danger Zone
 */

$blockNumber = 4;
$blockInfo = [
    'title' => 'Relegation Battle',
    'emoji' => '⚠️',
    'color' => '#FFA500',
    'positions' => '18-21',
    'description' => 'The Danger Zone'
];

$tableView = football_stats_get_table_view_combined($db, 'L1', 'league_table_L1', $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$teams = array_values(array_filter($tableView['standings'], function ($team) {
    return $team['position'] >= 18 && $team['position'] <= 21;
}));
$currentMatchday = $tableView['active_matchweek'] ?? (!empty($tableView['standings']) ? max(array_map('intval', array_column($tableView['standings'], 'played'))) : 1);
$last_update = $tableView['last_update'];
$tableViewNavParams = football_stats_get_current_table_view_params();
?>

<div class="block-detail-page">
    <!-- Block Header -->
    <div class="panel" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
        <div class="block-header-section">
            <h2 style="color: <?php echo $blockInfo['color']; ?>;">
                <?php echo $blockInfo['emoji']; ?> Block <?php echo $blockNumber; ?>: <?php echo $blockInfo['title']; ?>
            </h2>
            <span class="position-badge" style="background: rgba(255, 165, 0, 0.2); color: <?php echo $blockInfo['color']; ?>; border: 1px solid <?php echo $blockInfo['color']; ?>;">
                Positions <?php echo $blockInfo['positions']; ?>
            </span>
        </div>
        <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', 'league-one', $currentSubTab ?? 'blocks-4'); ?>
        <p style="margin-top: 15px; font-size: 1.1em; color: #ddd;">
            <?php echo $blockInfo['description']; ?> - Teams in positions 18-21 are in real danger of sliding into the relegation zone with significant consequences.
        </p>
        <p style="margin-top: 10px; color: #888; font-size: 0.9em;">
            📅 Current Matchday: <strong><?php echo $currentMatchday; ?></strong> of 46 •
            <?php if ($currentMatchday < 23): ?>
                First half of season
            <?php elseif ($currentMatchday == 23): ?>
                🎯 Halfway point!
            <?php else: ?>
                Second half of season
            <?php endif; ?>
            <?php if ($last_update && isset($last_update['ts'])): ?>
            <span style="margin-left: 15px;">
                Last updated: <?= date('Y-m-d H:i:s', $last_update['ts'] / 1000) ?>
            </span>
            <?php endif; ?>
        </p>
    </div>

    <!-- Live Table Data -->
    <?php if (!empty($teams)): ?>
    <div class="panel">
        <h3>📊 Live Block 4 Standings</h3>
        <div class="table-wrapper">
            <table class="block-table">
                <thead>
                    <tr style="background: rgba(255, 165, 0, 0.15); border-bottom: 2px solid <?php echo $blockInfo['color']; ?>;">
                        <th>Pos</th>
                        <th>Team</th>
                        <th>Pld</th>
                        <th>W</th>
                        <th>D</th>
                        <th>L</th>
                        <th>GF</th>
                        <th>GA</th>
                        <th>GD</th>
                        <th>Pts</th>
                        <th>PPG</th>
                        <th>Form</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teams as $team):
                        $ppg = $team['played'] > 0 ? round($team['points'] / $team['played'], 2) : 0;
                        $projectedPoints = round($ppg * 46, 1);
                    ?>
                    <tr>
                        <td class="pos-cell" style="color: <?php echo $blockInfo['color']; ?>; font-weight: bold;"><?php echo $team['position']; ?></td>
                        <td class="team-cell"><strong><?php echo htmlspecialchars($team['team_name']); ?></strong></td>
                        <td><?php echo $team['played']; ?></td>
                        <td style="color: #4CAF50;"><?php echo $team['won']; ?></td>
                        <td style="color: #888;"><?php echo $team['drawn']; ?></td>
                        <td style="color: #F44336;"><?php echo $team['lost']; ?></td>
                        <td><?php echo $team['gf']; ?></td>
                        <td><?php echo $team['ga']; ?></td>
                        <td class="<?php echo $team['gd'] > 0 ? 'positive' : ($team['gd'] < 0 ? 'negative' : ''); ?>">
                            <?php echo ($team['gd'] > 0 ? '+' : '') . $team['gd']; ?>
                        </td>
                        <td class="pts-cell" style="background: rgba(255, 165, 0, 0.2); font-weight: bold; font-size: 1.1em;"><?php echo $team['points']; ?></td>
                        <td style="color: <?php echo $blockInfo['color']; ?>; font-weight: bold;"><?php echo $ppg; ?></td>
                        <td class="form-cell"><?php echo $team['form'] ?? '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Projection Targets -->
    <div class="panel">
        <h3>🎯 Escape the Danger Zone</h3>
        <p style="color: #888; margin-bottom: 15px;">
            🚨 Teams in Block 4 must climb above position 21 to avoid relegation •
            Target: <strong>~45+ pts</strong> to escape relegation danger
        </p>
        <div class="projections-grid">
            <?php foreach ($teams as $team):
                $ppg = $team['played'] > 0 ? round($team['points'] / $team['played'], 2) : 0;
                $projectedPoints = round($ppg * 46, 0);
                $remainingGames = 46 - $team['played'];

                // Gap to 21st place safety (just above relegation zone)
                $team21st = null;
                foreach ($tableView['standings'] as $candidateTeam) {
                    if ((int) $candidateTeam['position'] === 21) {
                        $team21st = $candidateTeam;
                        break;
                    }
                }
                $gapToSafety = $team21st ? ((int) $team21st['points'] - (int) $team['points']) : 0;
                // If current team IS 21st, compare with 22nd
                if ($team['position'] <= 21) {
                    $team22nd = null;
                    foreach ($tableView['standings'] as $candidateTeam) {
                        if ((int) $candidateTeam['position'] === 22) {
                            $team22nd = $candidateTeam;
                            break;
                        }
                    }
                    $gapToSafety = $team22nd ? ((int) $team['points'] - (int) $team22nd['points']) : 0;
                }

                // Current PPG trajectory
                $ppgNeededForSafety = $remainingGames > 0 && $gapToSafety > 0 ? round($gapToSafety / $remainingGames, 2) : 0;

                $targets = getLeagueBlockTargets('league-one', $blockNumber);
                $ppgForLower = $remainingGames > 0 ? max(0, round(($targets['lower_pts'] - $team['points']) / $remainingGames, 2)) : 0;
                $ppgForUpper = $remainingGames > 0 ? max(0, round(($targets['upper_pts'] - $team['points']) / $remainingGames, 2)) : 0;
            ?>
            <div class="projection-card" style="border-left: 3px solid <?php echo $blockInfo['color']; ?>;">
                <h4><?php echo htmlspecialchars($team['team_name']); ?></h4>
                <div class="projection-stats">
                    <div class="proj-row">
                        <span class="proj-label">Current PPG:</span>
                        <span class="proj-value" style="color: <?php echo $ppg >= 1.0 ? '#FFA500' : '#F44336'; ?>;"><?php echo $ppg; ?></span>
                    </div>
                    <div class="proj-row">
                        <span class="proj-label">Current Points:</span>
                        <span class="proj-value"><strong><?php echo $team['points']; ?></strong></span>
                    </div>
                    <?php if ($team['position'] > 21): ?>
                    <div class="proj-row">
                        <span class="proj-label">Gap to safety (21st):</span>
                        <span class="proj-value" style="color: #F44336;">
                            -<?php echo abs($gapToSafety); ?> pts
                        </span>
                    </div>
                    <?php else: ?>
                    <div class="proj-row">
                        <span class="proj-label">Lead over relegation:</span>
                        <span class="proj-value" style="color: <?php echo $gapToSafety >= 5 ? '#4CAF50' : '#FFA500'; ?>;">
                            +<?php echo $gapToSafety; ?> pts
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="proj-row">
                        <span class="proj-label">Games remaining:</span>
                        <span class="proj-value"><?php echo $remainingGames; ?></span>
                    </div>
                    <div class="proj-row" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px; margin-top: 10px;">
                        <span class="proj-label">🏁 Season End (MD46):</span>
                        <span class="proj-value" style="font-size: 1.2em; color: <?php echo $projectedPoints >= 45 ? '#FFA500' : '#F44336'; ?>;">
                            <strong><?php echo $projectedPoints; ?> pts</strong>
                        </span>
                    </div>
                    <div class="proj-row" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px; margin-top: 10px;">
                        <span class="proj-label">Lower Target:</span>
                        <span class="proj-value"><?php echo $targets['lower_pts']; ?> pts <small style="color: #888;">(<?php echo $targets['lower_label']; ?>)</small></span>
                    </div>
                    <div class="proj-row">
                        <span class="proj-label" style="padding-left: 10px;">PPG needed:</span>
                        <?php if ($team['points'] >= $targets['lower_pts']): ?>
                        <span class="proj-value success">✅ Achieved</span>
                        <?php else: ?>
                        <span class="proj-value" style="color: <?php echo $ppgForLower <= $ppg ? '#4CAF50' : '#FF9800'; ?>;"><?php echo $ppgForLower; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="proj-row">
                        <span class="proj-label">Upper Target:</span>
                        <span class="proj-value"><?php echo $targets['upper_pts']; ?> pts <small style="color: #888;">(<?php echo $targets['upper_label']; ?>)</small></span>
                    </div>
                    <div class="proj-row">
                        <span class="proj-label" style="padding-left: 10px;">PPG needed:</span>
                        <?php if ($team['points'] >= $targets['upper_pts']): ?>
                        <span class="proj-value success">✅ Achieved</span>
                        <?php else: ?>
                        <span class="proj-value" style="color: <?php echo $ppgForUpper <= $ppg ? '#4CAF50' : '#FF9800'; ?>;"><?php echo $ppgForUpper; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="panel" style="background: rgba(255, 152, 0, 0.1); border-left: 3px solid #FF9800;">
        <h3>⚠️ No Live Data Available</h3>
        <p>Database connection not available or no teams in Block 4 positions.</p>
    </div>
    <?php endif; ?>

    <!-- Block Characteristics -->
    <div class="panel">
        <h3>🎯 Block 4 Escape Requirements</h3>
        <div class="benchmarks-grid">
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
                <h4>⚠️ Danger Zone Reality</h4>
                <ul>
                    <li><strong>Current PPG:</strong> 0.9-1.1 (risky)</li>
                    <li><strong>Typical at MD23:</strong> 18-25 points</li>
                    <li><strong>Typical Season End:</strong> 40-48 points</li>
                    <li><strong>Win Rate:</strong> 30-38%</li>
                </ul>
            </div>
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
                <h4>💡 Escape Strategy</h4>
                <ul>
                    <li>Win six-pointer matches (vs Block 4/5)</li>
                    <li>Unbeaten home runs crucial</li>
                    <li>January signings can transform form</li>
                    <li>Set pieces and work-rate often decisive</li>
                </ul>
            </div>
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
                <h4>📊 Key Milestones</h4>
                <ul>
                    <li><strong>Christmas safety:</strong> 22+ pts</li>
                    <li><strong>End of Jan target:</strong> 30+ pts</li>
                    <li><strong>Easter security:</strong> 38+ pts</li>
                    <li><strong>Final safety target:</strong> 45+ pts</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Historical Context -->
    <div class="panel">
        <h3>📜 Historical Danger Zone Data - League One</h3>
        <div class="history-table">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: rgba(255, 165, 0, 0.1); border-bottom: 2px solid <?php echo $blockInfo['color']; ?>;">
                        <th style="padding: 12px; text-align: left;">Scenario</th>
                        <th style="padding: 12px; text-align: center;">Points Range</th>
                        <th style="padding: 12px; text-align: center;">Outcome</th>
                        <th style="padding: 12px; text-align: center;">PPG</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <td style="padding: 10px;"><strong>Likely Survived</strong></td>
                        <td style="padding: 10px; text-align: center;">45-49 pts</td>
                        <td style="padding: 10px; text-align: center; color: #4CAF50;">85%+ Safe</td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $blockInfo['color']; ?>;"><strong>1.0-1.1</strong></td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <td style="padding: 10px;"><strong>Nervous Finish</strong></td>
                        <td style="padding: 10px; text-align: center;">41-44 pts</td>
                        <td style="padding: 10px; text-align: center; color: #FFA500;">60% Safe</td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $blockInfo['color']; ?>;"><strong>0.95-1.0</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px;"><strong>In Real Danger</strong></td>
                        <td style="padding: 10px; text-align: center;">38-40 pts</td>
                        <td style="padding: 10px; text-align: center; color: #F44336;">50/50</td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $blockInfo['color']; ?>;"><strong>0.90-0.95</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="insight-box" style="background: rgba(255, 165, 0, 0.1); border-left: 3px solid <?php echo $blockInfo['color']; ?>; margin-top: 20px; padding: 15px; border-radius: 5px;">
            <h4>💡 Key Insight</h4>
            <p>
                Teams in Block 4 are one bad run away from the relegation zone and one good run from safety.
                Six-pointer matches against other Block 4/5 teams are absolutely crucial.
                <strong>Teams in 18th-21st at Christmas survive approximately 50-60% of the time</strong> —
                there is genuine hope, but improvement in form is non-negotiable.
            </p>
        </div>
    </div>

    <!-- Navigation -->
    <div class="panel">
        <div class="block-navigation">
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'league-one', 'blocks-3', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="nav-btn">← Block 3</a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'league-one', 'blocks-dynamic', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="nav-btn" style="background: rgba(255, 165, 0, 0.1); color: <?php echo $blockInfo['color']; ?>; border-color: <?php echo $blockInfo['color']; ?>;">🔥 Live Data</a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'league-one', 'blocks-5', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="nav-btn">Block 5 →</a>
        </div>
    </div>
</div>

<style>
.block-detail-page { max-width: 1400px; margin: 0 auto; }
.block-header-section { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
.block-header-section h2 { margin: 0; font-size: 2em; }
.position-badge { padding: 8px 15px; border-radius: 6px; font-size: 0.9em; font-weight: bold; }
.table-wrapper { overflow-x: auto; margin-top: 15px; }
.block-table { width: 100%; border-collapse: collapse; }
.block-table th, .block-table td { padding: 12px 8px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
.block-table th { font-weight: 600; font-size: 0.9em; text-transform: uppercase; }
.block-table td { font-size: 0.95em; }
.team-cell { text-align: left !important; }
.pos-cell { font-size: 1.1em; }
.pts-cell { font-size: 1.1em; }
.positive { color: #4CAF50; }
.negative { color: #F44336; }
.form-cell { font-family: monospace; font-size: 0.85em; }
.projections-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
.projection-card { background: rgba(255, 255, 255, 0.03); padding: 20px; border-radius: 8px; border-left: 3px solid; }
.projection-card h4 { margin: 0 0 15px 0; color: #fff; font-size: 1.1em; }
.projection-stats { display: flex; flex-direction: column; gap: 8px; }
.proj-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; }
.proj-label { color: #888; font-size: 0.9em; }
.proj-value { color: #fff; font-weight: 500; }
.proj-alert { padding: 10px; border-radius: 5px; margin-top: 10px; }
.benchmarks-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
.benchmark-card { background: rgba(255, 255, 255, 0.03); padding: 20px; border-radius: 8px; border-left: 4px solid; }
.benchmark-card h4 { margin: 0 0 15px 0; color: #fff; }
.benchmark-card ul { list-style: none; padding: 0; margin: 0; }
.benchmark-card ul li { padding: 8px 0; color: #bbb; font-size: 0.95em; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
.benchmark-card ul li:last-child { border-bottom: none; }
.block-navigation { display: flex; justify-content: space-between; gap: 15px; flex-wrap: wrap; }
.nav-btn { flex: 1; min-width: 150px; padding: 12px 20px; background: rgba(255, 255, 255, 0.05); color: #fff; text-decoration: none; text-align: center; border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.2s; font-weight: 500; }
.nav-btn:hover { background: rgba(255, 255, 255, 0.1); transform: translateY(-2px); }
@media (max-width: 768px) {
    .block-header-section { flex-direction: column; align-items: flex-start; }
    .projections-grid, .benchmarks-grid { grid-template-columns: 1fr; }
    .block-table { font-size: 0.85em; }
    .block-table th, .block-table td { padding: 8px 4px; }
}
</style>
