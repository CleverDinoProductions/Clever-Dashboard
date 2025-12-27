<?php
// Block 1: Positions 1-4 - Title Contenders - Dynamic Data
require_once __DIR__ . '/../../includes/blocks-functions.php';

$blockNumber = 1;
$teams = getBlockTeams($db, $blockNumber);
$stats = getBlockStats($db, $blockNumber);
$goalsStatus = getBlockGoalsStatus($db, $blockNumber);
$insights = getBlockInsights($db, $blockNumber);
$xgData = getBlockXG($db, $blockNumber);

$blockInfo = [
    'title' => 'Title Contenders',
    'icon' => '🏆',
    'color' => '#FFD700',
    'description' => 'Teams competing for the Premier League title and automatic Champions League qualification. These sides demonstrate attacking mentality, high confidence, and consistent winning records.'
];
?>
<div class="block-detail-wrapper">
    <div class="panel">
        <div class="block-title-header block-1-theme">
            <h2><?php echo $blockInfo['icon']; ?> Block <?php echo $blockNumber; ?>: <?php echo $blockInfo['title']; ?></h2>
            <span class="position-badge">Positions <?php echo (($blockNumber-1)*4)+1; ?>-<?php echo $blockNumber*4; ?></span>
        </div>
        <p class="block-description"><?php echo $blockInfo['description']; ?></p>
    </div>

    <!-- Goals Achievement Status -->
    <div class="panel">
        <h3>🎯 Goals Achievement Status</h3>
        <div class="goals-overview">
            <div class="goal-progress-card">
                <h4>Overall Progress</h4>
                <div class="progress-circle">
                    <div class="progress-value" style="color: <?php echo $goalsStatus['overall_status'] >= 75 ? '#43b581' : ($goalsStatus['overall_status'] >= 50 ? '#faa61a' : '#f04747'); ?>">
                        <?php echo $goalsStatus['overall_status']; ?>%
                    </div>
                    <div class="progress-label"><?php echo $goalsStatus['status_text']; ?></div>
                </div>
            </div>
            
            <div class="goal-targets">
                <div class="target-item <?php echo $goalsStatus['achievements']['ppg_met'] ? 'met' : 'unmet'; ?>">
                    <span class="target-icon"><?php echo $goalsStatus['achievements']['ppg_met'] ? '✅' : '❌'; ?></span>
                    <div>
                        <strong>Points Per Game</strong>
                        <p><?php echo $goalsStatus['current']['avg_ppg']; ?> / <?php echo $goalsStatus['goals']['target_ppg']; ?> target</p>
                    </div>
                </div>
                
                <div class="target-item <?php echo $goalsStatus['achievements']['win_rate_met'] ? 'met' : 'unmet'; ?>">
                    <span class="target-icon"><?php echo $goalsStatus['achievements']['win_rate_met'] ? '✅' : '❌'; ?></span>
                    <div>
                        <strong>Win Rate</strong>
                        <p><?php echo $goalsStatus['current']['avg_win_rate']; ?>% / <?php echo $goalsStatus['goals']['target_win_rate']; ?>% target</p>
                    </div>
                </div>
                
                <div class="target-item <?php echo $goalsStatus['achievements']['gd_met'] ? 'met' : 'unmet'; ?>">
                    <span class="target-icon"><?php echo $goalsStatus['achievements']['gd_met'] ? '✅' : '❌'; ?></span>
                    <div>
                        <strong>Goal Difference</strong>
                        <p><?php echo $goalsStatus['current']['avg_gd'] > 0 ? '+' : ''; ?><?php echo $goalsStatus['current']['avg_gd']; ?> / <?php echo $goalsStatus['goals']['target_gd'] > 0 ? '+' : ''; ?><?php echo $goalsStatus['goals']['target_gd']; ?> target</p>
                    </div>
                </div>
                
                <div class="target-item <?php echo $goalsStatus['achievements']['projection_met'] ? 'met' : 'unmet'; ?>">
                    <span class="target-icon"><?php echo $goalsStatus['achievements']['projection_met'] ? '✅' : '❌'; ?></span>
                    <div>
                        <strong>Projected Points</strong>
                        <p><?php echo $goalsStatus['current']['projected_points']; ?> / <?php echo $goalsStatus['goals']['target_points_38']; ?> target</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-Time Insights -->
    <?php if (!empty($insights)): ?>
    <div class="panel">
        <h3>💡 Real-Time Insights</h3>
        <div class="insights-grid">
            <?php foreach ($insights as $insight): ?>
            <div class="insight-card insight-<?php echo $insight['type']; ?>">
                <div class="insight-header">
                    <span class="insight-icon"><?php echo $insight['icon']; ?></span>
                    <strong><?php echo $insight['title']; ?></strong>
                </div>
                <p><?php echo $insight['message']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Current Teams in Block -->
    <div class="panel">
        <h3>📈 Current Block <?php echo $blockNumber; ?> Teams</h3>
        <div class="teams-table-wrapper">
            <table class="standings-table block-1-table">
                <thead>
                    <tr>
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
                        <th>Win %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teams as $team): ?>
                    <tr>
                        <td class='pos-cell'><?php echo $team['position']; ?></td>
                        <td class='team-cell'><strong><?php echo $team['team_name']; ?></strong></td>
                        <td><?php echo $team['played']; ?></td>
                        <td><?php echo $team['won']; ?></td>
                        <td><?php echo $team['drawn']; ?></td>
                        <td><?php echo $team['lost']; ?></td>
                        <td><?php echo $team['gf']; ?></td>
                        <td><?php echo $team['ga']; ?></td>
                        <td class='gd-cell'><?php echo $team['gd'] > 0 ? '+' : ''; ?><?php echo $team['gd']; ?></td>
                        <td class='pts-cell'><strong><?php echo $team['points']; ?></strong></td>
                        <td><?php echo $team['ppg']; ?></td>
                        <td><?php echo $team['win_rate']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Block Statistics -->
    <div class="panel">
        <h3>📊 Block Statistics</h3>
        <div class="block-stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo round($stats['avg_ppg'], 2); ?></div>
                <div class="stat-label">Average PPG</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo round($stats['avg_points'], 1); ?></div>
                <div class="stat-label">Average Points</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['point_spread']; ?></div>
                <div class="stat-label">Point Spread</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo round($stats['avg_win_rate'], 1); ?>%</div>
                <div class="stat-label">Average Win Rate</div>
            </div>
        </div>
    </div>

    <!-- xG Analysis -->
    <?php if (!empty($xgData)): ?>
    <div class="panel">
        <h3>📉 Expected Goals (xG) Analysis</h3>
        <div class="xg-table-wrapper">
            <table class="xg-table">
                <thead>
                    <tr>
                        <th>Team</th>
                        <th>xG For</th>
                        <th>xG Against</th>
                        <th>xG Diff</th>
                        <th>Actual GF</th>
                        <th>Actual GA</th>
                        <th>Finishing</th>
                        <th>Defensive</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($xgData as $xg): ?>
                    <tr>
                        <td class="team-cell"><strong><?php echo $xg['team_name']; ?></strong></td>
                        <td><?php echo round($xg['xg_for'], 2); ?></td>
                        <td><?php echo round($xg['xg_against'], 2); ?></td>
                        <td><?php echo $xg['xg_diff'] > 0 ? '+' : ''; ?><?php echo round($xg['xg_diff'], 2); ?></td>
                        <td><?php echo $xg['actual_gf']; ?></td>
                        <td><?php echo $xg['actual_ga']; ?></td>
                        <td class="<?php echo $xg['finishing_diff'] > 0 ? 'positive' : ($xg['finishing_diff'] < 0 ? 'negative' : ''); ?>">
                            <?php echo $xg['finishing_diff'] > 0 ? '+' : ''; ?><?php echo round($xg['finishing_diff'], 1); ?>
                        </td>
                        <td class="<?php echo $xg['defensive_diff'] > 0 ? 'positive' : ($xg['defensive_diff'] < 0 ? 'negative' : ''); ?>">
                            <?php echo $xg['defensive_diff'] > 0 ? '+' : ''; ?><?php echo round($xg['defensive_diff'], 1); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="note">
            <strong>Finishing:</strong> Positive = scoring more than expected, Negative = missing chances<br>
            <strong>Defensive:</strong> Positive = conceding less than expected, Negative = poor defending
        </p>
    </div>
    <?php endif; ?>

    <!-- Block Characteristics -->
    <div class="panel">
        <h3>🔑 Block 1 Characteristics</h3>
        <div class="characteristics-grid">
            <div class="char-card">
                <h4>🎯 Mentality</h4>
                <ul>
                    <li>Winning expectation every match</li>
                    <li>Attacking football prioritized</li>
                    <li>High press and possession focus</li>
                    <li>Squad depth for rotation</li>
                </ul>
            </div>
            
            <div class="char-card">
                <h4>📈 Typical Metrics</h4>
                <ul>
                    <li>Points Per Game: 2.0+</li>
                    <li>Win Rate: 65%+</li>
                    <li>Goals Per Game: 2.0+</li>
                    <li>Clean Sheets: 40%+</li>
                </ul>
            </div>
            
            <div class="char-card">
                <h4>🛡️ Tactical Approach</h4>
                <ul>
                    <li>Dominant possession strategies</li>
                    <li>High defensive line</li>
                    <li>Quick transitions</li>
                    <li>Set-piece excellence</li>
                </ul>
            </div>
            
            <div class="char-card">
                <h4>💰 Investment Level</h4>
                <ul>
                    <li>High transfer spend</li>
                    <li>Premium wage bills</li>
                    <li>Elite coaching staff</li>
                    <li>World-class facilities</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="panel">
        <div class="block-navigation">
            <a href="?tab=premier-league&subtab=blocks-overview" class="nav-btn">← Back to Overview</a>
            <a href="?tab=premier-league&subtab=blocks-2" class="nav-btn block-2-nav">Next: Block 2 →</a>
        </div>
    </div>
