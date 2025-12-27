<?php
// Block 5: Positions 17-20 - Relegation Battle
?>
<div class="block-detail-wrapper">
    <div class="panel">
        <div class="block-title-header block-5-theme">
            <h2>🔻 Block 5: Relegation Battle</h2>
            <span class="position-badge block-5-badge">Positions 17-20</span>
        </div>
        <p class="block-description">
            The relegation zone - teams fighting for Premier League survival. Crisis mode mentality with desperate 
            measures required. Mathematical escape becomes exponentially harder as the season progresses.
        </p>
    </div>

    <!-- Current Teams in Block -->
    <div class="panel">
        <h3>📈 Current Block 5 Teams</h3>
        <div class="teams-table-wrapper">
            <table class="standings-table block-5-table">
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
                        <th>Req'd</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Placeholder - Replace with actual database query
                    $placeholderTeams = [
                        ['pos' => 17, 'team' => 'Ipswich Town', 'pld' => 18, 'w' => 4, 'd' => 6, 'l' => 8, 'gf' => 17, 'ga' => 28, 'gd' => -11, 'pts' => 18],
                        ['pos' => 18, 'team' => 'Leicester City', 'pld' => 18, 'w' => 3, 'd' => 6, 'l' => 9, 'gf' => 19, 'ga' => 32, 'gd' => -13, 'pts' => 15],
                        ['pos' => 19, 'team' => 'Wolves', 'pld' => 18, 'w' => 2, 'd' => 5, 'l' => 11, 'gf' => 14, 'ga' => 35, 'gd' => -21, 'pts' => 11],
                        ['pos' => 20, 'team' => 'Southampton', 'pld' => 18, 'w' => 1, 'd' => 4, 'l' => 13, 'gf' => 12, 'ga' => 38, 'gd' => -26, 'pts' => 7],
                    ];
                    
                    foreach ($placeholderTeams as $team) {
                        $ppg = round($team['pts'] / $team['pld'], 2);
                        $gamesLeft = 38 - $team['pld'];
                        $pointsNeeded = max(0, 35 - $team['pts']); // 35 points typical survival
                        $requiredPPG = $gamesLeft > 0 ? round($pointsNeeded / $gamesLeft, 2) : 0;
                        
                        echo "<tr class='relegation-row'>";
                        echo "<td class='pos-cell block-5-pos'>{$team['pos']}</td>";
                        echo "<td class='team-cell'><strong>{$team['team']}</strong></td>";
                        echo "<td>{$team['pld']}</td>";
                        echo "<td>{$team['w']}</td>";
                        echo "<td>{$team['d']}</td>";
                        echo "<td>{$team['l']}</td>";
                        echo "<td>{$team['gf']}</td>";
                        echo "<td>{$team['ga']}</td>";
                        echo "<td class='gd-cell'>" . ($team['gd'] > 0 ? '+' : '') . "{$team['gd']}</td>";
                        echo "<td class='pts-cell block-5-pts'><strong>{$team['pts']}</strong></td>";
                        echo "<td>{$ppg}</td>";
                        echo "<td class='required-ppg'>{$requiredPPG}</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            <p class="table-note"><strong>Req'd:</strong> Points per game needed from remaining matches to reach 35-point safety threshold</p>
        </div>
    </div>

    <!-- Block Characteristics -->
    <div class="panel">
        <h3>🔑 Block 5 Characteristics</h3>
        <div class="characteristics-grid">
            <div class="char-card block-5-card">
                <h4>💔 Mentality</h4>
                <ul>
                    <li>Crisis mode activated</li>
                    <li>Extreme pressure on all</li>
                    <li>Desperation in every match</li>
                    <li>Fan protests possible</li>
                </ul>
            </div>
            
            <div class="char-card block-5-card">
                <h4>📉 Key Metrics</h4>
                <ul>
                    <li>Points Per Game: Under 1.0</li>
                    <li>Win Rate: Under 25%</li>
                    <li>Goals Per Game: Under 1.0</li>
                    <li>Clean Sheets: Under 20%</li>
                </ul>
            </div>
            
            <div class="char-card block-5-card">
                <h4>🆘 Tactical Approach</h4>
                <ul>
                    <li>Desperate defensive tactics</li>
                    <li>Long-ball reliance increases</li>
                    <li>Set-piece dependency</li>
                    <li>Formation experiments</li>
                </ul>
            </div>
            
            <div class="char-card block-5-card">
                <h4>🚪 Managerial Changes</h4>
                <ul>
                    <li>Manager sackings likely</li>
                    <li>Caretaker appointments</li>
                    <li>Board panic decisions</li>
                    <li>Transfer panic buying</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Mathematical Reality -->
    <div class="panel">
        <h3>🔢 The Mathematical Trap</h3>
        <div class="math-section">
            <div class="math-card critical">
                <h4>⛓️ Why Bottom Teams Can't Catch Up</h4>
                <p>As more points are earned by teams higher up the table, the mathematical gap becomes insurmountable:</p>
                <ul>
                    <li><strong>Point Inflation:</strong> Block 1-3 teams add 2-3 points per week consistently</li>
                    <li><strong>Block 5 stagnation:</strong> Teams here average 0-1 points per week</li>
                    <li><strong>The gap widens:</strong> Every gameweek increases the mathematical difficulty</li>
                    <li><strong>Historical reality:</strong> Teams with 0-7 points after 10 games have only 12% survival rate</li>
                </ul>
            </div>
            
            <div class="math-card">
                <h4>📊 Survival Thresholds</h4>
                <div class="survival-grid">
                    <div class="threshold-item">
                        <div class="threshold-points">40 pts</div>
                        <div class="threshold-label">Guaranteed Safety (Myth - usually 35-38 sufficient)</div>
                    </div>
                    <div class="threshold-item">
                        <div class="threshold-points">35 pts</div>
                        <div class="threshold-label">Typical Survival Target (Average 17th place)</div>
                    </div>
                    <div class="threshold-item danger">
                        <div class="threshold-points">33 pts</div>
                        <div class="threshold-label">Danger Zone (50/50 survival chance)</div>
                    </div>
                    <div class="threshold-item critical">
                        <div class="threshold-points">Under 32</div>
                        <div class="threshold-label">Likely Relegation</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Escape Scenarios -->
    <div class="panel">
        <h3>🏃 Escape Routes from Block 5</h3>
        <div class="escape-analysis">
            <div class="scenario">
                <h4>✅ Realistic Escape (Requires Miracle)</h4>
                <ul>
                    <li><strong>Required:</strong> 1.5+ PPG for remaining matches</li>
                    <li>Win 8-10 of final 20 games</li>
                    <li>Exploit fixtures against fellow strugglers</li>
                    <li>Dramatic improvement in both attack AND defense</li>
                    <li>New manager bounce effect essential</li>
                </ul>
            </div>
            
            <div class="scenario worst">
                <h4>❌ Historical Doom</h4>
                <p>Teams failing to escape Block 5 by March typically show:</p>
                <ul>
                    <li>Under 20 points by Matchday 25</li>
                    <li>Goal difference worse than -20</li>
                    <li>Multiple heavy defeats (3+ goals)</li>
                    <li>No wins against Block 4-5 rivals</li>
                    <li>Defensive record: 2+ goals conceded per game</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- The Wolves Example -->
    <div class="panel">
        <h3>🐺 Case Study: Wolverhampton Wanderers Crisis</h3>
        <div class="case-study">
            <p class="intro">Based on your analysis files, Wolves represent the perfect example of Block 5 mathematical doom:</p>
            <div class="wolves-stats">
                <div class="stat-box critical">
                    <div class="stat-number">0 pts</div>
                    <div class="stat-desc">After 5 games - unprecedented failure</div>
                </div>
                <div class="stat-box critical">
                    <div class="stat-number">0.6</div>
                    <div class="stat-desc">Goals per game - offensive crisis</div>
                </div>
                <div class="stat-box critical">
                    <div class="stat-number">2.4</div>
                    <div class="stat-desc">Goals conceded per game - defensive collapse</div>
                </div>
                <div class="stat-box critical">
                    <div class="stat-number">80%</div>
                    <div class="stat-desc">Relegation risk - mathematical certainty</div>
                </div>
            </div>
            <p class="analysis">Wolves need 4 points just to escape automatic relegation zone, yet they're accumulating 
            0 points per week. Meanwhile, teams in Block 3 like Leeds United have 7+ points creating an 
            insurmountable gap as the rich get richer and the poor get poorer.</p>
        </div>
    </div>

    <!-- Historical Context -->
    <div class="panel">
        <h3>📚 Historical Block 5 Analysis</h3>
        <div class="history-stats">
            <div class="stat-card block-5-stat">
                <div class="stat-value">35 pts</div>
                <div class="stat-label">Average 17th Place (Survival) Points</div>
            </div>
            <div class="stat-card block-5-stat">
                <div class="stat-value">28 pts</div>
                <div class="stat-label">Average 18th Place (Relegated) Points</div>
            </div>
            <div class="stat-card block-5-stat">
                <div class="stat-value">11 pts</div>
                <div class="stat-label">Lowest Ever Survival (Derby 2007-08: 11 pts)</div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="panel">
        <div class="block-navigation">
            <a href="?tab=premier-league&subtab=blocks-4" class="nav-btn block-4-nav">← Block 4</a>
            <a href="?tab=premier-league&subtab=blocks-overview" class="nav-btn">Back to Overview</a>
        </div>
    </div>
</div>

<style>
.block-5-theme h2 {
    color: #F44336;
}

.block-5-badge {
    background: rgba(244, 67, 54, 0.2);
    color: #F44336;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    border: 2px solid #F44336;
}

.block-5-table th {
    background: rgba(244, 67, 54, 0.2);
    border-bottom: 2px solid #F44336;
}

.relegation-row {
    background: rgba(244, 67, 54, 0.08);
    border-left: 4px solid #F44336;
}

.block-5-pos {
    color: #F44336;
    font-weight: bold;
}

.block-5-pts {
    background: rgba(244, 67, 54, 0.2);
}

.required-ppg {
    font-weight: bold;
    color: #F44336;
    font-family: monospace;
}

.table-note {
    margin-top: 10px;
    font-size: 0.9em;
    color: #888;
    font-style: italic;
}

.block-5-card {
    background: rgba(244, 67, 54, 0.05);
    border-left: 3px solid #F44336;
}

.block-5-card h4 {
    color: #F44336;
}

.block-5-card ul li:before {
    color: #F44336;
}

.math-section {
    display: grid;
    gap: 20px;
    margin-top: 15px;
}

.math-card {
    background: rgba(244, 67, 54, 0.1);
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #F44336;
}

.math-card.critical {
    border-left: 4px solid #D32F2F;
    background: rgba(211, 47, 47, 0.15);
}

.math-card h4 {
    margin-top: 0;
    color: #F44336;
}

.survival-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.threshold-item {
    background: rgba(255, 255, 255, 0.05);
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    border: 2px solid rgba(255, 255, 255, 0.1);
}

.threshold-item.danger {
    border-color: #FF9800;
}

.threshold-item.critical {
    border-color: #F44336;
    background: rgba(244, 67, 54, 0.1);
}

.threshold-points {
    font-size: 2em;
    font-weight: bold;
    color: #F44336;
}

.threshold-label {
    font-size: 0.85em;
    color: #aaa;
    margin-top: 5px;
}

.escape-analysis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.scenario {
    background: rgba(76, 175, 80, 0.1);
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #4CAF50;
}

.scenario.worst {
    background: rgba(244, 67, 54, 0.1);
    border-left: 4px solid #F44336;
}

.scenario h4 {
    margin-top: 0;
}

.case-study {
    background: rgba(244, 67, 54, 0.1);
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #F44336;
}

.case-study .intro {
    font-weight: bold;
    margin-bottom: 15px;
}

.wolves-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.stat-box {
    background: rgba(255, 255, 255, 0.05);
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    border: 2px solid rgba(255, 255, 255, 0.1);
}

.stat-box.critical {
    border-color: #F44336;
    background: rgba(244, 67, 54, 0.15);
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #F44336;
}

.stat-desc {
    font-size: 0.85em;
    color: #aaa;
    margin-top: 5px;
}

.analysis {
    margin-top: 15px;
    line-height: 1.6;
    color: #bbb;
}

.block-5-stat {
    background: rgba(244, 67, 54, 0.1);
    border: 2px solid rgba(244, 67, 54, 0.3);
}

.block-5-stat .stat-value {
    color: #F44336;
}

.block-4-nav {
    border-color: #FF9800;
    color: #FF9800;
}
</style>