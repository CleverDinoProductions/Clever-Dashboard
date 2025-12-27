<?php
// Block 3: Positions 9-12 - Safe Mid-Table (Leeds United's target zone)
?>
<div class="block-detail-wrapper">
    <div class="panel">
        <div class="block-title-header block-3-theme">
            <h2>✅ Block 3: Safe Mid-Table</h2>
            <span class="position-badge block-3-badge">Positions 9-12</span>
        </div>
        <p class="block-description">
            The Premier League security zone - teams here have achieved mid-table safety with no relegation concerns. 
            This is the sweet spot for newly promoted sides like Leeds United, providing stability to build for future success.
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
                        ['pos' => 9, 'team' => 'Manchester United', 'pld' => 18, 'w' => 7, 'd' => 5, 'l' => 6, 'gf' => 24, 'ga' => 23, 'gd' => 1, 'pts' => 26],
                        ['pos' => 10, 'team' => 'Nottingham Forest', 'pld' => 18, 'w' => 7, 'd' => 4, 'l' => 7, 'gf' => 23, 'ga' => 25, 'gd' => -2, 'pts' => 25],
                        ['pos' => 11, 'team' => 'Bournemouth', 'pld' => 18, 'w' => 6, 'd' => 6, 'l' => 6, 'gf' => 25, 'ga' => 26, 'gd' => -1, 'pts' => 24],
                        ['pos' => 12, 'team' => 'Leeds United', 'pld' => 18, 'w' => 6, 'd' => 5, 'l' => 7, 'gf' => 22, 'ga' => 25, 'gd' => -3, 'pts' => 23],
                    ];
                    
                    foreach ($placeholderTeams as $team) {
                        $ppg = round($team['pts'] / $team['pld'], 2);
                        $isLeeds = ($team['team'] === 'Leeds United');
                        echo "<tr" . ($isLeeds ? " class='leeds-highlight'" : "") . ">";
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
                    <li>No relegation pressure</li>
                    <li>Build for future seasons</li>
                    <li>Focus on tactical development</li>
                    <li>Youth integration opportunities</li>
                </ul>
            </div>
            
            <div class="char-card block-3-card">
                <h4>📊 Key Metrics</h4>
                <ul>
                    <li>Points Per Game: 1.2-1.5</li>
                    <li>Win Rate: 35-45%</li>
                    <li>Goals Per Game: 1.2-1.5</li>
                    <li>Clean Sheets: 25-35%</li>
                </ul>
            </div>
            
            <div class="char-card block-3-card">
                <h4>🛡️ Tactical Approach</h4>
                <ul>
                    <li>Defensive stability priority</li>
                    <li>Home fortress mentality</li>
                    <li>Organized defensive structure</li>
                    <li>Counter-attacking efficiency</li>
                </ul>
            </div>
            
            <div class="char-card block-3-card">
                <h4>💰 Investment Level</h4>
                <ul>
                    <li>Strategic moderate spending</li>
                    <li>Long-term squad building</li>
                    <li>Focus on value signings</li>
                    <li>Sustainable financial model</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Leeds United Context -->
    <div class="panel">
        <h3>💛 Leeds United in Block 3: Curse-Breaking Success</h3>
        <div class="leeds-context">
            <div class="context-card">
                <h4>🏆 Historical Achievement</h4>
                <p>Leeds United's position in Block 3 represents unprecedented success for a newly promoted Championship winner. 
                Breaking the 18-year promotion curse, Leeds have established Premier League permanence through:</p>
                <ul>
                    <li>Daniel Farke's obsessive tactical preparation</li>
                    <li>Championship-winning mentality translated to top flight</li>
                    <li>Elland Road fortress strategy (strong home form)</li>
                    <li>Defensive organization prioritized over attacking flair</li>
                </ul>
            </div>
            
            <div class="context-card">
                <h4>📈 The Safety Buffer</h4>
                <p><strong>Why Block 3 is Perfect:</strong></p>
                <ul>
                    <li>7-10 point cushion above relegation zone</li>
                    <li>No psychological pressure of survival fight</li>
                    <li>Can rotate squad without relegation fears</li>
                    <li>Build foundations for future Block 2 push</li>
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
                <p>Teams escaping Block 4 danger zone typically show:</p>
                <ul>
                    <li>Improved defensive record</li>
                    <li>Home wins becoming consistent</li>
                    <li>Reaching 30+ point safety threshold</li>
                    <li>Managerial stability established</li>
                </ul>
            </div>
            
            <div class="movement-card down-movement">
                <h4>⬇️ Teams Dropping to Block 4</h4>
                <p>Loss of mid-table security signals:</p>
                <ul>
                    <li>Extended winless runs (6+ games)</li>
                    <li>Defensive fragility emerging</li>
                    <li>Home form collapse</li>
                    <li>Squad morale deteriorating</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Historical Context -->
    <div class="panel">
        <h3>📚 Historical Block 3 Analysis</h3>
        <div class="history-stats">
            <div class="stat-card block-3-stat">
                <div class="stat-value">50 pts</div>
                <div class="stat-label">Average 9th Place Points (Last 5 Years)</div>
            </div>
            <div class="stat-card block-3-stat">
                <div class="stat-value">45 pts</div>
                <div class="stat-label">Average 12th Place Points (Last 5 Years)</div>
            </div>
            <div class="stat-card block-3-stat">
                <div class="stat-value">8-12</div>
                <div class="stat-label">Typical Point Gap Above Relegation</div>
            </div>
        </div>
        
        <div class="promoted-teams-note">
            <h4>📊 Promoted Teams in Block 3</h4>
            <p>Historically rare achievement - most promoted teams finish in Blocks 4-5. Leeds United's Block 3 
            position validates the systematic preparation and curse-breaking framework predictions.</p>
        </div>
    </div>

    <!-- Navigation -->
    <div class="panel">
        <div class="block-navigation">
            <a href="?tab=premier-league&subtab=blocks-2" class="nav-btn block-2-nav">← Block 2</a>
            <a href="?tab=premier-league&subtab=blocks-overview" class="nav-btn">Overview</a>
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
    color: #2196F3;
}

.block-3-pts {
    background: rgba(33, 150, 243, 0.1);
}

.leeds-highlight {
    background: rgba(255, 205, 0, 0.1);
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

.leeds-context {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.context-card {
    background: rgba(255, 205, 0, 0.1);
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #FFCD00;
}

.context-card h4 {
    margin-top: 0;
    color: #FFCD00;
}

.context-card ul {
    margin: 10px 0;
    padding-left: 20px;
}

.block-3-stat {
    background: rgba(33, 150, 243, 0.1);
    border: 2px solid rgba(33, 150, 243, 0.3);
}

.block-3-stat .stat-value {
    color: #2196F3;
}

.promoted-teams-note {
    background: rgba(33, 150, 243, 0.1);
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
    border-left: 3px solid #2196F3;
}

.promoted-teams-note h4 {
    margin-top: 0;
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