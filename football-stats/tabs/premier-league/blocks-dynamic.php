<?php
/**
 * Dynamic Block Analysis Template
 * Works for all 5 blocks with real-time data and goal tracking
 * 
 * Usage: Set $blockNumber before including this file
 * Example: $blockNumber = 1; include 'blocks-dynamic.php';
 */

require_once __DIR__ . '/../../includes/blocks-functions.php';

// Block configuration
if (!isset($blockNumber)) {
    die('Block number not set');
}

$blockInfo = getBlockInfo($blockNumber);
$blockColor = getBlockColor($blockNumber);
$startPos = ($blockNumber - 1) * 4 + 1;
$endPos = $blockNumber * 4;

// Get database connection
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/../../football-stats.sqlite3');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $pdo = null;
}

// Fetch real-time data
$teams = $pdo ? getBlockTeams($pdo, $startPos, $endPos) : [];
$blockStats = getBlockStats($teams);
$matchday = $pdo ? getCurrentMatchday($pdo) : 1;
$goals = checkBlockGoals($blockNumber, $teams, $matchday);
$lastUpdated = $pdo ? getLastUpdated($pdo) : date('Y-m-d H:i:s');
?>

<div class="block-detail-wrapper" data-block="<?php echo $blockNumber; ?>">
    <!-- Block Header -->
    <div class="panel">
        <div class="block-title-header" style="border-left: 4px solid <?php echo $blockColor['primary']; ?>;">
            <h2 style="color: <?php echo $blockColor['primary']; ?>;">
                <?php echo $blockInfo['emoji']; ?> Block <?php echo $blockNumber; ?>: <?php echo $blockInfo['title']; ?>
            </h2>
            <span class="position-badge" style="background: <?php echo $blockColor['rgba']; ?>; color: <?php echo $blockColor['primary']; ?>; border-color: <?php echo $blockColor['primary']; ?>;">
                Positions <?php echo $blockInfo['positions']; ?>
            </span>
        </div>
        <p class="update-info" style="margin-top: 10px;">
            🔄 Last updated: <?php echo $lastUpdated; ?> | Matchday <?php echo $matchday; ?> of 38
        </p>
    </div>

    <!-- Real-Time Block Statistics -->
    <div class="panel">
        <h3>📊 Block <?php echo $blockNumber; ?> Statistics (Matchday <?php echo $matchday; ?>)</h3>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value"><?php echo $blockStats['avg_points']; ?></div>
                <div class="stat-label">Average Points</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $blockStats['avg_ppg']; ?></div>
                <div class="stat-label">Average PPG</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $blockStats['avg_goals']; ?></div>
                <div class="stat-label">Average Goals Scored</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $blockStats['total_wins']; ?></div>
                <div class="stat-label">Total Wins (Block)</div>
            </div>
        </div>
    </div>

    <!-- Current Teams in Block -->
    <div class="panel">
        <h3>🎯 Current Block <?php echo $blockNumber; ?> Teams</h3>
        <?php if (!empty($teams)): ?>
        <div class="teams-table-wrapper">
            <table class="standings-table">
                <thead>
                    <tr style="background: <?php echo $blockColor['rgba']; ?>; border-bottom: 2px solid <?php echo $blockColor['primary']; ?>;">
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
                        <td class="pos-cell" style="color: <?php echo $blockColor['primary']; ?>;"><?php echo $team['position']; ?></td>
                        <td class="team-cell"><strong><?php echo htmlspecialchars($team['team_name']); ?></strong></td>
                        <td><?php echo $team['played']; ?></td>
                        <td><?php echo $team['won']; ?></td>
                        <td><?php echo $team['drawn']; ?></td>
                        <td><?php echo $team['lost']; ?></td>
                        <td><?php echo $team['gf']; ?></td>
                        <td><?php echo $team['ga']; ?></td>
                        <td class="gd-cell"><?php echo ($team['gd'] > 0 ? '+' : '') . $team['gd']; ?></td>
                        <td class="pts-cell" style="background: <?php echo $blockColor['rgba']; ?>;"><strong><?php echo $team['points']; ?></strong></td>
                        <td><?php echo $team['ppg']; ?></td>
                        <td><?php echo $team['win_rate']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="no-data">
            <p>⚠️ No team data available. Please ensure the database is populated.</p>
            <p class="text-muted">Run the data fetch script to populate league table data.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Block Goals & Progress Tracking -->
    <div class="panel">
        <h3>🎯 Block <?php echo $blockNumber; ?> Goals & Progress</h3>
        <p class="block-description">Typical objectives for teams in this block and whether they're being achieved:</p>
        
        <?php if (!empty($goals)): ?>
        <div class="goals-container">
            <?php foreach ($goals as $goal): ?>
            <div class="goal-card" style="border-left: 4px solid <?php echo $blockColor['primary']; ?>;">
                <div class="goal-header">
                    <h4><?php echo htmlspecialchars($goal['goal']); ?></h4>
                    <?php echo getStatusBadge($goal['status']); ?>
                </div>
                <div class="goal-details">
                    <div class="goal-row">
                        <span class="goal-label">🎯 Target:</span>
                        <span class="goal-value"><strong><?php echo htmlspecialchars($goal['target']); ?></strong></span>
                    </div>
                    <div class="goal-row">
                        <span class="goal-label">📈 Current Pace:</span>
                        <span class="goal-value"><?php echo htmlspecialchars($goal['current_pace']); ?></span>
                    </div>
                    <?php if (isset($goal['leader'])): ?>
                    <div class="goal-row">
                        <span class="goal-label">🏆 Leader:</span>
                        <span class="goal-value"><?php echo htmlspecialchars($goal['leader']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($goal['team'])): ?>
                    <div class="goal-row">
                        <span class="goal-label">👥 Team:</span>
                        <span class="goal-value"><?php echo htmlspecialchars($goal['team']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($goal['buffer'])): ?>
                    <div class="goal-row">
                        <span class="goal-label">🛡️ Buffer:</span>
                        <span class="goal-value"><?php echo htmlspecialchars($goal['buffer']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($goal['urgency'])): ?>
                    <div class="goal-row urgency">
                        <span class="goal-label">🚨 Urgency:</span>
                        <span class="goal-value"><strong><?php echo htmlspecialchars($goal['urgency']); ?></strong></span>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($goal['context'])): ?>
                    <div class="goal-context">
                        <em><?php echo htmlspecialchars($goal['context']); ?></em>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted">No goal data available for analysis.</p>
        <?php endif; ?>
    </div>

    <!-- Team Performance Analysis -->
    <div class="panel">
        <h3>📉 Individual Team Analysis</h3>
        <?php if (!empty($teams)): ?>
        <div class="team-analysis-grid">
            <?php foreach ($teams as $team): 
                $projectedPoints = round($team['ppg'] * 38, 1);
                $isOverperforming = $team['ppg'] > $blockStats['avg_ppg'];
            ?>
            <div class="team-analysis-card" style="border-top: 3px solid <?php echo $blockColor['primary']; ?>;">
                <h4><?php echo htmlspecialchars($team['team_name']); ?></h4>
                <div class="team-mini-stats">
                    <div class="mini-stat">
                        <span class="mini-label">Position</span>
                        <span class="mini-value" style="color: <?php echo $blockColor['primary']; ?>;"><?php echo $team['position']; ?></span>
                    </div>
                    <div class="mini-stat">
                        <span class="mini-label">Points</span>
                        <span class="mini-value"><strong><?php echo $team['points']; ?></strong></span>
                    </div>
                    <div class="mini-stat">
                        <span class="mini-label">PPG</span>
                        <span class="mini-value"><?php echo $team['ppg']; ?></span>
                    </div>
                    <div class="mini-stat">
                        <span class="mini-label">Projected</span>
                        <span class="mini-value"><?php echo $projectedPoints; ?> pts</span>
                    </div>
                </div>
                <div class="team-insight">
                    <?php if ($isOverperforming): ?>
                    <span class="insight-badge good">↗ Outperforming block average</span>
                    <?php else: ?>
                    <span class="insight-badge warning">↘ Below block average</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted">No team data available for analysis.</p>
        <?php endif; ?>
    </div>

    <!-- Block Navigation -->
    <div class="panel">
        <div class="block-navigation">
            <?php if ($blockNumber > 1): ?>
            <a href="?tab=premier-league&subtab=blocks-<?php echo $blockNumber - 1; ?>" class="nav-btn">
                ← Block <?php echo $blockNumber - 1; ?>
            </a>
            <?php else: ?>
            <a href="?tab=premier-league&subtab=blocks-overview" class="nav-btn">← Overview</a>
            <?php endif; ?>
            
            <a href="?tab=premier-league&subtab=blocks-overview" class="nav-btn" style="background: <?php echo $blockColor['rgba']; ?>; border-color: <?php echo $blockColor['primary']; ?>; color: <?php echo $blockColor['primary']; ?>;">
                🎯 All Blocks
            </a>
            
            <?php if ($blockNumber < 5): ?>
            <a href="?tab=premier-league&subtab=blocks-<?php echo $blockNumber + 1; ?>" class="nav-btn">
                Block <?php echo $blockNumber + 1; ?> →
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-box {
    background: rgba(255, 255, 255, 0.03);
    padding: 25px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-value {
    font-size: 2.5em;
    font-weight: bold;
    color: #fff;
    margin-bottom: 10px;
}

.stat-label {
    color: #888;
    font-size: 0.9em;
}

.goals-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 20px;
}

