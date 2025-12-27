<?php
/**
 * Block 2: European Zone (Positions 5-8)
 * Europa League / Conference League qualification
 */

$blockNumber = 2;
$blockInfo = [
    'title' => 'European Zone',
    'emoji' => '🌟',
    'color' => '#4CAF50',
    'positions' => '5-8',
    'description' => 'Europa League & Conference League Qualification'
];
?>

<div class="block-detail-page">
    <div class="panel" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
        <div class="block-header-section">
            <h2 style="color: <?php echo $blockInfo['color']; ?>;">
                <?php echo $blockInfo['emoji']; ?> Block <?php echo $blockNumber; ?>: <?php echo $blockInfo['title']; ?>
            </h2>
            <span class="position-badge" style="background: rgba(76, 175, 80, 0.2); color: <?php echo $blockInfo['color']; ?>; border: 1px solid <?php echo $blockInfo['color']; ?>;">
                Positions <?php echo $blockInfo['positions']; ?>
            </span>
        </div>
        <p style="margin-top: 15px; font-size: 1.1em; color: #ddd;">
            <?php echo $blockInfo['description']; ?> - Teams competing for European football while maintaining
            competitive Premier League status.
        </p>
    </div>

    <div class="panel">
        <h3>🎯 Block 2 Characteristics</h3>
        <div class="characteristics-grid">
            <div class="char-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;">
                <h4>📊 Target Performance</h4>
                <ul>
                    <li><strong>Points Per Game:</strong> 1.6-1.9 PPG</li>
                    <li><strong>Projected Points:</strong> 60-72 points</li>
                    <li><strong>Win Rate:</strong> 45-55% typical</li>
                    <li><strong>Goal Difference:</strong> +15 to +30</li>
                </ul>
            </div>
            
            <div class="char-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;">
                <h4>🧠 Team Mentality</h4>
                <ul>
                    <li>Balanced attacking approach</li>
                    <li>European ambitions</li>
                    <li>Strong home record essential</li>
                    <li>Tactical flexibility required</li>
                </ul>
            </div>
            
            <div class="char-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;">
                <h4>⚽ Tactical Approach</h4>
                <ul>
                    <li>Mix of possession and counter</li>
                    <li>Solid defensive foundation</li>
                    <li>Opportunistic attacking</li>
                    <li>Squad rotation for cup runs</li>
                </ul>
            </div>
            
            <div class="char-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;">
                <h4>💰 Financial Reality</h4>
                <ul>
                    <li>Europa revenue valuable</li>
                    <li>Mid-tier wage structure</li>
                    <li>Smart recruitment key</li>
                    <li>Player development focus</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="panel">
        <h3>👥 Typical Block 2 Teams</h3>
        <div class="teams-showcase">
            <div class="team-showcase-card">
                <h4>⚪ Tottenham Hotspur</h4>
                <p>Frequently compete for European spots with attacking football.</p>
            </div>
            <div class="team-showcase-card">
                <h4>⚫ Newcastle United</h4>
                <p>Recent investment pushing for European return.</p>
            </div>
            <div class="team-showcase-card">
                <h4>🟣 Aston Villa</h4>
                <p>Historic club rebuilding European credentials.</p>
            </div>
            <div class="team-showcase-card">
                <h4>🔵 Brighton</h4>
                <p>Modern recruitment model achieving consistent results.</p>
            </div>
        </div>
    </div>

    <div class="panel" style="background: rgba(76, 175, 80, 0.05);">
        <h3>🎯 Why Block 2 Matters</h3>
        <div class="importance-grid">
            <div class="importance-item">
                <span class="importance-icon">🌍</span>
                <h4>European Football</h4>
                <p>Access to Europa and Conference League competition</p>
            </div>
            <div class="importance-item">
                <span class="importance-icon">💰</span>
                <h4>Revenue Boost</h4>
                <p>£15-30m additional income from European football</p>
            </div>
            <div class="importance-item">
                <span class="importance-icon">📈</span>
                <h4>Player Appeal</h4>
                <p>Attract better talent with European football offer</p>
            </div>
            <div class="importance-item">
                <span class="importance-icon">🏆</span>
                <h4>Trophy Chances</h4>
                <p>Realistic opportunity for European silverware</p>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="block-navigation">
            <a href="?tab=premier-league&subtab=blocks-1" class="nav-btn">← Block 1</a>
            <a href="?tab=premier-league&subtab=blocks-dynamic" class="nav-btn" style="background: rgba(76, 175, 80, 0.1); color: <?php echo $blockInfo['color']; ?>; border-color: <?php echo $blockInfo['color']; ?>;">
                🔥 Live Data
            </a>
            <a href="?tab=premier-league&subtab=blocks-3" class="nav-btn">Block 3 →</a>
        </div>
    </div>
</div>

<style>
.block-detail-page { max-width: 1400px; margin: 0 auto; }
.block-header-section { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
.block-header-section h2 { margin: 0; font-size: 2em; }
.position-badge { padding: 8px 15px; border-radius: 6px; font-size: 0.9em; font-weight: bold; }
.characteristics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
.char-card { background: rgba(255, 255, 255, 0.03); padding: 20px; border-radius: 8px; border-left: 4px solid; }
.char-card h4 { margin: 0 0 15px 0; color: #fff; font-size: 1.1em; }
.char-card ul { list-style: none; padding: 0; margin: 0; }
.char-card ul li { padding: 8px 0; color: #bbb; font-size: 0.95em; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
.char-card ul li:last-child { border-bottom: none; }
.teams-showcase { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 20px; }
.team-showcase-card { background: rgba(255, 255, 255, 0.03); padding: 15px; border-radius: 8px; border: 1px solid rgba(76, 175, 80, 0.3); }
.team-showcase-card h4 { margin: 0 0 10px 0; color: #4CAF50; }
.team-showcase-card p { margin: 0; color: #bbb; font-size: 0.9em; }
.importance-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 20px; }
.importance-item { text-align: center; padding: 20px; background: rgba(255, 255, 255, 0.03); border-radius: 8px; }
.importance-icon { font-size: 3em; display: block; margin-bottom: 10px; }
.importance-item h4 { margin: 10px 0; color: #4CAF50; }
.importance-item p { margin: 0; color: #bbb; font-size: 0.9em; }
.block-navigation { display: flex; justify-content: space-between; gap: 15px; flex-wrap: wrap; }
.nav-btn { flex: 1; min-width: 150px; padding: 12px 20px; background: rgba(255, 255, 255, 0.05); color: #fff; text-decoration: none; text-align: center; border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.2s; font-weight: 500; }
.nav-btn:hover { background: rgba(255, 255, 255, 0.1); transform: translateY(-2px); }
</style>