</div>

<style>
.block-detail-wrapper {
    max-width: 1400px;
    margin: 0 auto;
}

.block-title-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.block-1-theme h2 {
    color: #FFD700;
}

.position-badge {
    background: rgba(255, 215, 0, 0.2);
    color: #FFD700;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    border: 2px solid #FFD700;
}

.block-description {
    margin-top: 15px;
    font-size: 1.05em;
    line-height: 1.6;
    color: #ccc;
}

.goals-overview {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 30px;
    margin-top: 20px;
}

.goal-progress-card {
    text-align: center;
}

.progress-circle {
    background: rgba(255, 215, 0, 0.1);
    border: 4px solid rgba(255, 215, 0, 0.3);
    border-radius: 50%;
    width: 180px;
    height: 180px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin: 20px auto;
}

.progress-value {
    font-size: 3em;
    font-weight: bold;
}

.progress-label {
    font-size: 0.9em;
    color: #888;
    margin-top: 5px;
}

.goal-targets {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.target-item {
    display: flex;
    gap: 12px;
    padding: 15px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 8px;
    border-left: 3px solid;
}

.target-item.met {
    border-left-color: #43b581;
}

.target-item.unmet {
    border-left-color: #f04747;
}

.target-icon {
    font-size: 1.5em;
}

.target-item strong {
    display: block;
    color: #fff;
    margin-bottom: 5px;
}

.target-item p {
    margin: 0;
    color: #bbb;
    font-size: 0.9em;
}

.insights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.insight-card {
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid;
}

.insight-success {
    background: rgba(67, 181, 129, 0.1);
    border-left-color: #43b581;
}

.insight-warning {
    background: rgba(250, 166, 26, 0.1);
    border-left-color: #faa61a;
}

.insight-danger {
    background: rgba(244, 67, 54, 0.1);
    border-left-color: #f04747;
}

.insight-info {
    background: rgba(88, 101, 242, 0.1);
    border-left-color: #5865F2;
}

.insight-header {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 8px;
}

.insight-icon {
    font-size: 1.3em;
}

.insight-card p {
    margin: 0;
    color: #bbb;
    font-size: 0.95em;
}

.teams-table-wrapper {
    overflow-x: auto;
    margin-top: 15px;
}

.standings-table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255, 255, 255, 0.03);
}

