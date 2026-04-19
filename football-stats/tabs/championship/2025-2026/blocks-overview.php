<?php
/**
 * Blocks Overview - Championship Blocks Framework
 * Comprehensive overview showing all 5 blocks with live data
 */

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/table-view.php';

$tableView = football_stats_get_table_view($db, 'ELC', 'league_table_ELC', $currentMainTab ?? '2025-2026');
$allTeams = $tableView['standings'];
$currentMatchday = $tableView['active_matchweek'] ?? (!empty($allTeams) ? max(array_map('intval', array_column($allTeams, 'played'))) : 1);
$last_update = $tableView['last_update'];
$tableViewNavParams = football_stats_get_current_table_view_params();

// Organize teams into blocks (Championship-specific ranges)
$blocks = [
	1 => ['teams' => [], 'range' => '1-2', 'title' => 'Title Contenders', 'emoji' => '🏆', 'color' => '#FFD700', 'desc' => 'Automatic Promotion'],
	2 => ['teams' => [], 'range' => '3-6', 'title' => 'Playoff Contenders', 'emoji' => '🎯', 'color' => '#5865F2', 'desc' => 'Playoff Spots'],
	3 => ['teams' => [], 'range' => '7-14', 'title' => 'Mid-Table Security', 'emoji' => '⚖️', 'color' => '#99AAB5', 'desc' => 'Safe & Stable'],
	4 => ['teams' => [], 'range' => '21-22', 'title' => 'Relegation Battle', 'emoji' => '⚠️', 'color' => '#FFA500', 'desc' => 'Danger Zone'],
	5 => ['teams' => [], 'range' => '23-24', 'title' => 'Relegation Zone', 'emoji' => '🔴', 'color' => '#F44336', 'desc' => 'Drop Zone']
];
foreach ($allTeams as $team) {
	$pos = $team['position'];
	if ($pos >= 1 && $pos <= 2) $blocks[1]['teams'][] = $team;
	if ($pos >= 3 && $pos <= 6) $blocks[2]['teams'][] = $team;
	if ($pos >= 7 && $pos <= 14) $blocks[3]['teams'][] = $team;
	if ($pos >= 21 && $pos <= 22) $blocks[4]['teams'][] = $team;
	if ($pos >= 23 && $pos <= 24) $blocks[5]['teams'][] = $team;
}
?>

<div class="blocks-overview-page">
	<!-- Page Header -->
	<div class="panel" style="border-left: 4px solid #5865F2;">
		<div class="overview-header">
			<h2>📊 Blocks Framework Overview</h2>
			<span class="season-badge">2025/26 Season</span>
		</div>
		<?php football_stats_render_table_view_controls($tableView, $currentMainTab ?? '2025-2026', $currentLeague ?? 'championship', $currentSubTab ?? 'blocks-overview'); ?>
		<p style="margin-top: 15px; font-size: 1.1em; color: #ddd;">
			The Championship divided into 5 strategic blocks, each representing distinct competitive zones with unique targets, challenges, and psychological boundaries.
		</p>
		<p style="margin-top: 10px; color: #888; font-size: 0.9em;">
			📅 Current Matchday: <strong><?php echo $currentMatchday; ?></strong> of 46 • 
			<?php if ($currentMatchday < 23): ?>
				First half of season
			<?php elseif ($currentMatchday == 23): ?>
				🎯 Halfway point!
			<?php elseif ($currentMatchday > 23 && $currentMatchday <= 38): ?>
				Mid-season phase
			<?php elseif ($currentMatchday > 38 && $currentMatchday < 46): ?>
				Final stretch
			<?php endif; ?>
			<?php if ($last_update && isset($last_update['ts'])): ?>
			<span style="margin-left: 15px;">
				Last updated: <?= date('Y-m-d H:i:s', $last_update['ts'] / 1000) ?>
			</span>
			<?php endif; ?>
		</p>
	</div>

	<!-- Live Blocks Summary -->
	<?php foreach ($blocks as $blockNum => $block): ?>
	<div class="panel" style="border-left: 4px solid <?php echo $block['color']; ?>;">
		<div class="block-summary-header">
			<div class="block-title-section">
				<h3 style="color: <?php echo $block['color']; ?>;">
					<?php echo $block['emoji']; ?> Block <?php echo $blockNum; ?>: <?php echo $block['title']; ?>
				</h3>
				<span class="positions-badge" style="background: rgba(<?php 
					echo $blockNum == 1 ? '255, 215, 0' : 
						($blockNum == 2 ? '88, 101, 242' : 
						($blockNum == 3 ? '153, 170, 181' : 
						($blockNum == 4 ? '255, 165, 0' : '244, 67, 54'))); 
				?>, 0.2); color: <?php echo $block['color']; ?>; border: 1px solid <?php echo $block['color']; ?>;">
					Positions <?php echo $block['range']; ?>
				</span>
			</div>
			<p style="color: #888; margin: 10px 0 15px 0;">
				<strong style="color: <?php echo $block['color']; ?>;"><?php echo $block['desc']; ?></strong>
			</p>
		</div>

		<?php if (!empty($block['teams'])): ?>
		<div class="mini-table-wrapper">
			<table class="mini-block-table">
				<thead>
					<tr style="background: rgba(<?php 
						echo $blockNum == 1 ? '255, 215, 0' : 
							($blockNum == 2 ? '88, 101, 242' : 
							($blockNum == 3 ? '153, 170, 181' : 
							($blockNum == 4 ? '255, 165, 0' : '244, 67, 54'))); 
					?>, 0.1);">
						<th>Pos</th>
						<th>Team</th>
						<th>Pld</th>
						<th>W</th>
						<th>D</th>
						<th>L</th>
						<th>GD</th>
						<th>Pts</th>
						<th>PPG</th>
						<th>Form</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($block['teams'] as $team): 
						$ppg = $team['played'] > 0 ? round($team['points'] / $team['played'], 2) : 0; ?>
						<tr>
							<td><?php echo $team['position']; ?></td>
							<td><?php echo htmlspecialchars($team['team_name']); ?></td>
							<td><?php echo $team['played']; ?></td>
							<td><?php echo $team['won']; ?></td>
							<td><?php echo $team['drawn']; ?></td>
							<td><?php echo $team['lost']; ?></td>
							<td><?php echo $team['gd']; ?></td>
							<td><?php echo $team['points']; ?></td>
							<td><?php echo $ppg; ?></td>
							<td><!-- Form placeholder --></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
	</div>
	<?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
