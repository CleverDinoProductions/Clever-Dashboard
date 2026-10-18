<?php
// Uses the selected table snapshot, never a separate live-data query.
$trackerPlayed = max(0, (int)$games_played);
$trackerRemaining = max(0, $trackerTotalGames - $trackerPlayed);
$trackerPoints = (int)$team['points'];
$trackerResultPoints = $trackerScoring ?? ['win' => 3, 'draw' => 1, 'loss' => 0];
$trackerAvailable = $trackerRemaining * max($trackerResultPoints);
$trackerMaximum = $trackerPoints + $trackerAvailable;
$trackerPercent = min(100, max(0, $trackerPlayed / max(1, $trackerTotalGames) * 100));
$trackerPace = $trackerPlayed > 0 ? $trackerPoints / $trackerPlayed : null;
$trackerProjection = $trackerPace === null ? null : min($trackerMaximum, max($trackerPoints + min($trackerResultPoints) * $trackerRemaining, $trackerPoints + $trackerPace * $trackerRemaining));
$trackerPhase = $trackerRemaining === 0 ? 'Season complete' : ($trackerPlayed === 0 ? 'Season not started' : ($trackerPlayed < $halfway_point ? 'First half' : ($trackerPlayed === $halfway_point ? 'Halfway reached' : 'Second half')));
$trackerMilestones = array_unique([(int)$full_season_safety_target, (int)$extended_season_safety_target, (int)$guaranteed_season_safety_target, (int)$mathematical_season_safety_target, (int)$top_half_season_safety_target, (int)$european_season_safety_target, (int)$title_season_safety_target]);
sort($trackerMilestones);
?>
<style>
.tracker-progress { background:linear-gradient(135deg,#252a38,#2e3136); color:#fff; border:1px solid #596174; }
.tracker-progress .tracker-heading { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; }
.tracker-progress h2 { margin:0; color:#fff; }
.tracker-progress .tracker-phase { padding:6px 12px; border-radius:20px; background:#404854; color:#fff; }
.tracker-progress .tracker-metrics { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin:20px 0; }
.tracker-progress .tracker-metric { background:#222732; border-radius:8px; padding:14px; }
.tracker-progress .tracker-metric strong { display:block; font-size:28px; color:#fff; margin:5px 0; }
.tracker-progress .tracker-muted { color:#d0d5df; font-size:14px; }
.tracker-progress .tracker-bar { height:18px; background:#454d5c; border-radius:10px; position:relative; overflow:hidden; }
.tracker-progress .tracker-fill { height:100%; background:linear-gradient(90deg,#7385ff,#58d5af); }
.tracker-progress .tracker-ticks { display:flex; justify-content:space-between; font-size:12px; color:#d0d5df; margin:8px 0 20px; }
.tracker-progress table { width:100%; border-collapse:collapse; color:#fff; }
.tracker-progress th,.tracker-progress td { padding:12px; text-align:left; border-bottom:1px solid #4b5362; white-space:nowrap; }
.tracker-progress caption { text-align:left; font-size:18px; font-weight:bold; padding:16px 0; color:#fff; }
.tracker-progress details summary { cursor:pointer; padding:12px 0; font-weight:bold; }
</style>
<section class="panel tracker-progress" aria-label="Season progress and points outlook">
    <div class="tracker-heading">
        <h2>Season Progress</h2>
        <span class="tracker-phase"><?= $trackerPhase ?> · <?= round($trackerPercent, 1) ?>%</span>
    </div>
    <div class="tracker-metrics">
        <div class="tracker-metric"><span class="tracker-muted">Games played</span><strong><?= $trackerPlayed ?> <small style="font-size:16px">/ <?= $trackerTotalGames ?></small></strong></div>
        <div class="tracker-metric"><span class="tracker-muted">Games remaining</span><strong><?= $trackerRemaining ?></strong></div>
        <div class="tracker-metric"><span class="tracker-muted">Points available</span><strong><?= $trackerAvailable ?></strong></div>
        <div class="tracker-metric"><span class="tracker-muted">Current points</span><strong><?= $trackerPoints ?></strong></div>
    </div>
    <div class="tracker-bar" role="progressbar" aria-label="Season games completed" aria-valuemin="0" aria-valuemax="<?= $trackerTotalGames ?>" aria-valuenow="<?= min($trackerPlayed, $trackerTotalGames) ?>" aria-valuetext="<?= $trackerPlayed ?> of <?= $trackerTotalGames ?> games played">
        <div class="tracker-fill" style="width:<?= $trackerPercent ?>%"></div>
        <span style="position:absolute;left:<?= min(100, $halfway_point / max(1, $trackerTotalGames) * 100) ?>%;top:0;bottom:0;border-left:2px solid white" aria-hidden="true"></span>
    </div>
    <div class="tracker-ticks"><span>Start · 0</span><span>Halfway · <?= $halfway_point ?></span><span>Finish · <?= $trackerTotalGames ?></span></div>
    <div class="tracker-metrics">
        <div class="tracker-metric"><span class="tracker-muted">Points per game</span><strong><?= $trackerPace === null ? '—' : number_format($trackerPace, 2) ?></strong></div>
        <div class="tracker-metric"><span class="tracker-muted"><?= $trackerRemaining === 0 ? 'Final points' : 'Projected finish' ?></span><strong><?= $trackerProjection === null ? '—' : number_format($trackerProjection, 1) ?></strong></div>
        <div class="tracker-metric"><span class="tracker-muted">Maximum possible points</span><strong><?= $trackerMaximum ?></strong></div>
    </div>
    <p class="tracker-muted">Based on the selected table view. Projection uses current points per game, bounded by possible remaining results; future results and deductions can change it. Remaining points use the highest available result score (<?= max($trackerResultPoints) ?> points per game).</p>
    <details open>
        <summary>Points target planner</summary>
        <div style="overflow-x:auto" tabindex="0" role="region" aria-label="Points targets">
            <table>
                <caption>What is needed from the remaining <?= $trackerRemaining ?> games?</caption>
                <thead><tr><th scope="col">Target</th><th scope="col">Points needed</th><th scope="col">Required PPG</th><th scope="col">Outlook</th></tr></thead>
                <tbody>
                <?php foreach ($trackerMilestones as $trackerTarget):
                    $trackerNeeded = max(0, $trackerTarget - $trackerPoints);
                    $trackerReachable = $trackerNeeded <= $trackerAvailable;
                    $trackerOutlook = $trackerNeeded === 0 ? 'Target reached' : (!$trackerReachable ? ($trackerRemaining === 0 ? 'Not reached' : 'Out of reach') : ($trackerPace === null ? 'No games played yet' : ($trackerProjection >= $trackerTarget ? 'On current pace' : 'Higher pace needed')));
                ?>
                    <tr><th scope="row"><?= $trackerTarget ?> pts</th><td><?= $trackerNeeded ?></td><td><?= $trackerNeeded === 0 ? '0.00' : ($trackerRemaining > 0 && $trackerReachable ? number_format($trackerNeeded / $trackerRemaining, 2) : '—') ?></td><td><?= $trackerOutlook ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="tracker-muted">Points milestones are planning targets. They do not guarantee safety, promotion or qualification.</p>
    </details>
</section>
