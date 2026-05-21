<?php
require_once __DIR__ . '/../../../includes/table-view.php';

$tableView = football_stats_get_table_view($db, 'L1', 'league_table_L1', $currentMainTab ?? '2025-2026');
$standings = $tableView['standings'];
$last_update = $tableView['last_update'];

// Helper: Get next N matches for a team
function get_next_matches($team_name, $all_matches, $current_matchweek, $n = 6) {
    $matches = array_filter($all_matches, function($m) use ($team_name, $current_matchweek) {
        return ($m['home_team'] === $team_name || $m['away_team'] === $team_name) && $m['matchweek'] >= $current_matchweek;
    });
    usort($matches, function($a, $b) { return $a['matchweek'] <=> $b['matchweek']; });
    return array_slice($matches, 0, $n);
}

// Fetch all matches for the season
$all_matches = $tableView['all_matches'] ?? [];
$current_matchweek = $tableView['active_matchweek'] ?? 1;

$whatif_counts = [6, 5, 4, 3, 2, 1];
?>
<style>
.whatif-table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
.whatif-table th, .whatif-table td { border: 1px solid #444; padding: 6px 10px; text-align: center; }
.whatif-table th { background: #222; color: #fff; }
.whatif-table tr:nth-child(even) { background: #333; }
</style>
<div class="panel">
    <h2>League One What-If Scenarios (Next 6, 5, 4, 3, 2, 1 Matches)</h2>
    <p class="update-info">
        <?= htmlspecialchars($tableView['updated_label'], ENT_QUOTES, 'UTF-8') ?>:
        <?= $last_update['ts'] ? date('Y-m-d H:i:s', $last_update['ts'] / 1000) : 'No data available yet' ?>
    </p>
    <?php foreach ($whatif_counts as $n): ?>
    <h3>What If: Next <?= $n ?> Matches</h3>
    <table class="whatif-table">
        <tr>
            <th>Pos</th>
            <th>Team</th>
            <th>Current Pts</th>
            <th>Played</th>
            <th>Max Pts (All Win)</th>
            <th>Max Pts (All Draw)</th>
            <th>Next <?= $n ?> Opponents</th>
        </tr>
        <?php foreach ($standings as $team): ?>
        <?php
            $next_matches = get_next_matches($team['team_name'], $all_matches, $current_matchweek, $n);
            $max_pts_win = $team['points'] + ($n * 3);
            $max_pts_draw = $team['points'] + ($n * 1);
        ?>
        <tr>
            <td><?= $team['position'] ?></td>
            <td><?= htmlspecialchars($team['team_name']) ?></td>
            <td><?= $team['points'] ?></td>
            <td><?= $team['played'] ?></td>
            <td><?= $max_pts_win ?></td>
            <td><?= $max_pts_draw ?></td>
            <td>
                <?php if ($next_matches): ?>
                    <?php foreach ($next_matches as $m): ?>
                        <span><?= htmlspecialchars($m['home_team']) ?> vs <?= htmlspecialchars($m['away_team']) ?> (MW<?= $m['matchweek'] ?>)</span><br>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span>No matches left</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endforeach; ?>
</div>
