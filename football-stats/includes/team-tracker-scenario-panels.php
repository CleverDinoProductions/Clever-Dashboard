<?php
$renderLegacyScenarios = static function ($games, $rows, $target) use ($team, $analysis) {
?>
<div class="tt-scroll" tabindex="0" role="region" aria-label="Result combinations"><table><thead><tr><th scope="col">W–D–L</th><th scope="col">Added points</th><th scope="col">Total points</th><th scope="col">Target gap</th></tr></thead><tbody>
<?php foreach ($rows as $row): $total = (int)$team['points'] + $row['points']; ?>
<tr><th scope="row"><?= $row['wins'] ?>–<?= $row['draws'] ?>–<?= $row['losses'] ?></th><td><?= $row['points'] ?></td><td><?= $total ?></td><td><?= max(0, $target - $total) ?> pts</td></tr>
<?php endforeach; ?></tbody></table></div><p class="tt-muted">Every row accounts for exactly <?= $games ?> games. Target gap uses the <?= $target ?>-point benchmark, not a predicted league position.</p>
<?php };
?>
<section class="panel">
    <h2>Scenarios to Halfway</h2>
    <?php if (!$gamesToHalfway): ?><p>Halfway has been reached. Select a snapshot from before game <?= $halfway_point ?> to explore the run to halfway.</p><?php else:
        // For long horizons show representative complete runs, rather than hundreds of combinations.
        $halfwayRows = $gamesToHalfway <= 6 ? football_stats_tracker_scenarios($gamesToHalfway, $trackerScoring) : array_map(static function ($record) use ($trackerScoring) {
            return ['wins' => $record[0], 'draws' => $record[1], 'losses' => $record[2], 'points' => $record[0] * $trackerScoring['win'] + $record[1] * $trackerScoring['draw'] + $record[2] * $trackerScoring['loss']];
        }, [[$gamesToHalfway,0,0], [(int)ceil($gamesToHalfway/2),(int)floor($gamesToHalfway/2),0], [0,$gamesToHalfway,0], [0,0,$gamesToHalfway]]);
    ?><p><?= $gamesToHalfway ?> games to halfway · <?= $halfwayTarget ?>-point target. <?= $gamesToHalfway > 6 ? 'Representative full runs are shown.' : 'All W/D/L combinations are shown.' ?></p><?php $renderLegacyScenarios($gamesToHalfway, $halfwayRows, $halfwayTarget); endif; ?>
</section>
<?php for ($horizon = 6; $horizon >= 1; $horizon--): ?>
<section class="panel"><h2>What If Scenarios — Next <?= $horizon ?> <?= $horizon === 1 ? 'Game' : 'Games' ?></h2>
<?php if ($horizon > $analysis['remaining']): ?><p>Unavailable: only <?= $analysis['remaining'] ?> games remain in this season.</p><?php else: ?><details<?= $horizon === 1 ? ' open' : '' ?>><summary>Explore all <?= count(football_stats_tracker_scenarios($horizon, $trackerScoring)) ?> result combinations</summary><?php $renderLegacyScenarios($horizon, football_stats_tracker_scenarios($horizon, $trackerScoring), $full_season_safety_target); ?></details><?php endif; ?></section>
<?php endfor; ?>
<section class="panel"><h2>Season Projection</h2><div class="tt-cards"><div><h3>Current PPG</h3><strong><?= $analysis['pace'] === null ? '—' : number_format($analysis['pace'], 2) ?></strong></div><div><h3>Projected final points</h3><strong><?= $trackerProjection === null ? '—' : number_format($trackerProjection, 1) ?></strong><p>Current points plus current pace over remaining games, bounded by possible results.</p></div><div><h3>Projected at halfway</h3><strong><?= !$gamesToHalfway || $analysis['pace'] === null ? '—' : number_format(min((int)$team['points'] + $gamesToHalfway * $analysis['max_result_points'], max((int)$team['points'] + $gamesToHalfway * $analysis['min_result_points'], (int)$team['points'] + $analysis['pace'] * $gamesToHalfway)), 1) ?></strong><p><?= !$gamesToHalfway ? 'Historical halfway points require a halfway snapshot.' : 'Estimate, not a guaranteed result.' ?></p></div></div></section>
<?php foreach ([[0,9], [10,19], [20,30]] as [$start, $end]): ?>
<section class="panel"><h2>Full Season Projection — <?= number_format($start/10, 1) ?>–<?= number_format($end/10, 1) ?> PPG</h2><details><summary>Compare future points rates</summary><div class="tt-scroll" tabindex="0" role="region" aria-label="Full season projection rates"><table><thead><tr><th scope="col">Future PPG</th><th scope="col">Added points</th><th scope="col">Final estimate</th></tr></thead><tbody>
<?php for ($rate = $start; $rate <= $end; $rate++): $pace = $rate / 10; ?><tr><th scope="row"><?= number_format($pace, 1) ?></th><td><?= $pace < $analysis['min_result_points'] || $pace > $analysis['max_result_points'] ? 'Not possible under scoring rules' : number_format($pace * $analysis['remaining'], 1) ?></td><td><?= $pace < $analysis['min_result_points'] || $pace > $analysis['max_result_points'] ? '—' : number_format((int)$team['points'] + $pace * $analysis['remaining'], 1) ?></td></tr><?php endfor; ?>
</tbody></table></div><p class="tt-muted">These rates apply to remaining games only. Decimal totals are estimates, not exact obtainable scores.</p></details></section>
<?php endforeach; ?>
