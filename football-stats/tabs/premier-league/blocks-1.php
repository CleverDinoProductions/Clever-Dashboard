<?php
// Block 1: Positions 1-4 - Title Contenders
// This would connect to your database to fetch current standings

// Placeholder for database query - adjust based on your actual schema
// $block1Teams = $pdo->query("SELECT * FROM premier_league_table WHERE position BETWEEN 1 AND 4 ORDER BY position")->fetchAll();
?>
<div class="block-detail-wrapper">
    <div class="panel">
        <div class="block-title-header block-1-theme">
            <h2>🏆 Block 1: Title Contenders</h2>
            <span class="position-badge">Positions 1-4</span>
        </div>
        <p class="block-description">
            Teams competing for the Premier League title and automatic Champions League qualification. 
            These sides demonstrate attacking mentality, high confidence, and consistent winning records.
        </p>
    </div>

    <!-- Current Teams in Block -->
    <div class="panel">
        <h3>📈 Current Block 1 Teams</h3>
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
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Placeholder - Replace with actual database query
                    $placeholderTeams = [
                        ['pos' => 1, 'team' => 'Liverpool', 'pld' => 18, 'w' => 13, 'd' => 4, 'l' => 1, 'gf' => 39, 'ga' => 16, 'gd' => 23, 'pts' => 43],
                        ['pos' => 2, 'team' => 'Arsenal', 'pld' => 18, 'w' => 11, 'd' => 5, 'l' => 2, 'gf' => 35, 'ga' => 16, 'gd' => 19, 'pts' => 38],
                        ['pos' => 3, 'team' => 'Chelsea', 'pld' => 18, 'w' => 10, 'd' => 5, 'l' => 3, 'gf' => 35, 'ga' => 20, 'gd' => 15, 'pts' => 35],
                        ['pos' => 4, 'team' => 'Manchester City', 'pld' => 18, 'w' => 10, 'd' => 4, 'l' => 4, 'gf' => 32, 'ga' => 21, 'gd' => 11, 'pts' => 34],
                    ];
                    
                    foreach ($placeholderTeams as $team) {
                        $ppg = round($team['pts'] / $team['pld'], 2);
                        echo "<tr>";
                        echo "<td class='pos-cell'>{$team['pos']}</td>";
                        echo "<td class='team-cell'><strong>{$team['team']}</strong></td>";
                        echo "<td>{$team['pld']}</td>";
                        echo "<td>{$team['w']}</td>";
                        echo "<td>{$team['d']}</td>";
                        echo "<td>{$team['l']}</td>";
                        echo "<td>{$team['gf']}</td>";
                        echo "<td>{$team['ga']}</td>";
                        echo "<td class='gd-cell'>" . ($team['gd'] > 0 ? '+' : '') . "{$team['gd']}</td>";
                        echo "<td class='pts-cell'><strong>{$team['pts']}</strong></td>";
                        echo "<td>{$ppg}</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

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
                <h4>📈 Key Metrics</h4>
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

    <!-- Movement Analysis -->
    <div class="panel">
        <h3>🔄 Block Movement Trends</h3>
        <div class="movement-analysis">
            <div class="movement-card up-movement">
                <h4>⬆️ Teams Moving Into Block 1</h4>
                <p>Teams climbing from Block 2 typically show:</p>
                <ul>
                    <li>Extended winning runs (5+ games)</li>
                    <li>Improved goal difference (+15 or better)</li>
                    <li>Strong home form (2.5+ PPG at home)</li>
                    <li>Consistency in key player availability</li>
                </ul>
            </div>
            
            <div class="movement-card down-movement">
                <h4>⬇️ Teams Dropping to Block 2</h4>
                <p>Relegation from title race signals:</p>
                <ul>
                    <li>Injury crises affecting key players</li>
                    <li>Loss of form in crucial fixtures</li>
                    <li>Fatigue from multiple competitions</li>
                    <li>Psychological pressure of title expectations</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Head-to-Head -->
    <div class="panel">
        <h3>⚔️ Block 1 Head-to-Head Record</h3>
        <p class="note">Performance between title contenders often determines final standings</p>
        <div class="h2h-note">
            <p>💡 <strong>Key Insight:</strong> Teams in Block 1 typically need 85-95 points to win the title, 
            and 70-75 points minimum to secure Champions League football. Current leaders are on pace for 90+ points.</p>
        </div>
    </div>

    <!-- Historical Context -->
    <div class="panel">
        <h3>📚 Historical Block 1 Analysis</h3>
        <div class="history-stats">
            <div class="stat-card">
                <div class="stat-value">89 pts</div>
                <div class="stat-label">Average Title-Winning Points (Last 5 Years)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">71 pts</div>
                <div class="stat-label">Average 4th Place Points (Last 5 Years)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">12-15</div>
                <div class="stat-label">Typical Point Gap Between 1st & 4th</div>
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

.movement-analysis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.movement-card {
    padding: 20px;
    border-radius: 8px;
}

.up-movement {
    background: rgba(76, 175, 80, 0.1);
    border-left: 4px solid #4CAF50;
}

.down-movement {
    background: rgba(255, 152, 0, 0.1);
    border-left: 4px solid #FF9800;
}

.movement-card h4 {
    margin-top: 0;
}

.movement-card ul {
    margin: 10px 0;
    padding-left: 20px;
}

.movement-card ul li {
    margin: 8px 0;
    color: #bbb;
}

.h2h-note {
    background: rgba(255, 215, 0, 0.1);
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
    border-left: 3px solid #FFD700;
}

.history-stats {
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
    margin-bottom: 10px;
}
</style>