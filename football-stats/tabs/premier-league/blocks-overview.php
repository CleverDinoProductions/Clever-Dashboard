<?php
// Blocks of 4 Overview - Dynamic Real-Time Data
require_once __DIR__ . '/../../includes/blocks-functions.php';

// Get real-time data
$blockComparison = getBlockComparison($db);
$movementPredictions = getMovementPredictions($db);
$timeline = getBlockTimeline($db);
?>
<div class="blocks-wrapper">
    <!-- Season Progress Header -->
    <div class="panel">
        <div class="season-progress-header">
            <div class="progress-info">
                <h2>📊 Blocks of 4 Framework - Live Analysis</h2>
                <p class="season-status">
                    <strong>Matchday <?php echo $timeline['current_matchday']; ?></strong> • 
                    <span style="color: <?php echo $timeline['stabilization_level']['color']; ?>">
                        <?php echo $timeline['stabilization_level']['text']; ?>
                    </span>
                </p>
            </div>
        </div>
        <p class="intro-text">
            The Premier League table divided into 5 equal blocks of 4 teams, providing clear insights into 
            competitive zones, psychological boundaries, and tactical positioning throughout the season.
        </p>
    </div>

    <!-- Live Block Comparison -->
    <div class="panel">
        <h3>🔥 Live Block Performance</h3>
        <div class="blocks-comparison-grid">
            <?php foreach ($blockComparison as $blockNum => $data): 
                $blockInfo = [
                    1 => ['name' => 'Title Contenders', 'icon' => '🏆', 'color' => '#FFD700'],
                    2 => ['name' => 'European Zone', 'icon' => '🌟', 'color' => '#4CAF50'],
                    3 => ['name' => 'Safe Mid-Table', 'icon' => '✅', 'color' => '#2196F3'],
                    4 => ['name' => 'Danger Zone', 'icon' => '⚠️', 'color' => '#FF9800'],
                    5 => ['name' => 'Relegation Battle', 'icon' => '🔻', 'color' => '#F44336']
                ];
                $info = $blockInfo[$blockNum];
            ?>
            <div class="block-comparison-card" style="border-left-color: <?php echo $info['color']; ?>">
                <div class="block-comp-header">
                    <span class="block-icon"><?php echo $info['icon']; ?></span>
                    <h4>Block <?php echo $blockNum; ?></h4>
                </div>
                <p class="block-comp-name"><?php echo $info['name']; ?></p>
                <div class="block-comp-stats">
                    <div class="stat-row">
                        <span class="stat-label">Avg PPG:</span>
                        <span class="stat-value"><?php echo $data['avg_ppg']; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Avg Points:</span>
                        <span class="stat-value"><?php echo $data['avg_points']; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Point Spread:</span>
                        <span class="stat-value"><?php echo $data['point_spread']; ?></span>
                    </div>
                </div>
                <div class="goals-status" style="background: rgba(<?php 
                    echo $data['goals_met'] >= 75 ? '76, 175, 80' : ($data['goals_met'] >= 50 ? '255, 152, 0' : '244, 67, 54');
                ?>, 0.15);">
                    <strong><?php echo $data['goals_met']; ?>%</strong> Goals Met
                    <p class="status-text"><?php echo $data['status']; ?></p>
                </div>
                <a href="?tab=premier-league&subtab=blocks-<?php echo $blockNum; ?>" class="view-block-btn">
                    View Details →
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Movement Predictions -->
    <?php if (!empty($movementPredictions)): ?>
    <div class="panel">
        <h3>🔄 Predicted Block Movement</h3>
        <p class="note">Based on current points-per-game, these teams are projected to move blocks:</p>
        <div class="movement-predictions-grid">
            <?php foreach ($movementPredictions as $pred): ?>
            <div class="movement-card <?php echo $pred['direction']; ?>-movement">
                <div class="movement-header">
                    <strong><?php echo $pred['team']; ?></strong>
                    <span class="movement-arrow">
                        <?php echo $pred['direction'] == 'up' ? '⬆️' : '⬇️'; ?>
                    </span>
                </div>
                <div class="movement-details">
                    <p>
                        Block <?php echo $pred['current_block']; ?> 
                        <span class="arrow">→</span> 
                        Block <?php echo $pred['projected_block']; ?>
                    </p>
                    <p class="movement-stats">
                        <?php echo $pred['ppg']; ?> PPG • Projected: <?php echo $pred['projected_points']; ?> pts
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Season Timeline -->
    <div class="panel">
        <h3>🗓️ Block Stabilization Timeline</h3>
        <p class="note">Blocks typically stabilize as the season progresses. Historical patterns show:</p>
        <div class="timeline-container">
            <?php foreach ($timeline['milestones'] as $milestone): ?>
            <div class="timeline-item <?php echo $milestone['status'] ? 'completed' : 'pending'; ?>">
                <div class="timeline-marker">
                    <?php echo $milestone['status'] ? '✅' : '⏳'; ?>
                </div>
                <div class="timeline-content">
                    <strong>Matchday <?php echo $milestone['matchday']; ?></strong>
                    <p><?php echo $milestone['description']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Framework Explanation -->
    <div class="panel">
        <h3>🎯 How the Framework Works</h3>
        <div class="blocks-grid">
            <div class="block-card block-1">
                <div class="block-header">
                    <span class="block-number">Block 1</span>
                    <span class="block-positions">Positions 1-4</span>
                </div>
                <h4>🏆 Title Contenders</h4>
                <ul>
                    <li>Champions League qualification</li>
                    <li>Title race participants</li>
                    <li>Attacking mentality</li>
                    <li>Target: 2.0+ PPG</li>
                </ul>
            </div>

            <div class="block-card block-2">
                <div class="block-header">
                    <span class="block-number">Block 2</span>
                    <span class="block-positions">Positions 5-8</span>
                </div>
                <h4>🌟 European Zone</h4>
                <ul>
                    <li>Europa League / Conference</li>
                    <li>Tactical flexibility</li>
                    <li>Competitive mid-table</li>
                    <li>Target: 1.6+ PPG</li>
                </ul>
            </div>

            <div class="block-card block-3">
                <div class="block-header">
                    <span class="block-number">Block 3</span>
                    <span class="block-positions">Positions 9-12</span>
                </div>
                <h4>✅ Safe Mid-Table</h4>
                <ul>
                    <li>Premier League security</li>
                    <li>No relegation concerns</li>
                    <li>Tactical stability</li>
                    <li>Target: 1.3+ PPG</li>
                </ul>
            </div>

            <div class="block-card block-4">
                <div class="block-header">
                    <span class="block-number">Block 4</span>
                    <span class="block-positions">Positions 13-16</span>
                </div>
                <h4>⚠️ Danger Zone</h4>
                <ul>
                    <li>Pressure building</li>
                    <li>Tactical changes needed</li>
                    <li>Relegation awareness</li>
                    <li>Target: 1.0+ PPG</li>
                </ul>
            </div>

            <div class="block-card block-5">
                <div class="block-header">
                    <span class="block-number">Block 5</span>
                    <span class="block-positions">Positions 17-20</span>
                </div>
                <h4>🔻 Relegation Battle</h4>
                <ul>
                    <li>Immediate danger</li>
                    <li>Crisis mode mentality</li>
                    <li>Desperate measures</li>
                    <li>Target: 0.9+ PPG minimum</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Mathematical Reality -->
    <div class="panel">
        <h3>🔢 The Mathematical Reality</h3>
        <div class="math-explanation">
            <p><strong>Why bottom blocks struggle to catch up:</strong></p>
            <ul>
                <li>Top teams accumulate 2-3 points per week consistently</li>
                <li>Bottom teams average 0-1 points per week</li>
                <li>The gap widens exponentially as the season progresses</li>
                <li>After Matchday 10, teams need 1.0-1.2 PPG to survive - historically difficult for struggling sides</li>
            </ul>
            
            <div class="survival-stats">
                <h4>Historical Survival Rates (After 10 Games):</h4>
                <ul>
                    <li>✅ <strong>11+ points:</strong> 89% survival rate</li>
                    <li>⚠️ <strong>8-10 points:</strong> 53% survival rate</li>
                    <li>🔻 <strong>0-7 points:</strong> Only 12% survival rate</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.blocks-wrapper {
    max-width: 1400px;
    margin: 0 auto;
}

