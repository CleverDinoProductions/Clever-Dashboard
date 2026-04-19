<?php
/**
 * Block 1: Title Contenders (Positions 1-2)
 * Automatic Promotion Zone
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$blockNumber = 1;
$blockInfo = [
	'title' => 'Title Contenders',
	'emoji' => '🏆',
	'color' => '#FFD700',
	'positions' => '1-2',
	'description' => 'Automatic Promotion Zone'
];

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/table-view.php';

$tableView = football_stats_get_table_view($db, 'ELC', 'league_table_ELC', $currentMainTab ?? '2025-2026');
$teams = array_values(array_filter($tableView['standings'], function ($team) {
	return $team['position'] >= 1 && $team['position'] <= 2;
}));
$currentMatchday = $tableView['active_matchweek'] ?? (!empty($tableView['standings']) ? max(array_map('intval', array_column($tableView['standings'], 'played'))) : 1);
$last_update = $tableView['last_update'];
$tableViewNavParams = football_stats_get_current_table_view_params();
?>

<div class="block-detail-page">
	<!-- Block Header -->
	<div class="panel" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
		<div class="block-header-section">
			<h2 style="color: <?php echo $blockInfo['color']; ?>;">
				<?php echo $blockInfo['emoji']; ?> Block <?php echo $blockNumber; ?>: <?php echo $blockInfo['title']; ?>
			</h2>
			<span class="position-badge" style="background: rgba(255, 215, 0, 0.2); color: <?php echo $blockInfo['color']; ?>; border: 1px solid <?php echo $blockInfo['color']; ?>;">
				Positions <?php echo $blockInfo['positions']; ?>
			</span>
		</div>
		<?php football_stats_render_table_view_controls($tableView, $currentMainTab ?? '2025-2026', $currentLeague ?? 'championship', $currentSubTab ?? 'blocks-1'); ?>
		<p style="margin-top: 15px; font-size: 1.1em; color: #ddd;">
			<?php echo $blockInfo['description']; ?> - The top two teams earn automatic promotion to the Premier League.
		</p>
		<p style="margin-top: 10px; color: #888; font-size: 0.9em;">
			📅 Current Matchday: <strong><?php echo $currentMatchday; ?></strong> of 46 • 
			<?php if ($currentMatchday < 23): ?>
				First half of season
			<?php elseif ($currentMatchday == 23): ?>
				🎯 Halfway point!
			<?php else: ?>
				Second half of season
			<?php endif; ?>
			<?php if ($last_update && isset($last_update['ts'])): ?>
			<span style="margin-left: 15px;">
				Last updated: <?= date('Y-m-d H:i:s', $last_update['ts'] / 1000) ?>
			</span>
			<?php endif; ?>
		</p>
	</div>

	<!-- Live Table Data -->
	<?php if (!empty($teams)): ?>
	<div class="panel">
		<h3>📊 Live Block 1 Standings</h3>
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
	<?php endif; ?>
</div>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
