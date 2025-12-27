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
    </div>

    <!-- Block Characteristics -->
    <div class="panel">
        <h3>🎯 Block 1 Characteristics</h3>
        <div class="characteristics-grid">
            <div class="char-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;">
                <h4>📊 Target Performance</h4>
                <ul>
                    <li><strong>Points Per Game:</strong> 2.0+ PPG</li>
                    <li><strong>Projected Points:</strong> 75-95 points</li>
                    <li><strong>Win Rate:</strong> 60%+ required</li>
                    <li><strong>Goal Difference:</strong> +40 to +70</li>
                </ul>
            </div>
            
            <div class="char-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;">
                <h4>🧠 Team Mentality</h4>
                <ul>
                    <li>Attacking, possession-based football</li>
                    <li>Expectation to win every home game</li>
                    <li>Squad depth for multiple competitions</li>
                    <li>Title-winning mentality required</li>
                </ul>
            </div>
            
            <div class="char-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;">
                <h4>⚽ Tactical Approach</h4>
                <ul>
                    <li>High press and ball recovery</li>
                    <li>Creative attacking players</li>
                    <li>Strong defensive organization</li>
                    <li>Set-piece effectiveness</li>
                </ul>
            </div>
            
            <div class="char-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;">
                <h4>💰 Financial Reality</h4>
                <ul>
                    <li>Highest wage bills in the league</li>
                    <li>International superstar players</li>
                    <li>Champions League revenue crucial</li>
                    <li>Global commercial appeal</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Current Teams (Placeholder) -->
    <div class="panel">
        <h3>👥 Typical Block 1 Teams</h3>
        <p class="note">These are the teams historically competing in Block 1 positions:</p>
        <div class="teams-showcase">
            <div class="team-showcase-card">
                <h4>🔴 Manchester City</h4>
                <p>Dominant force with sustained excellence. Multiple title wins in recent years.</p>
            </div>
            <div class="team-showcase-card">
                <h4>🔴 Arsenal</h4>
                <p>Consistent top-4 performers pushing for title challenge.</p>
            </div>
            <div class="team-showcase-card">
                <h4>🔴 Liverpool</h4>
                <p>High-intensity football with European pedigree.</p>
            </div>
            <div class="team-showcase-card">
                <h4>⚪ Chelsea</h4>
                <p>Investment-backed squad aiming for Champions League return.</p>
            </div>
        </div>
    </div>

    <!-- Historical Context -->
    <div class="panel">
        <h3>📜 Historical Block 1 Analysis</h3>
        <div class="history-section">
            <h4>Points Required for Block 1 (2000-2024)</h4>
            <ul>
                <li><strong>1st Place:</strong> Typically 85-95 points (2.2-2.5 PPG)</li>
                <li><strong>2nd Place:</strong> Typically 75-85 points (2.0-2.2 PPG)</li>
                <li><strong>3rd Place:</strong> Typically 70-80 points (1.8-2.1 PPG)</li>
                <li><strong>4th Place:</strong> Typically 65-75 points (1.7-2.0 PPG)</li>
            </ul>
            
            <div class="insight-box" style="background: rgba(255, 215, 0, 0.1); border-left: 3px solid <?php echo $blockInfo['color']; ?>; margin-top: 20px; padding: 15px; border-radius: 5px;">
                <h4>💡 Key Insight</h4>
                <p>
                    The gap between 4th place (last Champions League spot) and 5th place averages <strong>8-12 points</strong>
                    per season. This represents the massive financial and prestige difference between Block 1 and Block 2.
                </p>
            </div>
        </div>
    </div>

    <!-- Movement Analysis -->
    <div class="panel">
        <h3>🔄 Block Movement Patterns</h3>
        <div class="movement-analysis">
            <div class="movement-section">
                <h4>⬆️ Entering Block 1</h4>
                <p>Teams breaking into Block 1 typically need:</p>
                <ul>
                    <li>Significant investment in playing squad</li>
                    <li>Elite-level manager appointment</li>
                    <li>3-5 years of sustained progress</li>
                    <li>European qualification as stepping stone</li>
                </ul>
                <p class="example"><strong>Recent Examples:</strong> Newcastle (2023-24), Aston Villa (2023-24)</p>
            </div>
            
            <div class="movement-section">
                <h4>⬇️ Leaving Block 1</h4>
                <p>Teams dropping from Block 1 usually experience:</p>
                <ul>
                    <li>Managerial instability</li>
                    <li>Key player departures</li>
                    <li>Failed recruitment strategy</li>
                    <li>Financial fair play restrictions</li>
                </ul>
                <p class="example"><strong>Recent Examples:</strong> Manchester United (periods), Tottenham (fluctuating)</p>
            </div>
        </div>
    </div>

    <!-- Strategic Importance -->
    <div class="panel" style="background: rgba(255, 215, 0, 0.05);">
        <h3>🎯 Why Block 1 Matters</h3>
        <div class="importance-grid">
            <div class="importance-item">
                <span class="importance-icon">💰</span>
                <h4>Financial Impact</h4>
                <p>Champions League participation worth £50-100m+ per season</p>
            </div>
            <div class="importance-item">
                <span class="importance-icon">⭐</span>
                <h4>Player Recruitment</h4>
                <p>Ability to attract world-class talent</p>
            </div>
            <div class="importance-item">
                <span class="importance-icon">🌍</span>
                <h4>Global Brand</h4>
                <p>International exposure and commercial growth</p>
            </div>
            <div class="importance-item">
                <span class="importance-icon">🏆</span>
                <h4>Prestige</h4>
                <p>Competing against Europe's elite clubs</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="panel">
        <div class="block-navigation">
            <a href="?tab=premier-league&subtab=blocks-overview" class="nav-btn">
                ← Overview
            </a>
            <a href="?tab=premier-league&subtab=blocks-dynamic" class="nav-btn" style="background: rgba(255, 215, 0, 0.1); color: <?php echo $blockInfo['color']; ?>; border-color: <?php echo $blockInfo['color']; ?>;">
                🔥 Live Data
            </a>
            <a href="?tab=premier-league&subtab=blocks-2" class="nav-btn">
                Block 2 →
            </a>
        </div>
    </div>
