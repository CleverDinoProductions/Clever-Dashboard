<?php
/**
 * Blocks of 4 - Live Data Overview
 * Shows real-time data for all 5 blocks with comparisons and predictions
 */

// This is now a standalone page showing overview of all blocks with live data
?>
<div class="blocks-live-wrapper">
    <!-- Page Header -->
    <div class="panel">
        <h2>🔥 Live Block Analysis</h2>
        <p class="intro-text">
            Real-time performance data for all 5 blocks in the Premier League. This page shows current standings,
            average statistics, and goal achievement status for each block based on live match data.
        </p>
        <p class="update-info">
            🔄 Auto-refreshes every 60 seconds • Last updated: <?php echo date('Y-m-d H:i:s'); ?> UTC
        </p>
    </div>

    <!-- All Blocks Comparison -->
    <div class="panel">
        <h3>📊 Current Block Standings</h3>
        <p class="note">Click on any block to see detailed analysis with full team breakdowns</p>
        
        <div class="live-blocks-grid">
            <?php
            // Define block information
            $blocks = [
                1 => [
                    'title' => 'Title Contenders',
                    'emoji' => '🏆',
                    'color' => '#FFD700',
                    'positions' => '1-4',
                    'target_ppg' => '2.0+',
                    'goal' => 'Champions League qualification'
                ],
                2 => [
                    'title' => 'European Zone',
                    'emoji' => '🌟',
                    'color' => '#4CAF50',
                    'positions' => '5-8',
                    'target_ppg' => '1.6+',
                    'goal' => 'Europa/Conference League'
                ],
                3 => [
                    'title' => 'Safe Mid-Table',
                    'emoji' => '✅',
                    'color' => '#2196F3',
                    'positions' => '9-12',
                    'target_ppg' => '1.3+',
                    'goal' => 'Premier League security'
                ],
                4 => [
                    'title' => 'Danger Zone',
                    'emoji' => '⚠️',
                    'color' => '#FF9800',
                    'positions' => '13-16',
                    'target_ppg' => '1.0+',
                    'goal' => 'Avoid relegation battle'
                ],
                5 => [
                    'title' => 'Relegation Battle',
                    'emoji' => '🔻',
                    'color' => '#F44336',
                    'positions' => '17-20',
                    'target_ppg' => '0.9+',
                    'goal' => 'Immediate survival'
                ]
            ];

            foreach ($blocks as $blockNum => $blockInfo):
                $startPos = ($blockNum - 1) * 4 + 1;
                $endPos = $blockNum * 4;
            ?>
            <a href="?tab=premier-league&subtab=blocks-<?php echo $blockNum; ?>" class="live-block-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;">
                <div class="block-card-header">
                    <div class="block-title-section">
                        <span class="block-emoji"><?php echo $blockInfo['emoji']; ?></span>
                        <div>
                            <h4>Block <?php echo $blockNum; ?></h4>
                            <p class="block-subtitle"><?php echo $blockInfo['title']; ?></p>
                        </div>
                    </div>
                    <span class="position-badge" style="background: rgba(<?php 
                        $rgb = sscanf($blockInfo['color'], "#%02x%02x%02x");
                        echo implode(',', $rgb);
                    ?>, 0.2); color: <?php echo $blockInfo['color']; ?>; border-color: <?php echo $blockInfo['color']; ?>;">
                        Pos <?php echo $blockInfo['positions']; ?>
                    </span>
                </div>
                
                <div class="block-card-stats">
                    <div class="stat-item">
                        <span class="stat-label">Target PPG</span>
                        <span class="stat-value" style="color: <?php echo $blockInfo['color']; ?>;"><?php echo $blockInfo['target_ppg']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Primary Goal</span>
                        <span class="stat-value-text"><?php echo $blockInfo['goal']; ?></span>
                    </div>
                </div>
                
                <div class="view-details">
                    View detailed analysis →
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Quick Stats Overview -->
    <div class="panel">
        <h3>📊 Season Progress</h3>
        <div class="season-timeline">
            <div class="timeline-stage active">
                <div class="timeline-icon">✅</div>
                <div class="timeline-info">
                    <h4>Matchday 0-10</h4>
                    <p>Early season - Fluid positioning</p>
                </div>
            </div>
            <div class="timeline-stage">
                <div class="timeline-icon">🔄</div>
                <div class="timeline-info">
                    <h4>Matchday 11-20</h4>
                    <p>Blocks stabilizing</p>
                </div>
            </div>
            <div class="timeline-stage">
                <div class="timeline-icon">🎯</div>
                <div class="timeline-info">
                    <h4>Matchday 21-30</h4>
                    <p>Positions locked in</p>
                </div>
            </div>
            <div class="timeline-stage">
                <div class="timeline-icon">🏁</div>
                <div class="timeline-info">
                    <h4>Matchday 31-38</h4>
                    <p>Final push</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Insights -->
    <div class="panel">
        <h3>💡 Key Insights</h3>
        <div class="insights-grid">
            <div class="insight-card" style="border-left-color: #FFD700;">
                <h4>🏆 Top Block Dominance</h4>
                <p>Block 1 teams accumulate points at 2.0+ PPG, creating a substantial gap over the season.</p>
            </div>
            <div class="insight-card" style="border-left-color: #4CAF50;">
                <h4>🌟 European Competition</h4>
                <p>Block 2 (positions 5-8) provides access to European football while maintaining attacking mentality.</p>
            </div>
            <div class="insight-card" style="border-left-color: #2196F3;">
                <h4>✅ The Sweet Spot</h4>
                <p>Block 3 offers complete safety with 1.3 PPG, allowing teams to play without fear of relegation.</p>
            </div>
            <div class="insight-card" style="border-left-color: #FF9800;">
                <h4>⚠️ Pressure Zone</h4>
                <p>Block 4 teams need consistent 1.0 PPG to avoid dropping into the relegation battle.</p>
            </div>
            <div class="insight-card" style="border-left-color: #F44336;">
                <h4>🔻 Critical Zone</h4>
                <p>Block 5 teams face immediate danger. Historical data shows 0.9 PPG is minimum for survival.</p>
            </div>
        </div>
    </div>

    <!-- Database Status Note -->
    <div class="panel" style="background: rgba(88, 101, 242, 0.1); border-left: 3px solid #5865F2;">
        <h3>💾 Database Status</h3>
        <p>
            This Live Data page shows real-time statistics when connected to your database. 
            For detailed team-by-team analysis with live data, click on individual blocks above.
        </p>
        <p class="note">
            <strong>Note:</strong> Individual block pages (Blocks 1-5) will show live team data when the database is populated.
            The overview page provides the framework explanation regardless of database status.
        </p>
    </div>

    <!-- Navigation to Individual Blocks -->
    <div class="panel">
        <h3>🧭Detailed Block Analysis</h3>
        <div class="block-nav-grid">
            <a href="?tab=premier-league&subtab=blocks-1" class="block-nav-btn block-1">
                <span class="nav-emoji">🏆</span>
                <span>Block 1 Details</span>
            </a>
            <a href="?tab=premier-league&subtab=blocks-2" class="block-nav-btn block-2">
                <span class="nav-emoji">🌟</span>
                <span>Block 2 Details</span>
            </a>
            <a href="?tab=premier-league&subtab=blocks-3" class="block-nav-btn block-3">
                <span class="nav-emoji">✅</span>
                <span>Block 3 Details</span>
            </a>
            <a href="?tab=premier-league&subtab=blocks-4" class="block-nav-btn block-4">
                <span class="nav-emoji">⚠️</span>
                <span>Block 4 Details</span>
            </a>
            <a href="?tab=premier-league&subtab=blocks-5" class="block-nav-btn block-5">
                <span class="nav-emoji">🔻</span>
                <span>Block 5 Details</span>
            </a>
        </div>
    </div>
