<?php
// Fetch overall World Cup standings
$stmt = $db->query("SELECT * FROM wc_standings ORDER BY points DESC, gd DESC, gf DESC LIMIT 32");
$standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$last_update = $db->query("SELECT MAX(updated_at) as ts FROM wc_standings")->fetch();
?>

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
                if ($rank <= 16) {
                    $row_style = 'background: rgba(67, 181, 129, 0.1); border-left: 4px solid #43b581;';
                }
            ?>
            <tr style="<?= $row_style ?>">
                <td><strong><?= $rank++ ?></strong></td>
                <td><?= htmlspecialchars($team['team_name']) ?></td>
                <td style="font-size: 11px; color: #888;"><?= htmlspecialchars($team['stage']) ?></td>
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
            <span style="color: #43b581;">■</span> Top 16 qualify for knockout stage
        </div>
    <?php endif; ?>
</div>
