<?php
$blockNumber = 2;
$blockInfo = ['title' => 'European Zone', 'emoji' => '🌟', 'color' => '#4CAF50', 'positions' => '5-8', 'description' => 'Europa & Conference League'];
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../football-stats.sqlite3');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $matchdayQuery = $db->query("SELECT MAX(played) as current_matchday FROM premier_league_table");
    $currentMatchday = $matchdayQuery->fetch(PDO::FETCH_ASSOC)['current_matchday'] ?? 1;
    $teamsQuery = $db->query("SELECT * FROM premier_league_table WHERE position BETWEEN 5 AND 8 ORDER BY position");
    $teams = $teamsQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $db = null; $teams = []; $currentMatchday = 1;
}
?>
<div class="block-detail-page">
    <div class="panel" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
        <div class="block-header-section">
            <h2 style="color: <?php echo $blockInfo['color']; ?>;"><?php echo $blockInfo['emoji']; ?> Block <?php echo $blockNumber; ?>: <?php echo $blockInfo['title']; ?></h2>
            <span class="position-badge" style="background: rgba(76, 175, 80, 0.2); color: <?php echo $blockInfo['color']; ?>; border: 1px solid <?php echo $blockInfo['color']; ?>;">Positions <?php echo $blockInfo['positions']; ?></span>
        </div>
        <p style="margin-top: 15px; font-size: 1.1em; color: #ddd;"><?php echo $blockInfo['description']; ?> - Teams competing for European football.</p>
        <p style="margin-top: 10px; color: #888; font-size: 0.9em;">📅 Matchday: <strong><?php echo $currentMatchday; ?></strong> of 38</p>
    </div>
    <?php if (!empty($teams)): ?>
    <div class="panel">
        <h3>📊 Live Block 2 Standings</h3>
        <div class="table-wrapper">
            <table class="block-table">
                <thead><tr style="background: rgba(76, 175, 80, 0.15); border-bottom: 2px solid <?php echo $blockInfo['color']; ?>;"><th>Pos</th><th>Team</th><th>Pld</th><th>W</th><th>D</th><th>L</th><th>GF</th><th>GA</th><th>GD</th><th>Pts</th><th>PPG</th></tr></thead>
                <tbody>
                    <?php foreach ($teams as $team):
                        $ppg = $team['played'] > 0 ? round($team['points'] / $team['played'], 2) : 0;
                    ?>
                    <tr>
                        <td class="pos-cell" style="color: <?php echo $blockInfo['color']; ?>; font-weight: bold;"><?php echo $team['position']; ?></td>
                        <td class="team-cell"><strong><?php echo htmlspecialchars($team['team_name']); ?></strong></td>
                        <td><?php echo $team['played']; ?></td>
                        <td style="color: #4CAF50;"><?php echo $team['won']; ?></td>
                        <td style="color: #888;"><?php echo $team['drawn']; ?></td>
                        <td style="color: #F44336;"><?php echo $team['lost']; ?></td>
                        <td><?php echo $team['gf']; ?></td>
                        <td><?php echo $team['ga']; ?></td>
                        <td class="<?php echo $team['gd'] > 0 ? 'positive' : ($team['gd'] < 0 ? 'negative' : ''); ?>"><?php echo ($team['gd'] > 0 ? '+' : '') . $team['gd']; ?></td>
                        <td class="pts-cell" style="background: rgba(76, 175, 80, 0.2); font-weight: bold;"><?php echo $team['points']; ?></td>
                        <td style="color: <?php echo $blockInfo['color']; ?>; font-weight: bold;"><?php echo $ppg; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel">
        <h3>🎯 Season Projections</h3>
        <div class="projections-grid">
            <?php foreach ($teams as $team):
                $ppg = $team['played'] > 0 ? round($team['points'] / $team['played'], 2) : 0;
                $projectedPoints = round($ppg * 38, 0);
                $halfwayTarget = round($ppg * 19, 0);
                $remainingGames = 38 - $team['played'];
            ?>
            <div class="projection-card" style="border-left: 3px solid <?php echo $blockInfo['color']; ?>;">
                <h4><?php echo htmlspecialchars($team['team_name']); ?></h4>
                <div class="projection-stats">
                    <div class="proj-row"><span class="proj-label">Current PPG:</span><span class="proj-value" style="color: <?php echo $blockInfo['color']; ?>;"><?php echo $ppg; ?></span></div>
                    <div class="proj-row"><span class="proj-label">Current Points:</span><span class="proj-value"><strong><?php echo $team['points']; ?></strong></span></div>
                    <div class="proj-row" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px; margin-top: 10px;"><span class="proj-label">🎯 Halfway (MD19):</span><span class="proj-value"><?php echo $halfwayTarget; ?> pts</span></div>
                    <div class="proj-row"><span class="proj-label">🏁 Season End (MD38):</span><span class="proj-value" style="font-size: 1.2em; color: <?php echo $blockInfo['color']; ?>;"><strong><?php echo $projectedPoints; ?> pts</strong></span></div>
                    <div class="proj-row"><span class="proj-label">Europa target:</span><span class="proj-value">~60 pts</span></div>
                    <div class="proj-row"><span class="proj-label">Games remaining:</span><span class="proj-value"><?php echo $remainingGames; ?></span></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="panel">
        <h3>🎯 Block 2 Targets</h3>
        <div class="benchmarks-grid">
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;"><h4>🌟 Europa League (5th)</h4><ul><li><strong>Target PPG:</strong> 1.7-1.9</li><li><strong>Halfway (MD19):</strong> 32-36 pts</li><li><strong>Season End (MD38):</strong> 62-68 pts</li></ul></div>
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;"><h4>⚽ Conference League (6th-7th)</h4><ul><li><strong>Target PPG:</strong> 1.5-1.7</li><li><strong>Halfway (MD19):</strong> 28-32 pts</li><li><strong>Season End (MD38):</strong> 55-62 pts</li></ul></div>
            <div class="benchmark-card" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;"><h4>🔸 8th Place</h4><ul><li><strong>Target PPG:</strong> 1.4-1.6</li><li><strong>Halfway (MD19):</strong> 26-30 pts</li><li><strong>Season End (MD38):</strong> 52-58 pts</li></ul></div>
        </div>
    </div>
    <div class="panel"><div class="block-navigation"><a href="?tab=premier-league&subtab=blocks-1" class="nav-btn">← Block 1</a><a href="?tab=premier-league&subtab=blocks-dynamic" class="nav-btn" style="background: rgba(76, 175, 80, 0.1); color: <?php echo $blockInfo['color']; ?>;">🔥 Live</a><a href="?tab=premier-league&subtab=blocks-3" class="nav-btn">Block 3 →</a></div></div>
