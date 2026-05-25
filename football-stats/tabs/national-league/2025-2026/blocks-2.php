<?php
/**
 * Block 2: Playoff Contenders (Positions 2-7)
 * Playoff spots for promotion to League Two
 */

include_once __DIR__ . '/../../../includes/blocks-functions.php';

$blockNumber = 2;
$blockInfo = [
    'title' => 'Playoff Contenders',
    'emoji' => '🎯',
    'color' => '#5865F2',
    'positions' => '2-7',
    'description' => 'Playoff spots for promotion to League Two'
];

$tableView = football_stats_get_table_view_combined($db, 'NL', 'league_table_NL', $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$teams = array_values(array_filter($tableView['standings'], function ($team) {
    return $team['position'] >= 2 && $team['position'] <= 7;
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
        <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', 'national-league', $currentSubTab ?? 'blocks-2'); ?>
        <p style="margin-top: 15px; font-size: 1.1em; color: #ddd;">
            <?php echo $blockInfo['description']; ?> - Six teams battle for playoff spots with a chance to reach League Two via the playoffs.
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
                $targets = getLeagueBlockTargets('national-league', $blockNumber);
                $ppgForLower = $remainingGames > 0 ? max(0, round(($targets['lower_pts'] - $team['points']) / $remainingGames, 2)) : 0;
                $ppgForUpper = $remainingGames > 0 ? max(0, round(($targets['upper_pts'] - $team['points']) / $remainingGames, 2)) : 0;
                // Find position 2 team for gap calculation
                $team2nd = null;
                foreach ($tableView['standings'] as $candidateTeam) {
                    if ((int) $candidateTeam['position'] === 2) {
                        $team2nd = $candidateTeam;
                        break;
                    }
                }
                $team8th = null;
                foreach ($tableView['standings'] as $candidateTeam) {
                    if ((int) $candidateTeam['position'] === 8) {
                        $team8th = $candidateTeam;
                        break;
                    }
                }
                $gapTo2nd = $team2nd ? ((int) $team2nd['points'] - (int) $team['points']) : 0;
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
                    <?php if ($team['position'] > 2 && $gapTo2nd > 0): ?>
                    <div class="proj-row">
                        <span class="proj-label">Gap to 2nd (auto):</span>
                        <span class="proj-value" style="color: #FFA500;">-<?php echo $gapTo2nd; ?> pts</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($gapTo8th >= 0): ?>
                    <div class="proj-row">
                        <span class="proj-label">Lead over 8th:</span>
                        <span class="proj-value" style="color: #4CAF50;">+<?php echo $gapTo8th; ?> pts</span>
                    </div>
                    <?php endif; ?>
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
                        <span class="proj-label">Games remaining:</span>
                        <span class="proj-value"><?php echo $remainingGames; ?></span>
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
        <p>Database connection not available or no teams in Block 2 positions.</p>
    </div>
    <?php endif; ?>

    <!-- Block Characteristics -->
    <div class="panel">
        <h3>🎯 Block 2 Targets & Benchmarks</h3>
        <div class="benchmarks-grid">
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
                <h4>🎯 Playoff Qualification</h4>
                <ul>
                    <li><strong>Target PPG:</strong> 1.7-2.1</li>
                    <li><strong>Halfway (MD23):</strong> 35-42 points</li>
                    <li><strong>Season End (MD46):</strong> 70-89 points</li>
                    <li><strong>Win Rate:</strong> 50-65%</li>
                </ul>
            </div>
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
                <h4>⚽ Playoff Strategy</h4>
                <ul>
                    <li>Home advantage matters in semis</li>
                    <li>Form in final 10 games crucial</li>
                    <li>Squad depth tested at season end</li>
                    <li>One-off Wembley final decides fate</li>
                </ul>
            </div>
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
                <h4>📊 Positions 2-7 Context</h4>
                <ul>
                    <li>6 teams in playoff zone</li>
                    <li>2nd place: top seeding advantage</li>
                    <li>7th place: toughest route to final</li>
                    <li>Every point shapes playoff matchups</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Historical Context -->
    <div class="panel">
        <h3>📜 Historical Block 2 Points</h3>
        <div class="history-table">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: rgba(88, 101, 242, 0.1); border-bottom: 2px solid <?php echo $blockInfo['color']; ?>;">
                        <th style="padding: 12px; text-align: left;">Position</th>
                        <th style="padding: 12px; text-align: center;">Typical Points</th>
                        <th style="padding: 12px; text-align: center;">At Halfway (MD23)</th>
                        <th style="padding: 12px; text-align: center;">PPG Required</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <td style="padding: 10px;"><strong>2nd Place</strong></td>
                        <td style="padding: 10px; text-align: center;">83-92 pts</td>
                        <td style="padding: 10px; text-align: center;">42-46 pts</td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $blockInfo['color']; ?>;"><strong>2.0-2.2</strong></td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <td style="padding: 10px;"><strong>3rd-4th Place</strong></td>
                        <td style="padding: 10px; text-align: center;">76-84 pts</td>
                        <td style="padding: 10px; text-align: center;">38-42 pts</td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $blockInfo['color']; ?>;"><strong>1.9-2.1</strong></td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <td style="padding: 10px;"><strong>5th-6th Place</strong></td>
                        <td style="padding: 10px; text-align: center;">72-78 pts</td>
                        <td style="padding: 10px; text-align: center;">35-39 pts</td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $blockInfo['color']; ?>;"><strong>1.8-2.0</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px;"><strong>7th Place (Last Playoff)</strong></td>
                        <td style="padding: 10px; text-align: center;">68-74 pts</td>
                        <td style="padding: 10px; text-align: center;">33-37 pts</td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $blockInfo['color']; ?>;"><strong>1.7-1.9</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="insight-box" style="background: rgba(88, 101, 242, 0.1); border-left: 3px solid <?php echo $blockInfo['color']; ?>; margin-top: 20px; padding: 15px; border-radius: 5px;">
            <h4>💡 Key Insight</h4>
            <p>
                The National League playoff is one of football's most dramatic spectacles. The gap between 2nd and 7th
                is typically <strong>15-20 points</strong>, yet any of these 6 teams can win at Wembley. Home advantage
                in the semi-finals is significant — the higher-seeded team wins the semi-final approximately
                <strong>60% of the time</strong>.
            </p>
        </div>
    </div>

    <!-- Navigation -->
    <div class="panel">
        <div class="block-navigation">
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'national-league', 'blocks-1', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="nav-btn">← Block 1</a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'national-league', 'blocks-dynamic', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="nav-btn" style="background: rgba(88, 101, 242, 0.1); color: <?php echo $blockInfo['color']; ?>; border-color: <?php echo $blockInfo['color']; ?>;">🔥 Live Data</a>
            <a href="<?php echo htmlspecialchars(build_tab_url($currentMainTab ?? '2025-2026', $currentLeague ?? 'national-league', 'blocks-3', $tableViewNavParams), ENT_QUOTES, 'UTF-8'); ?>" class="nav-btn">Block 3 →</a>
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