.season-progress-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.season-status {
    margin-top: 10px;
    font-size: 1.1em;
    color: #bbb;
}

.intro-text {
    font-size: 1.05em;
    line-height: 1.6;
    color: #ddd;
}

.blocks-comparison-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.block-comparison-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 20px;
    border-left: 4px solid;
    transition: transform 0.2s;
}

.block-comparison-card:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.08);
}

.block-comp-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 5px;
}

.block-icon {
    font-size: 1.5em;
}

.block-comp-name {
    font-size: 0.9em;
    color: #888;
    margin: 5px 0 15px 0;
}

.block-comp-stats {
    margin: 15px 0;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-label {
    color: #999;
    font-size: 0.9em;
}

.stat-value {
    color: #fff;
    font-weight: bold;
}

.goals-status {
    padding: 10px;
    border-radius: 5px;
    text-align: center;
    margin: 15px 0;
}

.goals-status strong {
    font-size: 1.3em;
    display: block;
    margin-bottom: 5px;
}

.status-text {
    font-size: 0.85em;
    margin: 0;
    color: #bbb;
}

.view-block-btn {
    display: block;
    text-align: center;
    padding: 8px;
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    transition: all 0.2s;
    font-size: 0.9em;
}

.view-block-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.movement-predictions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.movement-card {
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid;
}

.up-movement {
    background: rgba(76, 175, 80, 0.1);
    border-left-color: #4CAF50;
}

.down-movement {
    background: rgba(244, 67, 54, 0.1);
    border-left-color: #F44336;
}

.movement-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.movement-arrow {
    font-size: 1.5em;
}

.movement-details p {
    margin: 5px 0;
    color: #bbb;
}

.movement-stats {
    font-size: 0.9em;
    color: #888;
}

.timeline-container {
    margin-top: 20px;
}

.timeline-item {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 8px;
}

.timeline-item.completed {
    border-left: 3px solid #43b581;
}

.timeline-item.pending {
    border-left: 3px solid #72767d;
    opacity: 0.6;
}

.timeline-marker {
    font-size: 1.5em;
}

.timeline-content strong {
    color: #5865F2;
}

.timeline-content p {
    margin: 5px 0 0 0;
    color: #bbb;
    font-size: 0.95em;
}

.blocks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.block-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 20px;
    border-left: 4px solid;
}