</div>
<style>.block-detail-page{max-width:1400px;margin:0 auto}.block-header-section{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px}.block-header-section h2{margin:0;font-size:2em}.position-badge{padding:8px 15px;border-radius:6px;font-size:0.9em;font-weight:bold}.table-wrapper{overflow-x:auto;margin-top:15px}.block-table{width:100%;border-collapse:collapse}.block-table th,.block-table td{padding:12px 8px;text-align:center;border-bottom:1px solid rgba(255,255,255,0.1)}.block-table th{font-weight:600;font-size:0.9em}.team-cell{text-align:left!important}.pos-cell,.pts-cell{font-size:1.1em}.positive{color:#4CAF50}.negative{color:#F44336}.projections-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-top:20px}.projection-card{background:rgba(255,255,255,0.03);padding:20px;border-radius:8px;border-left:3px solid}.projection-card h4{margin:0 0 15px 0;color:#fff}.projection-stats{display:flex;flex-direction:column;gap:8px}.proj-row{display:flex;justify-content:space-between;padding:5px 0}.proj-label{color:#888;font-size:0.9em}.proj-value{color:#fff;font-weight:500}.benchmarks-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:20px}.benchmark-card{background:rgba(255,255,255,0.03);padding:20px;border-radius:8px;border-left:4px solid}.benchmark-card h4{margin:0 0 15px 0;color:#fff}.benchmark-card ul{list-style:none;padding:0;margin:0}.benchmark-card ul li{padding:8px 0;color:#bbb;font-size:0.95em;border-bottom:1px solid rgba(255,255,255,0.05)}.benchmark-card ul li:last-child{border-bottom:none}.block-navigation{display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap}.nav-btn{flex:1;min-width:150px;padding:12px 20px;background:rgba(255,255,255,0.05);color:#fff;text-decoration:none;text-align:center;border-radius:6px;border:1px solid rgba(255,255,255,0.1);transition:all 0.2s;font-weight:500}.nav-btn:hover{background:rgba(255,255,255,0.1);transform:translateY(-2px)}</style>