.standings-table th {
    background: rgba(255, 215, 0, 0.2);
    padding: 12px 8px;
    text-align: center;
    font-weight: bold;
    border-bottom: 2px solid #FFD700;
}

.standings-table td {
    padding: 10px 8px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.team-cell {
    text-align: left !important;
}

.pos-cell {
    font-weight: bold;
    color: #FFD700;
}

.pts-cell {
    background: rgba(255, 215, 0, 0.1);
    font-size: 1.1em;
}

.gd-cell {
    font-family: monospace;
}

.block-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-card {
    background: rgba(255, 215, 0, 0.1);
    padding: 25px;
    border-radius: 8px;
    text-align: center;
    border: 2px solid rgba(255, 215, 0, 0.3);
}

.stat-value {
    font-size: 2.5em;
    font-weight: bold;
    color: #FFD700;
    margin-bottom: 10px;
}

.stat-label {
    font-size: 0.9em;
    color: #aaa;
}

.xg-table-wrapper {
    overflow-x: auto;
    margin-top: 15px;
}

.xg-table {
    width: 100%;
    border-collapse: collapse;
}

.xg-table th {
    background: #23272a;
    padding: 10px 8px;
    text-align: center;
    font-size: 0.9em;
}

.xg-table td {
    padding: 10px 8px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.xg-table .positive {
    color: #43b581;
    font-weight: bold;
}

.xg-table .negative {
    color: #f04747;
    font-weight: bold;
}

.characteristics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.char-card {
    background: rgba(255, 215, 0, 0.05);
    padding: 20px;
    border-radius: 8px;
    border-left: 3px solid #FFD700;
}

.char-card h4 {
    margin-top: 0;
    color: #FFD700;
}

.char-card ul {
    list-style: none;
    padding: 0;
    margin: 10px 0 0 0;
}

.char-card ul li {
    padding: 6px 0;
    color: #bbb;
}

.char-card ul li:before {
    content: "✓ ";
    color: #FFD700;
    font-weight: bold;
}

.block-navigation {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 20px;
}

.nav-btn {
    padding: 12px 24px;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    text-decoration: none;
    color: #fff;
    font-weight: bold;
    transition: all 0.3s;
}

.nav-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

.block-2-nav {
    border-color: #4CAF50;
    color: #4CAF50;
}

.note {
    color: #888;
    font-style: italic;
    margin-top: 15px;
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .goals-overview {
        grid-template-columns: 1fr;
    }
    
    .progress-circle {
        width: 150px;
        height: 150px;
    }
    
    .progress-value {
        font-size: 2.5em;
    }
}
</style>