.block-card.block-1 { border-left-color: #FFD700; }
.block-card.block-2 { border-left-color: #4CAF50; }
.block-card.block-3 { border-left-color: #2196F3; }
.block-card.block-4 { border-left-color: #FF9800; }
.block-card.block-5 { border-left-color: #F44336; }

.block-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.block-number {
    font-weight: bold;
    font-size: 0.9em;
}

.block-positions {
    font-size: 0.85em;
    color: #888;
}

.block-card h4 {
    margin: 10px 0;
    font-size: 1.2em;
}

.block-card ul {
    list-style: none;
    padding: 0;
    margin: 10px 0 0 0;
}

.block-card ul li {
    padding: 5px 0;
    font-size: 0.95em;
    color: #bbb;
}

.block-card ul li:before {
    content: "→ ";
    color: #666;
}

.math-explanation {
    background: rgba(255, 255, 255, 0.03);
    padding: 20px;
    border-radius: 8px;
    margin-top: 15px;
}

.math-explanation ul {
    margin: 10px 0;
    padding-left: 20px;
}

.survival-stats {
    background: rgba(255, 152, 0, 0.1);
    padding: 15px;
    border-radius: 5px;
    margin-top: 15px;
    border-left: 3px solid #FF9800;
}

.survival-stats h4 {
    margin-top: 0;
}

.survival-stats ul {
    list-style: none;
    padding: 0;
}

.survival-stats ul li {
    padding: 8px 0;
    font-size: 1.05em;
}

.note {
    color: #888;
    font-style: italic;
    margin-bottom: 15px;
}

.arrow {
    color: #5865F2;
    font-weight: bold;
}
</style>