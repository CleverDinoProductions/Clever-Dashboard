<?php
// Block 3: Positions 9-12 - Safe Mid-Table
// This would connect to your database to fetch current standings

// Placeholder for database query - adjust based on your actual schema
// $block3Teams = $pdo->query("SELECT * FROM premier_league_table WHERE position BETWEEN 9 AND 12 ORDER BY position")->fetchAll();
?>
<div class="block-detail-wrapper">
    <div class="panel">
        <div class="block-title-header block-3-theme">
            <h2>✅ Block 3: Safe Mid-Table</h2>
            <span class="position-badge block-3-badge">Positions 9-12</span>
        </div>
        <p class="block-description">
            The Premier League security zone - teams with no relegation concerns and established stability. 
            This is the ideal position for newly promoted sides breaking the promotion curse, like Leeds United's target zone.
        </p>
    </div>

    <!-- Current Teams in Block -->
    <div class="panel">
        <h3>📈 Current Block 3 Teams</h3>
        <div class="teams-table-wrapper">
            <table class="standings-table block-3-table">
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
                        ['pos' => 9, 'team' => 'Manchester United', 'pld' => 18, 'w' => 7, 'd' => 5, 'l' => 6, 'gf' => 26, 'ga' => 24, 'gd' => 2, 'pts' => 26],
                        ['pos' => 10, 'team' => 'West Ham', 'pld' => 18, 'w' => 7, 'd' => 4, 'l' => 7, 'gf' => 24, 'ga' => 27, 'gd' => -3, 'pts' => 25],
                        ['pos' => 11, 'team' => 'Crystal Palace', 'pld' => 18, 'w' => 6, 'd' => 6, 'l' => 6, 'gf' => 23, 'ga' => 24, 'gd' => -1, 'pts' => 24],
                        ['pos' => 12, 'team' => 'Leeds United', 'pld' => 18, 'w' => 6, 'd' => 5, 'l' => 7, 'gf' => 25, 'ga' => 28, 'gd' => -3, 'pts' => 23],
                    ];
                    
                    foreach ($placeholderTeams as $team) {
                        $ppg = round($team['pts'] / $team['pld'], 2);
                        $isLeeds = ($team['team'] == 'Leeds United');
                        echo "<tr" . ($isLeeds ? " class='leeds-row'" : "") . ">";
                        echo "<td class='pos-cell block-3-pos'>{$team['pos']}</td>";
                        echo "<td class='team-cell'><strong>{$team['team']}" . ($isLeeds ? " 💛" : "") . "</strong></td>";
                        echo "<td>{$team['pld']}</td>";
                        echo "<td>{$team['w']}</td>";
                        echo "<td>{$team['d']}</td>";
                        echo "<td>{$team['l']}</td>";
                        echo "<td>{$team['gf']}</td>";
                        echo "<td>{$team['ga']}</td>";
                        echo "<td class='gd-cell'>" . ($team['gd'] > 0 ? '+' : '') . "{$team['gd']}</td>";
                        echo "<td class='pts-cell block-3-pts'><strong>{$team['pts']}</strong></td>";
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
        <h3>🔑 Block 3 Characteristics</h3>
        <div class="characteristics-grid">
            <div class="char-card block-3-card">
                <h4>🎯 Mentality</h4>
                <ul>
                    <li>Premier League security achieved</li>
                    <li>Build for future success</li>
                    <li>Tactical stability prioritized</li>
                    <li>No immediate pressure</li>
                </ul>
            </div>
            
            <div class="char-card block-3-card">
                <h4>📈 Key Metrics</h4>
                <ul>
                    <li>Points Per Game: 1.2-1.5</li>
                    <li>Win Rate: 35-45%</li>
                    <li>Goals Per Game: 1.3-1.6</li>
                    <li>Clean Sheets: 25-30%</li>
                </ul>
            </div>
            
            <div class="char-card block-3-card">
                <h4>🛡️ Tactical Approach</h4>
                <ul>
                    <li>Pragmatic game management</li>
                    <li>Home fortress mentality</li>
                    <li>Away resilience focus</li>
                    <li>Points over performances</li>
                </ul>
            </div>
            
            <div class="char-card block-3-card">
                <h4>💰 Investment Level</h4>
                <ul>
                    <li>Strategic spending approach</li>
                    <li>Value-driven recruitment</li>
                    <li>Squad development focus</li>
                    <li>Long-term project building</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Movement Analysis -->
    <div class="panel">
        <h3>🔄 Block Movement Trends</h3>
        <div class="movement-analysis">
            <div class="movement-card up-movement">
                <h4>⬆️ Teams Moving Into Block 3</h4>
                <p>Teams climbing from Block 4 show:</p>
                <ul>
                    <li>Escape from relegation danger</li>
                    <li>Improved defensive organization</li>
                    <li>Key wins against fellow strugglers</li>
                    <li>Managerial stability restored</li>
                </ul>
            </div>
            
            <div class="movement-card lateral-movement">
                <h4>↔️ Within-Block Movement</h4>
                <p>Block 3 is the most stable:</p>
                <ul>
                    <li>Teams rarely leave once established</li>
                    <li>Position swaps within block common</li>
                    <li>Mathematical safety by November</li>
                    <li>Focus shifts to cup competitions</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Leeds United Context -->
    <div class="panel">
        <h3>💛 Leeds United: Block 3 Success Story</h3>
        <div class="leeds-context">
            <p class="leeds-intro">
                Leeds United's position in Block 3 represents a historic achievement - breaking the promotion curse 
                that has plagued newly promoted Championship winners for 18 years.
            </p>
            <div class="leeds-stats">
                <div class="leeds-stat-card">
                    <h4>✅ Curse-Breaking Achievement</h4>
                    <ul>
                        <li>First Championship winners to win opening game since 2007</li>
                        <li>Avoided typical promoted team collapse</li>
                        <li>Established in safe mid-table by autumn</li>
                        <li>Daniel Farke's tactical preparation delivered</li>
                    </ul>
                </div>
                <div class="leeds-stat-card">
                    <h4>🏰 Elland Road Fortress</h4>
                    <ul>
                        <li>Strong home form fundamental to success</li>
                        <li>Clean sheet record at home exceptional</li>
                        <li>Home advantage prioritized over away heroics</li>
                        <li>2+ PPG at Elland Road target achieved</li>
                    </ul>
                </div>
                <div class="leeds-stat-card">
                    <h4>📈 Buffer from Relegation</h4>
                    <ul>
                        <li>7-10 point gap to Block 5 maintained</li>
                        <li>Mathematical safety achieved early</li>
                        <li>Can focus on building, not surviving</li>
                        <li>Validates systematic preparation approach</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Historical Context -->
    <div class="panel">
        <h3>📚 Historical Block 3 Analysis</h3>
        <div class="history-stats">
            <div class="stat-card block-3-stat">
                <div class="stat-value">45-50 pts</div>
                <div class="stat-label">Typical Block 3 Final Points Range</div>
            </div>
            <div class="stat-card block-3-stat">
                <div class="stat-value">1.2-1.4</div>
                <div class="stat-label">Average PPG for Block 3 Teams</div>
            </div>
            <div class="stat-card block-3-stat">
                <div class="stat-value">95%+</div>
                <div class="stat-label">Survival Rate Once Established in Block 3</div>
            </div>
        </div>
        <div class="h2h-note block-3-note">
            <p>💡 <strong>Key Insight:</strong> Block 3 is the "sweet spot" for Premier League football - 
            safe from relegation, no European pressure, and time to build sustainable success. 
            Teams here typically need just 45-50 points over the season, achieved through steady 1.2-1.4 PPG.</p>
        </div>
    </div>

    <!-- Navigation -->
    <div class="panel">
        <div class="block-navigation">
            <a href="?tab=premier-league&subtab=blocks-2" class="nav-btn block-2-nav">← Block 2</a>
            <a href="?tab=premier-league&subtab=blocks-overview" class="nav-btn">🎯 Overview</a>
            <a href="?tab=premier-league&subtab=blocks-4" class="nav-btn block-4-nav">Block 4 →</a>
        </div>
    </div>
</div>

<style>
.block-3-theme h2 {
    color: #2196F3;
}

.block-3-badge {
    background: rgba(33, 150, 243, 0.2);
    color: #2196F3;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    border: 2px solid #2196F3;
}

.block-3-table th {
    background: rgba(33, 150, 243, 0.2);
    border-bottom: 2px solid #2196F3;
}

.block-3-pos {
    color: #2196F3 !important;
}

.block-3-pts {
    background: rgba(33, 150, 243, 0.1);
}

.leeds-row {
    background: rgba(255, 205, 0, 0.05);
    border-left: 3px solid #FFCD00;
}

.block-3-card {
    background: rgba(33, 150, 243, 0.05);
    border-left: 3px solid #2196F3;
}

.block-3-card h4 {
    color: #2196F3;
}

.block-3-card ul li:before {
    color: #2196F3;
}

.lateral-movement {
    background: rgba(33, 150, 243, 0.1);
    border-left: 4px solid #2196F3;
}

.leeds-context {
    background: rgba(255, 205, 0, 0.05);
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #FFCD00;
    margin-top: 15px;
}

.leeds-intro {
    color: #ddd;
    font-size: 1.05em;
    margin-bottom: 15px;
    font-style: italic;
}

.leeds-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 15px;
}

.leeds-stat-card {
    background: rgba(33, 150, 243, 0.1);
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #2196F3;
}

.leeds-stat-card h4 {
    color: #FFCD00;
    margin-top: 0;
    margin-bottom: 10px;
}

.leeds-stat-card ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.leeds-stat-card ul li {
    padding: 5px 0;
    color: #bbb;
    font-size: 0.95em;
}

.leeds-stat-card ul li:before {
    content: "✓ ";
    color: #2196F3;
    font-weight: bold;
}

.block-3-note {
    background: rgba(33, 150, 243, 0.1);
    border-left: 3px solid #2196F3;
    margin-top: 15px;
}

.block-3-stat {
    background: rgba(33, 150, 243, 0.1);
    border: 2px solid rgba(33, 150, 243, 0.3);
}

.block-3-stat .stat-value {
    color: #2196F3;
}

.block-2-nav {
    border-color: #4CAF50;
    color: #4CAF50;
}

.block-4-nav {
    border-color: #FF9800;
    color: #FF9800;
}
</style>