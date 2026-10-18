<section id="tt-targets" class="panel">
    <h2>Milestone progress — original target ladder</h2>
    <p>All original targets are retained below. “Guaranteed”, “Mathematical”, “Top Half”, “European” and “Title” are traditional benchmark names; reaching a fixed points total does not establish those outcomes.</p>
    <div class="tt-cards">
        <div><h3>Current position</h3><strong>#<?= (int)$team['position'] ?></strong><p><?= $escapeTracker($analysis['zone']) ?></p></div>
        <div><h3>Current points</h3><strong><?= (int)$team['points'] ?></strong><p><?= $analysis['pace'] === null ? 'No games played' : number_format($analysis['pace'], 2) . ' points per game' ?></p></div>
        <div><h3>Games to halfway</h3><strong><?= $gamesToHalfway ?></strong><p><?= $gamesToHalfway ? 'Halfway at game ' . $halfway_point : 'Halfway reached; current points are not a historical halfway total.' ?></p></div>
        <div><h3>Games remaining</h3><strong><?= $analysis['remaining'] ?></strong><p><?= $analysis['remaining'] * $analysis['max_result_points'] ?> points still available.</p></div>
    </div>
</section>
<div class="tt-target-grid">
<?php foreach ($legacyTargets as $index => $target):
    $need = max(0, $target - (int)$team['points']);
    $horizon = $index === 0 ? $gamesToHalfway : $analysis['remaining'];
    $historical = $index === 0 && $games_played > $halfway_point;
    $reachable = $need <= $horizon * $analysis['max_result_points'];
    $label = $legacyLabels[$index];
?>
<section class="panel">
    <h2><?= $escapeTracker($label) ?> Target Progress</h2>
    <p class="tt-eyebrow"><?= $target ?>-point benchmark<?= $index === 0 ? ' · by game ' . $halfway_point : ' · full season' ?></p>
    <strong class="tt-target-number"><?= (int)$team['points'] ?> / <?= $target ?> pts</strong>
    <progress max="<?= max(1, $target) ?>" value="<?= min($target, max(0, (int)$team['points'])) ?>" aria-label="<?= $escapeTracker($label) ?> target progress"></progress>
    <p><?= $need ?> points needed · <?= number_format(max(0, (int)$team['points'] / max(1, $target) * 100), 1) ?>% of target</p>
    <p><strong><?= $historical ? 'Historical halfway total unavailable' : ($need === 0 ? 'Benchmark reached' : ($horizon === 0 ? 'Benchmark missed at deadline' : (!$reachable ? 'Out of reach within deadline' : number_format($need / $horizon, 2) . ' PPG needed from ' . $horizon . ' games'))) ?></strong></p>
    <?php if ($historical): ?><p class="tt-muted">This bar compares current points with the halfway benchmark; it does not claim the benchmark was reached at halfway.</p><?php endif; ?>
    <?php if ($index === 4): ?><p class="tt-muted">“Guaranteed Safety” remains a target label. Check the mathematical panel for the conservative rival-points calculation.</p><?php endif; ?>
    <?php if ($index === 5): ?><p class="tt-notice">Mathematical check: <?= $legacySafety === null ? 'unavailable — full standings and a relegation cutoff are required.' : ($legacySafety ? 'enough rivals cannot catch this team, even on level points.' : 'not established — rivals can still catch this team.') ?></p><p class="tt-muted">Uses the selected table, configured scoring and remaining schedule. Ties remain catchable; future deductions and rule changes are excluded.</p><?php endif; ?>
</section>
<?php endforeach; ?>
</div>