</div>

<style>
.block-detail-page {
    max-width: 1400px;
    margin: 0 auto;
}

.block-header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.block-header-section h2 {
    margin: 0;
    font-size: 2em;
}

.position-badge {
    padding: 8px 15px;
    border-radius: 6px;
    font-size: 0.9em;
    font-weight: bold;
}

.characteristics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.char-card {
    background: rgba(255, 255, 255, 0.03);
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid;
}

.char-card h4 {
    margin: 0 0 15px 0;
    color: #fff;
    font-size: 1.1em;
}

.char-card ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.char-card ul li {
    padding: 8px 0;
    color: #bbb;
    font-size: 0.95em;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.char-card ul li:last-child {
    border-bottom: none;
}

.teams-showcase {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.team-showcase-card {
    background: rgba(255, 255, 255, 0.03);
    padding: 15px;
    border-radius: 8px;
    border: 1px solid rgba(255, 215, 0, 0.3);
}

.team-showcase-card h4 {
    margin: 0 0 10px 0;
    color: #FFD700;
}

.team-showcase-card p {
    margin: 0;
    color: #bbb;
    font-size: 0.9em;
}

.history-section ul {
    padding-left: 20px;
    margin: 15px 0;
}

.history-section ul li {
    padding: 8px 0;
    color: #ddd;
}

.insight-box h4 {
    margin: 0 0 10px 0;
    color: #FFD700;
}

.insight-box p {
    margin: 0;
    color: #ddd;
    line-height: 1.6;
}

.movement-analysis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.movement-section {
    background: rgba(255, 255, 255, 0.03);
    padding: 20px;
    border-radius: 8px;
}

.movement-section h4 {
    margin: 0 0 15px 0;
    color: #FFD700;
}

.movement-section ul {
    padding-left: 20px;
    margin: 10px 0;
}

.movement-section ul li {
    padding: 5px 0;
    color: #bbb;
}

.example {
    margin-top: 15px;
    padding: 10px;
    background: rgba(88, 101, 242, 0.1);
    border-radius: 5px;
    color: #aaa;
    font-size: 0.9em;
}

.importance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.importance-item {
    text-align: center;
    padding: 20px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 8px;
}

.importance-icon {
    font-size: 3em;
    display: block;
    margin-bottom: 10px;
}

.importance-item h4 {
    margin: 10px 0;
    color: #FFD700;
}

.importance-item p {
    margin: 0;
    color: #bbb;
    font-size: 0.9em;
}

.note {
    color: #888;
    font-style: italic;
    margin-bottom: 15px;
}

.block-navigation {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
}

.nav-btn {
    flex: 1;
    min-width: 150px;
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
    text-decoration: none;
    text-align: center;
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.2s;
    font-weight: 500;
}

.nav-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .block-header-section {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .characteristics-grid,
    .teams-showcase,
    .movement-analysis,
    .importance-grid {
        grid-template-columns: 1fr;
    }
    
    .block-navigation {
        flex-direction: column;
    }
    
    .nav-btn {
        width: 100%;
    }
}
</style>