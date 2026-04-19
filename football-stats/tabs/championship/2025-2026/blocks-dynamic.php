<?php
/**
 * Dynamic Blocks - Live Predictions & Projections for Championship
 * Real-time analysis with PPG-based season projections
 */

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/table-view.php';

$tableView = football_stats_get_table_view($db, 'ELC', 'league_table_ELC', $currentMainTab ?? '2025-2026');
$allTeams = $tableView['standings'];
$currentMatchday = $tableView['active_matchweek'] ?? (!empty($allTeams) ? max(array_map('intval', array_column($allTeams, 'played'))) : 1);
$last_update = $tableView['last_update'];
$tableViewNavParams = football_stats_get_current_table_view_params();
$totalGames = 46;

// Calculate projections for all teams
$projections = [];
foreach ($allTeams as $team) {
	$ppg = $team['played'] > 0 ? round($team['points'] / $team['played'], 2) : 0;
	$projectedPoints = round($ppg * $totalGames, 0);
	$remainingGames = $totalGames - $team['played'];
	// Block logic for Championship
	$predictedBlock = 5;
	if ($projectedPoints >= 85) $predictedBlock = 1;
	elseif ($projectedPoints >= 75) $predictedBlock = 2;
	elseif ($projectedPoints >= 60) $predictedBlock = 3;
	elseif ($projectedPoints >= 50) $predictedBlock = 4;
	// Current block
	$currentBlock = 5;
	if ($team['position'] <= 2) $currentBlock = 1;
	elseif ($team['position'] <= 6) $currentBlock = 2;
	elseif ($team['position'] <= 14) $currentBlock = 3;
	elseif ($team['position'] <= 22) $currentBlock = 4;
	// Risk assessment
	if ($projectedPoints >= 50) {
		$risk = 'Safe';
		$riskColor = '#4CAF50';
	} elseif ($projectedPoints >= 42) {
		$risk = 'Caution';
		$riskColor = '#FFA500';
	} else {
		$risk = 'Danger';
		$riskColor = '#F44336';
	}
	$projections[] = [
		'team' => $team,
		'ppg' => $ppg,
		'projected' => $projectedPoints,
		'remaining' => $remainingGames,
		'currentBlock' => $currentBlock,
		'predictedBlock' => $predictedBlock,
		'blockChange' => $predictedBlock - $currentBlock,
		'risk' => $risk,
		'riskColor' => $riskColor
	];
}
usort($projections, function($a, $b) {
	return $b['projected'] - $a['projected'];
});
?>

<div class="blocks-dynamic-page">
	<div class="panel" style="border-left: 4px solid #5865F2;">
		<h2>🔥 Dynamic Blocks & Projections</h2>
		<?php football_stats_render_table_view_controls($tableView, $currentMainTab ?? '2025-2026', $currentLeague ?? 'championship', $currentSubTab ?? 'blocks-dynamic'); ?>
		<p style="margin-top: 15px; font-size: 1.1em; color: #ddd;">
			Projected block placement and risk assessment for all teams based on current points per game (PPG).
		</p>
		<p style="margin-top: 10px; color: #888; font-size: 0.9em;">
			📅 Current Matchday: <strong><?php echo $currentMatchday; ?></strong> of 46
		</p>
	</div>
	<div class="panel">
		<table class="table table-striped">
			<thead>
				<tr>
					<th>Pos</th>
					<th>Team</th>
					<th>Pld</th>
					<th>PPG</th>
					<th>Projected Pts</th>
					<th>Current Block</th>
					<th>Predicted Block</th>
					<th>Risk</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($projections as $proj): ?>
				<tr>
					<td><?php echo $proj['team']['position']; ?></td>
					<td><?php echo htmlspecialchars($proj['team']['team_name']); ?></td>
					<td><?php echo $proj['team']['played']; ?></td>
					<td><?php echo $proj['ppg']; ?></td>
					<td><?php echo $proj['projected']; ?></td>
					<td><?php echo $proj['currentBlock']; ?></td>
					<td><?php echo $proj['predictedBlock']; ?></td>
					<td style="color:<?php echo $proj['riskColor']; ?>; font-weight:bold;"><?php echo $proj['risk']; ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
