<?php
// Fetch overall World Cup standings
$stmt = $db->query("SELECT * FROM wc_standings ORDER BY points DESC, gd DESC, gf DESC LIMIT 48");
$standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$last_update = $db->query("SELECT MAX(updated_at) as ts FROM wc_standings")->fetch();
?>

<style>
    /* 1. Target the header cells specifically */
th {
    position: sticky;
    top: 0;
    z-index: 10; /* Keeps the header above the scrolling body rows */
    background-color: #222; /* Use your team/site brand color here */
    color: white;
    white-space: nowrap; /* Prevents long titles from breaking the layout */
    border-bottom: 2px solid #444;
}

/* 2. Important fix for tables */
table {
    border-collapse: collapse; /* Required for sticky borders to show up correctly */
}
</style>

<div class="panel">
    <h2>📊 World Cup 2026 - Overall Rankings</h2>
    <?php if ($last_update['ts']): ?>
        <p class="update-info">
            Last updated: <?= date('Y-m-d H:i:s', $last_update['ts'] / 1000) ?>
        </p>
    <?php endif; ?>
    
    <?php if (empty($standings)): ?>
        <p style="color: #888; padding: 20px; text-align: center;">
            📈 Overall standings will be available during the tournament<br>
            <span style="font-size: 12px;">48 teams competing | June 11 - July 19, 2026</span>
        </p>
    <?php else: ?>
        <table>
            <tr>
                <th>Rank</th>
                <th>Team</th>
                <th>Group</th>
                <th>Pot</th>
                <th>P</th>
                <th>W</th>
                <th>D</th>
                <th>L</th>
                <th>GF</th>
                <th>GA</th>
                <th>GD</th>
                <th>Pts</th>
            </tr>
            <?php 
            $rank = 1;
            foreach ($standings as $team): 
                $row_style = '';
                // World Cup Champion
                if ($rank == 1) {
                    $row_style = 'background: rgba(255, 215, 0, 0.1); border-left: 4px solid #ffd700;';
                }
                // Runners-up
                elseif ($rank == 2) {
                    $row_style = 'background: rgba(192, 192, 192, 0.1); border-left: 4px solid #c0c0c0;';
                }
                // Third place
                elseif ($rank == 3) {
                    $row_style = 'background: rgba(205, 127, 50, 0.1); border-left: 4px solid #cd7f32;';
                }
                // Semi finalists
                elseif ($rank <= 4) {
                    $row_style = 'background: rgba(173, 216, 230, 0.1); border-left: 4px solid #87ceeb;';
                }
                // Quarter finalists
                elseif ($rank <= 8) {
                    $row_style = 'background: rgba(144, 238, 144, 0.1); border-left: 4px solid #90ee90;';
                }
                // Last 16
                elseif ($rank <= 16) {
                    $row_style = 'background: rgba(255, 182, 193, 0.1); border-left: 4px solid #ffb6c1;';
                }
                // Last 32
                elseif ($rank <= 32) {
                    $row_style = 'background: rgba(255, 228, 181, 0.1); border-left: 4px solid #ffe4b5;';
                }
                // Group stage exit
                else {
                    $row_style = 'background: rgba(211, 211, 211, 0.1); border-left: 4px solid #d3d3d3;';
                }
                
                // if team is England, add a special highlight
                if (strtolower($team['team_name']) == 'england') {
                    $row_style = 'background: rgba(255, 255, 255, 0.2); border-left: 4px solid #1e90ff;';
                }
            ?>
            <tr style="<?= $row_style ?>">
                <td><strong><?= $rank++ ?></strong></td>
                <td><?= htmlspecialchars($team['team_name']) ?></td>
                <td style="font-size: 11px; color: #888;"><?= htmlspecialchars($team['stage']) ?></td>
                <td style="font-size: 11px; color: #888;"><?= htmlspecialchars($team['rank']) ?></td>
                <td><?= $team['played'] ?></td>
                <td><?= $team['won'] ?></td>
                <td><?= $team['drawn'] ?></td>
                <td><?= $team['lost'] ?></td>
                <td><?= $team['gf'] ?></td>
                <td><?= $team['ga'] ?></td>
                <td style="color: <?= $team['gd'] > 0 ? '#43b581' : ($team['gd'] < 0 ? '#f04747' : '#dcddde') ?>;">
                    <?= $team['gd'] > 0 ? '+' . $team['gd'] : $team['gd'] ?>
                </td>
                <td><strong><?= $team['points'] ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div style="margin-top: 20px; font-size: 12px;">
            <span style="color: #ffd700;">■</span> World Cup Champion<br>
            <span style="color: #c0c0c0;">■</span> Runners-up<br>
            <span style="color: #cd7f32;">■</span> Third Place<br>
            <span style="color: #87ceeb;">■</span> Semi Finalists<br>
            <span style="color: #90ee90;">■</span> Quarter Finalists<br>
            <span style="color: #ffb6c1;">■</span> Last 16<br>
            <span style="color: #ffe4b5;">■</span> Last 32<br>
            <span style="color: #d3d3d3;">■</span> Group Stage Exit<br>
            <span style="color: #43b581;">■</span> Top 32 qualify for knockout stage
        </div>
    <?php endif; ?>
</div>