.goal-card {
    background: rgba(255, 255, 255, 0.03);
    padding: 20px;
    border-radius: 8px;
}

.goal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    flex-wrap: wrap;
    gap: 10px;
}

.goal-header h4 {
    margin: 0;
    color: #fff;
    font-size: 1.1em;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: bold;
    white-space: nowrap;
}

.status-badge.success {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
    border: 1px solid #4CAF50;
}

.status-badge.warning {
    background: rgba(255, 152, 0, 0.2);
    color: #FF9800;
    border: 1px solid #FF9800;
}

.status-badge.danger {
    background: rgba(244, 67, 54, 0.2);
    color: #F44336;
    border: 1px solid #F44336;
}

.status-badge.critical {
    background: rgba(244, 67, 54, 0.3);
    color: #ff1744;
    border: 2px solid #ff1744;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.goal-details {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.goal-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.goal-row.urgency {
    background: rgba(244, 67, 54, 0.1);
    padding: 8px 10px;
    border-radius: 4px;
    border: 1px solid rgba(244, 67, 54, 0.3);
}

.goal-label {
    color: #aaa;
    font-size: 0.9em;
}

.goal-value {
    color: #fff;
    font-size: 0.95em;
}

.goal-context {
    margin-top: 10px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.02);
    border-radius: 4px;
    color: #888;
    font-size: 0.85em;
}

.team-analysis-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.team-analysis-card {
    background: rgba(255, 255, 255, 0.03);
    padding: 20px;
    border-radius: 8px;
}

.team-analysis-card h4 {
    margin: 0 0 15px 0;
    color: #fff;
}

.team-mini-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}

.mini-stat {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.mini-label {
    font-size: 0.75em;
    color: #888;
    text-transform: uppercase;
}

.mini-value {
    font-size: 1.2em;
    color: #fff;
}

.team-insight {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.insight-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 500;
}

.insight-badge.good {
    background: rgba(76, 175, 80, 0.15);
    color: #4CAF50;
}

.insight-badge.warning {
    background: rgba(255, 152, 0, 0.15);
    color: #FF9800;
}

.no-data {
    text-align: center;
    padding: 40px 20px;
    background: rgba(255, 152, 0, 0.05);
    border-radius: 8px;
    border: 1px dashed rgba(255, 152, 0, 0.3);
}

.no-data p {
    margin: 10px 0;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .team-analysis-grid {
        grid-template-columns: 1fr;
    }
    
    .goal-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>