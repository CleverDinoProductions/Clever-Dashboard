<?php
/**
 * Relegation Battle - Bottom 4 Teams
 */
require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/table-view.php';
$tableView = football_stats_get_table_view($db, 'ELC', 'league_table_ELC', $currentMainTab ?? '2025-2026');
$teams = array_values(array_filter($tableView['standings'], function ($team) {
	return $team['position'] >= 21 && $team['position'] <= 24;
}));
$currentMatchday = $tableView['active_matchweek'] ?? (!empty($tableView['standings']) ? max(array_map('intval', array_column($tableView['standings'], 'played'))) : 1);
$last_update = $tableView['last_update'];
?>
<div class="panel" style="border-left: 4px solid #F44336;">
	<h2>🚨 Relegation Battle (21st-24th)</h2>
	<?php football_stats_render_table_view_controls($tableView, $currentMainTab ?? '2025-2026', $currentLeague ?? 'championship', $currentSubTab ?? 'relegation'); ?>
	<p style="margin-top: 15px; font-size: 1.1em; color: #ddd;">
		The bottom four teams face relegation to League One. Every point is crucial for survival.
	</p>
	<p style="margin-top: 10px; color: #888; font-size: 0.9em;">
		📅 Current Matchday: <strong><?php echo $currentMatchday; ?></strong> of 46
	</p>
	<table class="table table-striped">
		<thead>
			<tr>
				<th>Pos</th>
				<th>Team</th>
				<th>Pld</th>
				<th>W</th>
				<th>D</th>
				<th>L</th>
				<th>GD</th>
				<th>Pts</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($teams as $team): ?>
			<tr>
				<td><?php echo $team['position']; ?></td>
				<td><?php echo htmlspecialchars($team['team_name']); ?></td>
				<td><?php echo $team['played']; ?></td>
				<td><?php echo $team['won']; ?></td>
				<td><?php echo $team['drawn']; ?></td>
				<td><?php echo $team['lost']; ?></td>
				<td><?php echo $team['gd']; ?></td>
				<td><?php echo $team['points']; ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
