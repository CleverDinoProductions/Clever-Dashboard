<?php
/**
 * Block 2: Playoff Contenders (Positions 4-7)
 * Playoff spots for promotion to League One
 */

$blockNumber = 2;
$blockInfo = [
    'title' => 'Playoff Contenders',
    'emoji' => '🎯',
    'color' => '#5865F2',
    'positions' => '4-7',
    'description' => 'Playoff spots for promotion to League One'
];

$tableView = football_stats_get_table_view_combined($db, 'L2', 'league_table_L2', $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$teams = array_values(array_filter($tableView['standings'], function ($team) {
    return $team['position'] >= 4 && $team['position'] <= 7;
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
            <span class="position-badge" style="background: rgba(88, 101, 242, 0.2); color: <?php echo $blockInfo['color']; ?>; border: 1px solid <?php echo $blockInfo['color']; ?>;">
                Positions <?php echo $blockInfo['positions']; ?>
            </span>
        </div>
        <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', 'league-two', $currentSubTab ?? 'blocks-2'); ?>
        <p style="margin-top: 15px; font-size: 1.1em; color: #ddd;">
            <?php echo $blockInfo['description']; ?> - Four teams battle through the playoffs for one final promotion place to League One.
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
        <h3>📊 Live Block 2 Standings</h3>
        <div class="table-wrapper">
            <table class="block-table">
                <thead>
                    <tr style="background: rgba(88, 101, 242, 0.15); border-bottom: 2px solid <?php echo $blockInfo['color']; ?>;">
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
                        <td class="pts-cell" style="background: rgba(88, 101, 242, 0.2); font-weight: bold; font-size: 1.1em;"><?php echo $team['points']; ?></td>
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
        <h3>🎯 Season Projection Targets</h3>
        <div class="projections-grid">
            <?php foreach ($teams as $team):
                $ppg = $team['played'] > 0 ? round($team['points'] / $team['played'], 2) : 0;
                $projectedPoints = round($ppg * 46, 0);
                $halfwayTarget = round($ppg * 23, 0);
                $halfwayActual = $team['points'];
                $remainingGames = 46 - $team['played'];
                $pointsNeededFor72 = max(0, 72 - $team['points']);
                $ppgNeededFor72 = $remainingGames > 0 ? round($pointsNeededFor72 / $remainingGames, 2) : 0;

                // Gap to 3rd place (last auto spot)
                $team3rd = null;
                foreach ($tableView['standings'] as $candidateTeam) {
                    if ((int) $candidateTeam['position'] === 3) {
                        $team3rd = $candidateTeam;
                        break;
                    }
                }
                $gapTo3rd = $team3rd ? ((int) $team3rd['points'] - (int) $team['points']) : 0;

                // Gap to 8th place (falling out of playoffs)
                $team8th = null;
                foreach ($tableView['standings'] as $candidateTeam) {
                    if ((int) $candidateTeam['position'] === 8) {
                        $team8th = $candidateTeam;
                        break;
                    }
                }
                $gapTo8th = $team8th ? ((int) $team['points'] - (int) $team8th['points']) : 0;
            ?>
            <div class="projection-card" style="border-left: 3px solid <?php echo $blockInfo['color']; ?>;">
                <h4><?php echo htmlspecialchars($team['team_name']); ?></h4>
                <div class="projection-stats">
                    <div class="proj-row">
                        <span class="proj-label">Current PPG:</span>
                        <span class="proj-value" style="color: <?php echo $blockInfo['color']; ?>;"><?php echo $ppg; ?></span>
                    </div>
                    <div class="proj-row">
                        <span class="proj-label">Current Points:</span>
                        <span class="proj-value"><strong><?php echo $team['points']; ?></strong></span>
                    </div>
                    <div class="proj-row">
                        <span class="proj-label">Gap to 3rd (auto):</span>
                        <span class="proj-value" style="color: <?php echo $gapTo3rd <= 5 ? '#FFA500' : '#F44336'; ?>;">
                            <?php echo $gapTo3rd > 0 ? '-' . $gapTo3rd : ($gapTo3rd == 0 ? '=' : '+' . abs($gapTo3rd)); ?> pts
                        </span>
                    </div>
                    <div class="proj-row">
                        <span class="proj-label">Lead over 8th:</span>
                        <span class="proj-value" style="color: <?php echo $gapTo8th >= 3 ? '#4CAF50' : '#FFA500'; ?>;">
                            +<?php echo $gapTo8th; ?> pts
                        </span>
                    </div>
                    <div class="proj-row" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px; margin-top: 10px;">
                        <span class="proj-label">🎯 Halfway (MD23):</span>
                        <span class="proj-value"><?php echo $halfwayTarget; ?> pts target</span>
                    </div>
                    <?php if ($currentMatchday >= 23): ?>
                    <div class="proj-row">
                        <span class="proj-label">Actual at MD23:</span>
                        <span class="proj-value <?php echo $halfwayActual >= $halfwayTarget ? 'success' : 'warning'; ?>">
                            <?php echo $halfwayActual; ?> pts
                            <?php if ($halfwayActual >= $halfwayTarget): ?>
                                ✅
                            <?php else: ?>
                                ⚠️
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="proj-row" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px; margin-top: 10px;">
                        <span class="proj-label">🏁 Season End (MD46):</span>
                        <span class="proj-value" style="font-size: 1.2em; color: <?php echo $blockInfo['color']; ?>;">
                            <strong><?php echo $projectedPoints; ?> pts</strong>
                        </span>
                    </div>
                    <div class="proj-row">
                        <span class="proj-label">Playoff target range:</span>
                        <span class="proj-value">72-84 pts</span>
                    </div>
                    <div class="proj-row">
                        <span class="proj-label">Games remaining:</span>
                        <span class="proj-value"><?php echo $remainingGames; ?></span>
                    </div>
                    <?php if ($projectedPoints < 72): ?>
                    <div class="proj-alert" style="background: rgba(255, 152, 0, 0.1); padding: 10px; border-radius: 5px; margin-top: 10px; border-left: 3px solid #FF9800;">
                        <strong>⚠️ PPG needed for 72pts (playoff target):</strong> <?php echo $ppgNeededFor72; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="panel" style="background: rgba(255, 152, 0, 0.1); border-left: 3px solid #FF9800;">
        <h3>⚠️ No Live Data Available</h3>
        <p>Database connection not available or no teams in Block 2 positions.</p>
    </div>
    <?php endif; ?>

    <!-- Block Characteristics -->
    <div class="panel">
        <h3>🎯 Block 2 Targets & Benchmarks</h3>
        <div class="benchmarks-grid">
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
                <h4>🎯 4th Place (Best Playoff Seed)</h4>
                <ul>
                    <li><strong>Target PPG:</strong> 1.9-2.1</li>
                    <li><strong>Halfway (MD23):</strong> 38-42 points</li>
                    <li><strong>Season End (MD46):</strong> 82-84 points</li>
                    <li><strong>Win Rate:</strong> 58-63%</li>
                </ul>
            </div>
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
                <h4>⚽ 5th-6th Place</h4>
                <ul>
                    <li><strong>Target PPG:</strong> 1.7-1.9</li>
                    <li><strong>Halfway (MD23):</strong> 34-38 points</li>
                    <li><strong>Season End (MD46):</strong> 75-82 points</li>
                    <li><strong>Win Rate:</strong> 52-58%</li>
                </ul>
            </div>
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
                <h4>🔚 7th Place (Lowest Playoff Seed)</h4>
                <ul>
                    <li><strong>Target PPG:</strong> 1.6-1.8</li>
                    <li><strong>Halfway (MD23):</strong> 31-36 points</li>
                    <li><strong>Season End (MD46):</strong> 72-76 points</li>
                    <li><strong>Win Rate:</strong> 48-55%</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Playoff Structure -->
    <div class="panel">
        <h3>📜 League Two Playoff Structure</h3>
        <div class="history-table">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: rgba(88, 101, 242, 0.1); border-bottom: 2px solid <?php echo $blockInfo['color']; ?>;">
                        <th style="padding: 12px; text-align: left;">Stage</th>
                        <th style="padding: 12px; text-align: center;">Teams</th>
                        <th style="padding: 12px; text-align: center;">Format</th>
                        <th style="padding: 12px; text-align: center;">Advantage</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <td style="padding: 10px;"><strong>Semi-Final 1</strong></td>
                        <td style="padding: 10px; text-align: center;">4th vs 7th</td>
                        <td style="padding: 10px; text-align: center;">Two legs</td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $blockInfo['color']; ?>;"><strong>4th gets 2nd leg at home</strong></td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <td style="padding: 10px;"><strong>Semi-Final 2</strong></td>
                        <td style="padding: 10px; text-align: center;">5th vs 6th</td>
                        <td style="padding: 10px; text-align: center;">Two legs</td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $blockInfo['color']; ?>;"><strong>5th gets 2nd leg at home</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px;"><strong>Final</strong></td>
                        <td style="padding: 10px; text-align: center;">SF1 winner vs SF2 winner</td>
                        <td style="padding: 10px; text-align: center;">One-off at Wembley</td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $blockInfo['color']; ?>;"><strong>Neutral venue</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="insight-box" style="background: rgba(88, 101, 242, 0.1); border-left: 3px solid <?php echo $blockInfo['color']; ?>; margin-top: 20px; padding: 15px; border-radius: 5px;">
            <h4>💡 Playoff Reality</h4>
            <p>
                The League Two playoffs are famously unpredictable. Lower seeds regularly beat higher seeds.
                Historically, the <strong>7th place team wins the playoffs approximately 20% of the time</strong>.
                Form in the last 10 games of the regular season often matters more than final league position
                when it comes to playoff success.
            </p>
        </div>
    </div>

    <!-- Navigation -->
    <div class="panel">
        <div class="block-navigation">
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'league-two', 'blocks-1', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="nav-btn">← Block 1</a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'league-two', 'blocks-dynamic', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="nav-btn" style="background: rgba(88, 101, 242, 0.1); color: <?php echo $blockInfo['color']; ?>; border-color: <?php echo $blockInfo['color']; ?>;">🔥 Live Data</a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'league-two', 'blocks-3', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="nav-btn">Block 3 →</a>
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
.proj-value.success { color: #4CAF50; }
.proj-value.warning { color: #FF9800; }
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
