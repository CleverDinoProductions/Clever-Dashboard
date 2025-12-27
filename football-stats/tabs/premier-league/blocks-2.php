<?php
// Block 2: Positions 5-8 - European Zone
// This would connect to your database to fetch current standings

// Placeholder for database query - adjust based on your actual schema
// $block2Teams = $pdo->query("SELECT * FROM premier_league_table WHERE position BETWEEN 5 AND 8 ORDER BY position")->fetchAll();
?>
<div class="block-detail-wrapper">
    <div class="panel">
        <div class="block-title-header block-2-theme">
            <h2>🌟 Block 2: European Zone</h2>
            <span class="position-badge block-2-badge">Positions 5-8</span>
        </div>
        <p class="block-description">
            Teams competing for Europa League and UEFA Conference League qualification. 
            These sides require tactical flexibility, strong squad depth, and consistency to secure European football.
        </p>
    </div>

    <!-- Current Teams in Block -->
    <div class="panel">
        <h3>📈 Current Block 2 Teams</h3>
        <div class="teams-table-wrapper">
            <table class="standings-table block-2-table">
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
                        ['pos' => 5, 'team' => 'Newcastle United', 'pld' => 18, 'w' => 9, 'd' => 5, 'l' => 4, 'gf' => 31, 'ga' => 21, 'gd' => 10, 'pts' => 32],
                        ['pos' => 6, 'team' => 'Tottenham', 'pld' => 18, 'w' => 9, 'd' => 4, 'l' => 5, 'gf' => 34, 'ga' => 25, 'gd' => 9, 'pts' => 31],
                        ['pos' => 7, 'team' => 'Aston Villa', 'pld' => 18, 'w' => 8, 'd' => 5, 'l' => 5, 'gf' => 28, 'ga' => 25, 'gd' => 3, 'pts' => 29],
                        ['pos' => 8, 'team' => 'Brighton', 'pld' => 18, 'w' => 7, 'd' => 7, 'l' => 4, 'gf' => 27, 'ga' => 24, 'gd' => 3, 'pts' => 28],
                    ];
                    
                    foreach ($placeholderTeams as $team) {
                        $ppg = round($team['pts'] / $team['pld'], 2);
                        echo "<tr>";
                        echo "<td class='pos-cell block-2-pos'>{$team['pos']}</td>";
                        echo "<td class='team-cell'><strong>{$team['team']}</strong></td>";
                        echo "<td>{$team['pld']}</td>";
                        echo "<td>{$team['w']}</td>";
                        echo "<td>{$team['d']}</td>";
                        echo "<td>{$team['l']}</td>";
                        echo "<td>{$team['gf']}</td>";
                        echo "<td>{$team['ga']}</td>";
                        echo "<td class='gd-cell'>" . ($team['gd'] > 0 ? '+' : '') . "{$team['gd']}</td>";
                        echo "<td class='pts-cell block-2-pts'><strong>{$team['pts']}</strong></td>";
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
        <h3>🔑 Block 2 Characteristics</h3>
        <div class="characteristics-grid">
            <div class="char-card block-2-card">
                <h4>🎯 Mentality</h4>
                <ul>
                    <li>European qualification target</li>
                    <li>Balanced attack and defense</li>
                    <li>Tactical flexibility required</li>
                    <li>Strong cup run potential</li>
                </ul>
            </div>
            
            <div class="char-card block-2-card">
                <h4>📈 Key Metrics</h4>
                <ul>
                    <li>Points Per Game: 1.5-1.8</li>
                    <li>Win Rate: 45-55%</li>
                    <li>Goals Per Game: 1.5-1.8</li>
                    <li>Clean Sheets: 30-35%</li>
                </ul>
            </div>
            
            <div class="char-card block-2-card">
                <h4>🛡️ Tactical Approach</h4>
                <ul>
                    <li>Solid defensive structure</li>
                    <li>Counter-attacking threat</li>
                    <li>Set-piece importance</li>
                    <li>Rotational squad management</li>
                </ul>
            </div>
            
            <div class="char-card block-2-card">
                <h4>💰 Investment Level</h4>
                <ul>
                    <li>Mid-to-high transfer spend</li>
                    <li>Competitive wage structure</li>
                    <li>Strategic recruitment focus</li>
                    <li>Youth development emphasis</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Movement Analysis -->
    <div class="panel">
        <h3>🔄 Block Movement Trends</h3>
        <div class="movement-analysis">
            <div class="movement-card up-movement">
                <h4>⬆️ Teams Moving Into Block 2</h4>
                <p>Teams climbing from Block 3 typically show:</p>
                <ul>
                    <li>Winning streaks of 3-4 games</li>
                    <li>Improved away form (1.5+ PPG)</li>
                    <li>Key player injury returns</li>
                    <li>Tactical system clicking into place</li>
                </ul>
            </div>
            
            <div class="movement-card down-movement">
                <h4>⬇️ Teams Dropping from Block 1</h4>
                <p>Falling from title race due to:</p>
                <ul>
                    <li>Loss of form in big matches</li>
                    <li>Squad depth issues exposed</li>
                    <li>European competition fatigue</li>
                    <li>Inconsistent results against lower blocks</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- European Qualification Context -->
    <div class="panel">
        <h3>🌟 European Qualification Scenarios</h3>
        <div class="euro-context">
            <div class="euro-card">
                <h4>🏆 5th Place - Europa League</h4>
                <p>Automatic qualification for UEFA Europa League group stage. Typically requires 60-65 points.</p>
            </div>
            <div class="euro-card">
                <h4>⚽ 6th Place - Conference League</h4>
                <p>Automatic qualification for UEFA Conference League. Usually secured with 55-60 points.</p>
            </div>
            <div class="euro-card">
                <h4>🏆 FA Cup Route</h4>
                <p>Cup winners in Block 2 can secure European football regardless of league position.</p>
            </div>
        </div>
        <div class="h2h-note block-2-note">
            <p>💡 <strong>Key Insight:</strong> Block 2 teams need sustained consistency rather than brilliance. 
            A steady 1.6-1.7 PPG through the season typically delivers European qualification. 
            Current leaders are on pace for 60-62 points - right in the target range.</p>
        </div>
    </div>

    <!-- Historical Context -->
    <div class="panel">
        <h3>📚 Historical Block 2 Analysis</h3>
        <div class="history-stats">
            <div class="stat-card block-2-stat">
                <div class="stat-value">61 pts</div>
                <div class="stat-label">Average 5th Place Points (Last 5 Years)</div>
            </div>
            <div class="stat-card block-2-stat">
                <div class="stat-value">56 pts</div>
                <div class="stat-label">Average 8th Place Points (Last 5 Years)</div>
            </div>
            <div class="stat-card block-2-stat">
                <div class="stat-value">8-10</div>
                <div class="stat-label">Typical Point Gap Between 5th & 8th</div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="panel">
        <div class="block-navigation">
            <a href="?tab=premier-league&subtab=blocks-1" class="nav-btn block-1-nav">← Block 1</a>
            <a href="?tab=premier-league&subtab=blocks-overview" class="nav-btn">🎯 Overview</a>
            <a href="?tab=premier-league&subtab=blocks-3" class="nav-btn block-3-nav">Block 3 →</a>
        </div>
    </div>
</div>

<style>
.block-2-theme h2 {
    color: #4CAF50;
}

.block-2-badge {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    border: 2px solid #4CAF50;
}

.block-2-table th {
    background: rgba(76, 175, 80, 0.2);
    border-bottom: 2px solid #4CAF50;
}

.block-2-pos {
    color: #4CAF50 !important;
}

.block-2-pts {
    background: rgba(76, 175, 80, 0.1);
}

.block-2-card {
    background: rgba(76, 175, 80, 0.05);
    border-left: 3px solid #4CAF50;
}

.block-2-card h4 {
    color: #4CAF50;
}

.block-2-card ul li:before {
    color: #4CAF50;
}

.euro-context {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.euro-card {
    background: rgba(76, 175, 80, 0.1);
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #4CAF50;
}

.euro-card h4 {
    color: #4CAF50;
    margin-top: 0;
    margin-bottom: 8px;
}

.euro-card p {
    color: #bbb;
    font-size: 0.95em;
    margin: 0;
}

.block-2-note {
    background: rgba(76, 175, 80, 0.1);
    border-left: 3px solid #4CAF50;
    margin-top: 15px;
}

.block-2-stat {
    background: rgba(76, 175, 80, 0.1);
    border: 2px solid rgba(76, 175, 80, 0.3);
}

.block-2-stat .stat-value {
    color: #4CAF50;
}

.block-1-nav {
    border-color: #FFD700;
    color: #FFD700;
}

.block-3-nav {
    border-color: #2196F3;
    color: #2196F3;
}
</style>