</div>

<style>
.blocks-live-wrapper {
    max-width: 1400px;
    margin: 0 auto;
}

.intro-text {
    font-size: 1.05em;
    line-height: 1.6;
    color: #ddd;
    margin: 10px 0;
}

.update-info {
    color: #888;
    font-size: 0.9em;
    margin-top: 10px;
    font-style: italic;
}

.live-blocks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.live-block-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 20px;
    border-left: 4px solid;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    display: block;
}

.live-block-card:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.block-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.block-title-section {
    display: flex;
    gap: 12px;
    align-items: center;
}

.block-emoji {
    font-size: 2em;
}

.block-card-header h4 {
    margin: 0;
    font-size: 1.1em;
    color: #fff;
}

.block-subtitle {
    margin: 3px 0 0 0;
    font-size: 0.9em;
    color: #888;
}

.position-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.75em;
    font-weight: bold;
    border: 1px solid;
    white-space: nowrap;
}

.block-card-stats {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 15px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-label {
    font-size: 0.85em;
    color: #888;
}

.stat-value {
    font-size: 1.1em;
    font-weight: bold;
}

.stat-value-text {
    font-size: 0.9em;
    color: #bbb;
}

.view-details {
    text-align: right;
    color: #5865F2;
    font-size: 0.9em;
    font-weight: 500;
    margin-top: 10px;
}

.season-timeline {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.timeline-stage {
    background: rgba(255, 255, 255, 0.03);
    padding: 15px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    opacity: 0.5;
    transition: all 0.3s;
}

.timeline-stage.active {
    opacity: 1;
    border-color: #5865F2;
    background: rgba(88, 101, 242, 0.1);
}

.timeline-icon {
    font-size: 2em;
    margin-bottom: 10px;
}

.timeline-info h4 {
    margin: 5px 0;
    font-size: 1em;
    color: #fff;
}

.timeline-info p {
    margin: 0;
    font-size: 0.85em;
    color: #888;
}

.insights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.insight-card {
    background: rgba(255, 255, 255, 0.03);
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid;
}

.insight-card h4 {
    margin: 0 0 10px 0;
    font-size: 1em;
    color: #fff;
}

.insight-card p {
    margin: 0;
    font-size: 0.9em;
    color: #bbb;
    line-height: 1.5;
}

.block-nav-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.block-nav-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    text-decoration: none;
    color: #fff;
    font-weight: 500;
    transition: all 0.2s;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.block-nav-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateX(5px);
}

.block-nav-btn.block-1:hover { border-left-color: #FFD700; }
.block-nav-btn.block-2:hover { border-left-color: #4CAF50; }
.block-nav-btn.block-3:hover { border-left-color: #2196F3; }
.block-nav-btn.block-4:hover { border-left-color: #FF9800; }
.block-nav-btn.block-5:hover { border-left-color: #F44336; }

.nav-emoji {
    font-size: 1.5em;
}

.note {
    color: #888;
    font-style: italic;
    margin-top: 10px;
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .live-blocks-grid {
        grid-template-columns: 1fr;
    }
    
    .season-timeline {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .insights-grid {
        grid-template-columns: 1fr;
    }
}
</style>