<?php
// Block 4: Positions 13-16 - Danger Zone
?>
<div class="block-detail-wrapper">
    <div class="panel">
        <div class="block-title-header block-4-theme">
            <h2>⚠️ Block 4: Danger Zone</h2>
            <span class="position-badge block-4-badge">Positions 13-16</span>
        </div>
        <p class="block-description">
            Teams in the danger zone face mounting pressure to avoid relegation battles. This critical block requires 
            immediate tactical changes, consistent results, and strong mentality to climb back to Block 3 safety.
        </p>
    </div>

    <!-- Current Teams in Block -->
    <div class="panel">
        <h3>📈 Current Block 4 Teams</h3>
        <div class="teams-table-wrapper">
            <table class="standings-table block-4-table">
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
                        ['pos' => 13, 'team' => 'Fulham', 'pld' => 18, 'w' => 5, 'd' => 7, 'l' => 6, 'gf' => 22, 'ga' => 25, 'gd' => -3, 'pts' => 22],
                        ['pos' => 14, 'team' => 'Brentford', 'pld' => 18, 'w' => 5, 'd' => 6, 'l' => 7, 'gf' => 24, 'ga' => 28, 'gd' => -4, 'pts' => 21],
                        ['pos' => 15, 'team' => 'Crystal Palace', 'pld' => 18, 'w' => 4, 'd' => 8, 'l' => 6, 'gf' => 19, 'ga' => 24, 'gd' => -5, 'pts' => 20],
                        ['pos' => 16, 'team' => 'Everton', 'pld' => 18, 'w' => 4, 'd' => 7, 'l' => 7, 'gf' => 18, 'ga' => 26, 'gd' => -8, 'pts' => 19],
                    ];
                    
                    foreach ($placeholderTeams as $team) {
                        $ppg = round($team['pts'] / $team['pld'], 2);
                        echo "<tr class='danger-row'>";
                        echo "<td class='pos-cell block-4-pos'>{$team['pos']}</td>";
                        echo "<td class='team-cell'><strong>{$team['team']}</strong></td>";
                        echo "<td>{$team['pld']}</td>";
                        echo "<td>{$team['w']}</td>";
                        echo "<td>{$team['d']}</td>";
                        echo "<td>{$team['l']}</td>";
                        echo "<td>{$team['gf']}</td>";
                        echo "<td>{$team['ga']}</td>";
                        echo "<td class='gd-cell'>" . ($team['gd'] > 0 ? '+' : '') . "{$team['gd']}</td>";
                        echo "<td class='pts-cell block-4-pts'><strong>{$team['pts']}</strong></td>";
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
        <h3>🔑 Block 4 Characteristics</h3>
        <div class="characteristics-grid">
            <div class="char-card block-4-card">
                <h4>🎯 Mentality</h4>
                <ul>
                    <li>Pressure building significantly</li>
                    <li>Defensive mistakes costly</li>
                    <li>Every point matters</li>
                    <li>Squad confidence fragile</li>
                </ul>
            </div>
            
            <div class="char-card block-4-card">
                <h4>📊 Key Metrics</h4>
                <ul>
                    <li>Points Per Game: 1.0-1.3</li>
                    <li>Win Rate: 25-35%</li>
                    <li>Goals Per Game: 1.0-1.3</li>
                    <li>Clean Sheets: 20-30%</li>
                </ul>
            </div>
            
            <div class="char-card block-4-card">
                <h4>⚠️ Tactical Approach</h4>
                <ul>
                    <li>Defensive solidity essential</li>
                    <li>Avoid heavy defeats at all costs</li>
                    <li>Set-pieces become crucial</li>
                    <li>Tactical changes frequent</li>
                </ul>
            </div>
            
            <div class="char-card block-4-card">
                <h4>💼 Management Pressure</h4>
                <ul>
                    <li>Managerial scrutiny increasing</li>
                    <li>Fan pressure mounting</li>
                    <li>Board discussions ongoing</li>
                    <li>January transfer urgency</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Escape Plan -->
    <div class="panel">
        <h3>🎯 Escaping the Danger Zone</h3>
        <div class="escape-strategies">
            <div class="strategy-card">
                <h4>🏖️ Home Fortress Strategy</h4>
                <p>Teams must maximize home points to escape Block 4:</p>
                <ul>
                    <li><strong>Target:</strong> 2.0+ PPG at home (30+ points from 19 home games)</li>
                    <li>Create intimidating atmosphere</li>
                    <li>Aggressive pressing in familiar surroundings</li>
                    <li>Capitalize on home set-pieces</li>
                </ul>
            </div>
            
            <div class="strategy-card">
                <h4>🛡️ Defensive Stability</h4>
                <p>Tighten defense to stop points bleeding:</p>
                <ul>
                    <li>Reduce goals conceded to under 1.3 per game</li>
                    <li>Organized low block against top teams</li>
                    <li>No defensive individual errors</li>
                    <li>Clean sheets in winnable matches</li>
                </ul>
            </div>
            
            <div class="strategy-card">
                <h4>🔄 Six-Pointer Wins</h4>
                <p>Victory against fellow strugglers is essential:</p>
                <ul>
                    <li>Matches vs Block 4-5 teams = survival defining</li>
                    <li>Winning creates 3-point swing</li>
                    <li>Psychological advantage gained</li>
                    <li>3-4 wins required to reach Block 3</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Movement Analysis -->
    <div class="panel">
        <h3>🔄 Block Movement Trends</h3>
        <div class="movement-analysis">
            <div class="movement-card up-movement">
                <h4>⬆️ Teams Escaping to Block 3</h4>
                <p>Successfully climbing out requires:</p>
                <ul>
                    <li>4-5 game unbeaten run</li>
                    <li>3+ wins in 6 games</li>
                    <li>Reaching 30-35 point safety mark</li>
                    <li>Goal difference improvement</li>
                </ul>
            </div>
            
            <div class="movement-card down-movement critical">
                <h4>⬇️ Teams Dropping to Block 5</h4>
                <p>Relegation zone descent signals:</p>
                <ul>
                    <li>6+ games without a win</li>
                    <li>Heavy defeats damaging confidence</li>
                    <li>Managerial change disruption</li>
                    <li>Squad morale collapse</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Critical Timeline -->
    <div class="panel">
        <h3>⏱️ Critical Timeline for Block 4 Teams</h3>
        <div class="timeline">
            <div class="timeline-item">
                <h4>📅 October-November (Matchday 10-12)</h4>
                <p>First crisis point - teams with under 11 points face 89% relegation likelihood</p>
            </div>
            <div class="timeline-item">
                <h4>🎅 December-January</h4>
                <p>Fixture congestion + transfer window - make or break period for survival hopes</p>
            </div>
            <div class="timeline-item">
                <h4>🌻 March-April</h4>
                <p>Final push - teams need 35+ points by now to avoid mathematical relegation danger</p>
            </div>
        </div>
    </div>

    <!-- Historical Context -->
    <div class="panel">
        <h3>📚 Historical Block 4 Analysis</h3>
        <div class="history-stats">
            <div class="stat-card block-4-stat">
                <div class="stat-value">42 pts</div>
                <div class="stat-label">Average 13th Place Points (Last 5 Years)</div>
            </div>
            <div class="stat-card block-4-stat">
                <div class="stat-value">38 pts</div>
                <div class="stat-label">Average 16th Place Points (Last 5 Years)</div>
            </div>
            <div class="stat-card block-4-stat">
                <div class="stat-value">2-4</div>
                <div class="stat-label">Typical Point Gap to Block 5</div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="panel">
        <div class="block-navigation">
            <a href="?tab=premier-league&subtab=blocks-3" class="nav-btn block-3-nav">← Block 3</a>
            <a href="?tab=premier-league&subtab=blocks-overview" class="nav-btn">Overview</a>
            <a href="?tab=premier-league&subtab=blocks-5" class="nav-btn block-5-nav">Block 5 →</a>
        </div>
    </div>
</div>

<style>
.block-4-theme h2 {
    color: #FF9800;
}

.block-4-badge {
    background: rgba(255, 152, 0, 0.2);
    color: #FF9800;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    border: 2px solid #FF9800;
}

.block-4-table th {
    background: rgba(255, 152, 0, 0.2);
    border-bottom: 2px solid #FF9800;
}

.danger-row {
    background: rgba(255, 152, 0, 0.05);
}

.block-4-pos {
    color: #FF9800;
}

.block-4-pts {
    background: rgba(255, 152, 0, 0.15);
}

.block-4-card {
    background: rgba(255, 152, 0, 0.05);
    border-left: 3px solid #FF9800;
}

.block-4-card h4 {
    color: #FF9800;
}

.block-4-card ul li:before {
    color: #FF9800;
}

.escape-strategies {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.strategy-card {
    background: rgba(255, 152, 0, 0.1);
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #FF9800;
}

.strategy-card h4 {
    margin-top: 0;
    color: #FF9800;
}

.strategy-card ul {
    margin: 10px 0;
    padding-left: 20px;
}

.critical {
    border-left: 4px solid #F44336;
}

.timeline {
    display: grid;
    gap: 15px;
    margin-top: 15px;
}

.timeline-item {
    background: rgba(255, 152, 0, 0.1);
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #FF9800;
}

.timeline-item h4 {
    margin: 0 0 8px 0;
    color: #FF9800;
}

.timeline-item p {
    margin: 0;
    color: #bbb;
}

.block-4-stat {
    background: rgba(255, 152, 0, 0.1);
    border: 2px solid rgba(255, 152, 0, 0.3);
}

.block-4-stat .stat-value {
    color: #FF9800;
}

.block-3-nav {
    border-color: #2196F3;
    color: #2196F3;
}

.block-5-nav {
    border-color: #F44336;
    color: #F44336;
}
</style>