<?php
$legacyPoints = (int)$team['points'];
$legacyHalfPercent = $legacyPoints / max(1, $halfwayTarget) * 100;
$legacyBand = $legacyPoints >= $full_season_safety_target ? '95%+' : ($legacyHalfPercent >= 100 ? '95%+' : ($legacyHalfPercent >= 75 ? '85–90%' : ($legacyHalfPercent >= 50 ? '60–70%' : '40% or less')));
$legacyInDanger = false;
$legacyHasRelegation = false;
foreach ($trackerRules['zones'] as $zone) {
    if ($zone['key'] !== 'relegation') continue;
    $legacyHasRelegation = true;
    if ((int)$team['position'] >= (int)$zone['from'] && (int)$team['position'] <= (int)$zone['to']) $legacyInDanger = true;
}
?>
<section class="panel">
    <h2>Survival status and probability guide</h2>
    <div class="tt-cards">
        <div><h3>Current status</h3><strong><?= $legacySafety === true ? 'Safety secured in this model' : (!$legacyHasRelegation ? 'No configured relegation line' : ($legacyInDanger ? 'Danger zone' : 'Outside relegation zone')) ?></strong><p><?= $escapeTracker($analysis['zone']) ?> · Position #<?= (int)$team['position'] ?></p><?php if (!$legacyHasRelegation): ?><p>No relegation line is configured for this competition.</p><?php endif; ?></div>
        <div><h3>Survival probability — legacy heuristic</h3><strong><?= $legacyBand ?></strong><p>Original points-based guide, not a calibrated probability. It does not account for rival strength, fixtures or games played.</p></div>
        <div><h3>Full season safety target</h3><strong><?= $legacyPoints ?> / <?= $full_season_safety_target ?> pts</strong><p><?= max(0, $full_season_safety_target - $legacyPoints) ?> points to the traditional benchmark.</p></div>
    </div>
    <?php if ($legacyInDanger): ?><p class="tt-notice">The team is currently in a relegation position even if its points-based guide looks favourable.</p><?php endif; ?>
    <?php if ($gamesToHalfway && $analysis['pace'] !== null && $analysis['pace'] * $halfway_point >= $halfwayTarget): ?><p class="tt-notice"><strong>Ahead of schedule:</strong> current pace reaches the <?= $halfwayTarget ?>-point halfway benchmark. Results can change that projection.</p><?php endif; ?>
</section>
