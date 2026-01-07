<?php
/**
 * Block 1: Title Contenders (Positions 1-4)
 * Champions League qualification zone
 */

$blockNumber = 1;
$blockInfo = [
    'title' => 'Title Contenders',
    'emoji' => '🏆',
    'color' => '#FFD700',
    'positions' => '1-4',
    'description' => 'Champions League Qualification Zone'
];

// Fetch league table - $db comes from parent include
$stmt = $db->query("SELECT * FROM league_table WHERE position BETWEEN 1 AND 4 ORDER BY position ASC");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current matchday
$matchdayQuery = $db->query("SELECT MAX(played) as current_matchday FROM league_table");
$currentMatchday = $matchdayQuery->fetch(PDO::FETCH_ASSOC)['current_matchday'] ?? 1;

// Get last update timestamp
$last_update = $db->query("SELECT MAX(updated_at) as ts FROM league_table")->fetch();
?>

<div class="block-detail-page">
    <!-- Block Header -->
    <div class="panel" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
        <div class="block-header-section">
            <h2 style="color: <?php echo $blockInfo['color']; ?>;">
                <?php echo $blockInfo['emoji']; ?> Block <?php echo $blockNumber; ?>: <?php echo $blockInfo['title']; ?>
            </h2>
            <span class="position-badge" style="background: rgba(255, 215, 0, 0.2); color: <?php echo $blockInfo['color']; ?>; border: 1px solid <?php echo $blockInfo['color']; ?>;">
                Positions <?php echo $blockInfo['positions']; ?>
            </span>
        </div>
        <p style="margin-top: 15px; font-size: 1.1em; color: #ddd;">
            <?php echo $blockInfo['description']; ?> - The elite tier of English football where teams compete for
            the Premier League title and automatic Champions League qualification.
        </p>
        <p style="margin-top: 10px; color: #888; font-size: 0.9em;">
            📅 Current Matchday: <strong><?php echo $currentMatchday; ?></strong> of 38 • 
            <?php if ($currentMatchday < 19): ?>
                First half of season
            <?php elseif ($currentMatchday == 19): ?>
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
        <h3>📊 Live Block 1 Standings</h3>
        <div class="table-wrapper">
            <table class="block-table">
                <thead>
                    <tr style="background: rgba(255, 215, 0, 0.15); border-bottom: 2px solid <?php echo $blockInfo['color']; ?>;">
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
                        $projectedPoints = round($ppg * 38, 1);
                        $halfwayTarget = round($ppg * 19, 1);
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
                        <td class="pts-cell" style="background: rgba(255, 215, 0, 0.2); font-weight: bold; font-size: 1.1em;"><?php echo $team['points']; ?></td>
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
                $projectedPoints = round($ppg * 38, 0);
                $halfwayTarget = round($ppg * 19, 0);
                $halfwayActual = $team['points'];
                $remainingGames = 38 - $team['played'];
                $pointsNeededFor70 = max(0, 70 - $team['points']);
                $ppgNeededFor70 = $remainingGames > 0 ? round($pointsNeededFor70 / $remainingGames, 2) : 0;
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
                    <div class="proj-row" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px; margin-top: 10px;">
                        <span class="proj-label">🎯 Halfway (MD19):</span>
                        <span class="proj-value"><?php echo $halfwayTarget; ?> pts target</span>
                    </div>
                    <?php if ($currentMatchday >= 19): ?>
                    <div class="proj-row">
                        <span class="proj-label">Actual at MD19:</span>
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
                        <span class="proj-label">🏁 Season End (MD38):</span>
                        <span class="proj-value" style="font-size: 1.2em; color: <?php echo $blockInfo['color']; ?>;">
                            <strong><?php echo $projectedPoints; ?> pts</strong>
                        </span>
                    </div>
                    <div class="proj-row">
                        <span class="proj-label">Minimum for CL:</span>
                        <span class="proj-value">~70 pts</span>
                    </div>
                    <div class="proj-row">
                        <span class="proj-label">Games remaining:</span>
                        <span class="proj-value"><?php echo $remainingGames; ?></span>
                    </div>
                    <?php if ($projectedPoints < 70): ?>
                    <div class="proj-alert" style="background: rgba(255, 152, 0, 0.1); padding: 10px; border-radius: 5px; margin-top: 10px; border-left: 3px solid #FF9800;">
                        <strong>⚠️ PPG needed for 70pts:</strong> <?php echo $ppgNeededFor70; ?>
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
        <p>Database connection not available or no teams in Block 1 positions. The framework explanation and targets are shown below.</p>
    </div>
    <?php endif; ?>

    <!-- Block Characteristics -->
    <div class="panel">
        <h3>🎯 Block 1 Targets & Benchmarks</h3>
        <div class="benchmarks-grid">
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
                <h4>🏆 Title Challenge</h4>
                <ul>
                    <li><strong>Target PPG:</strong> 2.3-2.5</li>
                    <li><strong>Halfway (MD19):</strong> 44-48 points</li>
                    <li><strong>Season End (MD38):</strong> 88-95 points</li>
                    <li><strong>Win Rate:</strong> 70%+</li>
                </ul>
            </div>
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
                <h4>🥈 2nd-3rd Place</h4>
                <ul>
                    <li><strong>Target PPG:</strong> 2.0-2.2</li>
                    <li><strong>Halfway (MD19):</strong> 38-42 points</li>
                    <li><strong>Season End (MD38):</strong> 76-84 points</li>
                    <li><strong>Win Rate:</strong> 60-65%</li>
                </ul>
            </div>
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
                <h4>⚽ 4th Place (Last CL Spot)</h4>
                <ul>
                    <li><strong>Target PPG:</strong> 1.8-2.0</li>
                    <li><strong>Halfway (MD19):</strong> 34-38 points</li>
                    <li><strong>Season End (MD38):</strong> 68-76 points</li>
                    <li><strong>Win Rate:</strong> 55%+</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Historical Context -->
    <div class="panel">
        <h3>📜 Historical Block 1 Points</h3>
        <div class="history-table">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: rgba(255, 215, 0, 0.1); border-bottom: 2px solid <?php echo $blockInfo['color']; ?>;">
                        <th style="padding: 12px; text-align: left;">Position</th>
                        <th style="padding: 12px; text-align: center;">Typical Points</th>
                        <th style="padding: 12px; text-align: center;">At Halfway (MD19)</th>
                        <th style="padding: 12px; text-align: center;">PPG Required</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <td style="padding: 10px;"><strong>1st - Champions</strong></td>
                        <td style="padding: 10px; text-align: center;">88-95 pts</td>
                        <td style="padding: 10px; text-align: center;">44-48 pts</td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $blockInfo['color']; ?>;"><strong>2.3-2.5</strong></td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <td style="padding: 10px;"><strong>2nd Place</strong></td>
                        <td style="padding: 10px; text-align: center;">78-86 pts</td>
                        <td style="padding: 10px; text-align: center;">39-43 pts</td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $blockInfo['color']; ?>;"><strong>2.0-2.3</strong></td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <td style="padding: 10px;"><strong>3rd Place</strong></td>
                        <td style="padding: 10px; text-align: center;">72-80 pts</td>
                        <td style="padding: 10px; text-align: center;">36-40 pts</td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $blockInfo['color']; ?>;"><strong>1.9-2.1</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px;"><strong>4th - Last CL Spot</strong></td>
                        <td style="padding: 10px; text-align: center;">68-76 pts</td>
                        <td style="padding: 10px; text-align: center;">34-38 pts</td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $blockInfo['color']; ?>;"><strong>1.8-2.0</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="insight-box" style="background: rgba(255, 215, 0, 0.1); border-left: 3px solid <?php echo $blockInfo['color']; ?>; margin-top: 20px; padding: 15px; border-radius: 5px;">
            <h4>💡 Key Insight</h4>
            <p>
                The gap between 4th place (last Champions League spot) and 5th place averages <strong>8-12 points</strong>
                per season. This represents the massive financial and prestige difference between Block 1 and Block 2.
                Teams on 70+ points by season end are virtually guaranteed Champions League football.
            </p>
        </div>
    </div>

    <!-- Navigation -->
    <div class="panel">
        <div class="block-navigation">
            <a href="?tab=premier-league&subtab=blocks-overview" class="nav-btn">← Overview</a>
            <a href="?tab=premier-league&subtab=blocks-dynamic" class="nav-btn" style="background: rgba(255, 215, 0, 0.1); color: <?php echo $blockInfo['color']; ?>; border-color: <?php echo $blockInfo['color']; ?>;">🔥 Live Data</a>
            <a href="?tab=premier-league&subtab=blocks-2" class="nav-btn">Block 2 →</a>
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
