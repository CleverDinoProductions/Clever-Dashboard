<?php
// Block 2: Positions 5-8 - European Zone
?>
<div class="block-detail-wrapper">
    <div class="panel">
        <div class="block-title-header block-2-theme">
            <h2>🌟 Block 2: European Zone</h2>
            <span class="position-badge block-2-badge">Positions 5-8</span>
        </div>
        <p class="block-description">
            Teams competing for Europa League and Conference League qualification. This competitive mid-table zone 
            requires tactical flexibility and consistent performances to secure European football.
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
                        ['pos' => 5, 'team' => 'Newcastle United', 'pld' => 18, 'w' => 9, 'd' => 4, 'l' => 5, 'gf' => 30, 'ga' => 21, 'gd' => 9, 'pts' => 31],
                        ['pos' => 6, 'team' => 'Brighton', 'pld' => 18, 'w' => 8, 'd' => 6, 'l' => 4, 'gf' => 29, 'ga' => 23, 'gd' => 6, 'pts' => 30],
                        ['pos' => 7, 'team' => 'Aston Villa', 'pld' => 18, 'w' => 8, 'd' => 5, 'l' => 5, 'gf' => 28, 'ga' => 25, 'gd' => 3, 'pts' => 29],
                        ['pos' => 8, 'team' => 'Tottenham', 'pld' => 18, 'w' => 8, 'd' => 3, 'l' => 7, 'gf' => 32, 'ga' => 26, 'gd' => 6, 'pts' => 27],
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
                    <li>Push for European qualification</li>
                    <li>Balance attack and defense</li>
                    <li>Tactical flexibility required</li>
                    <li>Consistent squad rotation needed</li>
                </ul>
            </div>
            
            <div class="char-card block-2-card">
                <h4>📊 Key Metrics</h4>
                <ul>
                    <li>Points Per Game: 1.5-1.8</li>
                    <li>Win Rate: 45-55%</li>
                    <li>Goals Per Game: 1.5-2.0</li>
                    <li>Clean Sheets: 30-40%</li>
                </ul>
            </div>
            
            <div class="char-card block-2-card">
                <h4>⚔️ Tactical Approach</h4>
                <ul>
                    <li>Adaptable game plans</li>
                    <li>Strong away performances crucial</li>
                    <li>Counter-attacking threat</li>
                    <li>Set-piece importance</li>
                </ul>
            </div>
            
            <div class="char-card block-2-card">
                <h4>💰 Investment Level</h4>
                <ul>
                    <li>Moderate to high transfer spend</li>
                    <li>Strategic recruitment</li>
                    <li>Developing talent focus</li>
                    <li>Europa/Conference League revenue</li>
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
                    <li>3-4 game winning streaks</li>
                    <li>Improved goal difference (positive swing)</li>
                    <li>Better home form (2.0+ PPG at home)</li>
                    <li>Tactical consistency and stability</li>
                </ul>
            </div>
            
            <div class="movement-card down-movement">
                <h4>⬇️ Teams Dropping to Block 3</h4>
                <p>Loss of European contention signals:</p>
                <ul>
                    <li>Inconsistent results against top teams</li>
                    <li>Poor away form dragging down PPG</li>
                    <li>Squad depth issues emerging</li>
                    <li>Loss of key players to injury</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- European Qualification -->
    <div class="panel">
        <h3>🏆 European Qualification Paths</h3>
        <div class="euro-paths">
            <div class="euro-card">
                <h4>🌟 Europa League</h4>
                <p><strong>Typical Requirement:</strong> 5th place (60-65 points)</p>
                <ul>
                    <li>Automatic group stage entry</li>
                    <li>Prestigious European competition</li>
                    <li>Thursday night fixtures</li>
                    <li>Revenue boost for club</li>
                </ul>
            </div>
            
            <div class="euro-card">
                <h4>🎪 Conference League</h4>
                <p><strong>Typical Requirement:</strong> 6th-7th place (55-60 points)</p>
                <ul>
                    <li>UEFA's third-tier competition</li>
                    <li>European football experience</li>
                    <li>Squad development opportunity</li>
                    <li>Path to Europa League</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Historical Context -->
    <div class="panel">
        <h3>📚 Historical Block 2 Analysis</h3>
        <div class="history-stats">
            <div class="stat-card block-2-stat">
                <div class="stat-value">62 pts</div>
                <div class="stat-label">Average 5th Place Points (Last 5 Years)</div>
            </div>
            <div class="stat-card block-2-stat">
                <div class="stat-value">56 pts</div>
                <div class="stat-label">Average 8th Place Points (Last 5 Years)</div>
            </div>
            <div class="stat-card block-2-stat">
                <div class="stat-value">6-8</div>
                <div class="stat-label">Typical Point Gap Between 5th & 8th</div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="panel">
        <div class="block-navigation">
            <a href="?tab=premier-league&subtab=blocks-1" class="nav-btn block-1-nav">← Block 1</a>
            <a href="?tab=premier-league&subtab=blocks-overview" class="nav-btn">Overview</a>
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
    color: #4CAF50;
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

.euro-paths {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.euro-card {
    background: rgba(76, 175, 80, 0.1);
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #4CAF50;
}

.euro-card h4 {
    margin-top: 0;
    color: #4CAF50;
}

.euro-card ul {
    margin: 10px 0;
    padding-left: 20px;
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