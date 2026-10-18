<?php
require_once __DIR__ . '/team-tracker-helpers.php';
require_once __DIR__ . '/team-tracker-analysis.php';
$tableView = football_stats_get_table_view_combined($db, $trackerCode, $trackerTable, $currentMainTab ?? '2025-2026');
$standings = $tableView['standings'];
$team = football_stats_get_tracker_team($standings, $trackerDefaultTeam);
$escapeTracker = static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
if (!$team) {
    echo '<div class="panel"><h2>No team data available</h2><p>No standings are available for ' . $escapeTracker($trackerName) . ' in this view. Choose another snapshot or load competition data.</p>';
    football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', $currentLeague ?? 'premier-league', $currentSubTab ?? 'leeds');
    echo '</div>';
    return;
}
$seasonTracker = $tableView['active_season_label'] ?? $currentMainTab ?? '2025-2026';
$trackerRules = football_stats_get_competition_rules($trackerCode, $seasonTracker, $db);
$trackerScoring = football_stats_get_result_points($trackerCode, $seasonTracker, $db, ($tableView['calc_mode'] ?? '') === 'custom_matches');
$analysis = football_stats_tracker_analysis($team, $standings, $trackerRules, $trackerScoring);
$teamName = $team['team_name'];
$teamColors = football_stats_get_team_colors($teamName);
$teamPrimary = $teamColors['primary'];
$teamSecondary = $teamColors['secondary'];
$teamTextColor = '#ffffff';
$games_played = $analysis['played'];
$trackerTotalGames = $analysis['total'];
$halfway_point = (int)$trackerRules['halfway_games'];
// Preserve the original league-specific target ladder and familiar panel names.
$legacyTargets = in_array($trackerCode, ['PL', 'D1'], true)
    ? [20, 30, 38, 40, 42, 45, 50, 60, 70]
    : [23, 37, 46, 51, 54, 57, 60, 70, 90];
if ($trackerCode !== 'PL' && $trackerCode !== 'D1') {
    $legacyTargets[0] = $halfway_point;
    $legacyTargets[2] = $trackerTotalGames;
}
[$halfwayTarget, $halfSeasonTarget, $full_season_safety_target, $extended_season_safety_target,
 $guaranteed_season_safety_target, $mathematical_season_safety_target, $top_half_season_safety_target,
 $european_season_safety_target, $title_season_safety_target] = $legacyTargets;
$gamesToHalfway = max(0, $halfway_point - $games_played);
$halfwayNeed = max(0, $halfwayTarget - (int)$team['points']);
$legacyLabels = ['Halfway Point', 'Halfway Safety', 'Full Season Safety', 'Extended Safety', 'Guaranteed Safety', 'Mathematical Safety', 'Top Half', 'European', 'Title'];
$legacySafety = football_stats_tracker_safety($team, $standings, $trackerRules, $trackerScoring);
$trackerTableRows = static function ($rows) use ($teamName, $team, $games_played, $escapeTracker) {
    foreach ($rows as $row) {
        $selected = $row['team_name'] === $teamName;
        $gap = (int)$row['points'] - (int)$team['points'];
        $hand = $games_played - (int)$row['played'];
        echo '<tr' . ($selected ? ' class="tt-selected" aria-current="true"' : '') . '><td>' . (int)$row['position'] . '</td><th scope="row">' . $escapeTracker($row['team_name']) . ($selected ? ' (selected)' : '') . '</th><td>' . (int)$row['played'] . '</td><td>' . (int)$row['points'] . '</td><td>' . sprintf('%+d', (int)$row['gd']) . '</td><td>' . ($selected ? '—' : sprintf('%+d', $gap)) . '</td><td>' . ($selected ? '—' : sprintf('%+d', $hand)) . '</td></tr>';
    }
};
?>
<link rel="stylesheet" href="assets/team-tracker.css">
<div class="team-tracker">
<section class="panel tt-hero" style="border-top:4px solid <?= $escapeTracker($teamSecondary) ?>">
    <p class="tt-eyebrow"><?= $escapeTracker($trackerName) ?> · <?= $escapeTracker($seasonTracker) ?></p>
    <h1><?php if (!empty($team['team_crest'])): ?><img src="<?= $escapeTracker($team['team_crest']) ?>" alt="" width="48" height="48"><?php endif; ?><?= $escapeTracker($teamName) ?></h1>
    <h2>Team tracker</h2>
    <p class="tt-badge">#<?= (int)$team['position'] ?> · <?= $escapeTracker($analysis['zone']) ?></p>
    <p>Position in the selected table, not a prediction of the final outcome.</p>
    <?php football_stats_render_team_selector($standings, $teamName); ?>
    <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', $currentLeague ?? 'premier-league', $currentSubTab ?? 'leeds'); ?>
</section>
<nav class="panel tt-nav" aria-label="Tracker sections"><a href="#tt-outlook">Progress</a><a href="#tt-record">Record</a><a href="#tt-targets">Milestones</a><a href="#tt-scenarios">Scenarios</a><a href="#tt-battle">Position battle</a><a href="#tt-goals">Goals</a></nav>
<?php include __DIR__ . '/team-tracker-survival.php'; ?>
<div id="tt-outlook"><?php include __DIR__ . '/team-tracker-progress.php'; ?></div>
<section id="tt-record" class="panel">
    <h2>Season record</h2>
    <div class="tt-cards">
        <?php foreach (['won' => 'Wins', 'drawn' => 'Draws', 'lost' => 'Losses'] as $key => $label): ?>
        <div><h3><?= $label ?></h3><strong><?= (int)$team[$key] ?></strong><p><?= $games_played ? number_format((int)$team[$key] / $games_played * 100, 1) . '% of games' : 'No games played' ?></p></div>
        <?php endforeach; ?>
        <div><h3>Unbeaten games</h3><strong><?= (int)$team['won'] + (int)$team['drawn'] ?></strong><p>Wins and draws this season; not a consecutive streak.</p></div>
    </div>
    <?php if ($games_played): ?><div class="tt-record-bar" aria-label="Season record: <?= (int)$team['won'] ?> wins, <?= (int)$team['drawn'] ?> draws, <?= (int)$team['lost'] ?> losses">
        <?php foreach (['won','drawn','lost'] as $key): ?><span class="tt-<?= $key ?>" style="width:<?= min(100, max(0, (int)$team[$key] / $games_played * 100)) ?>%"></span><?php endforeach; ?>
    </div><p class="tt-muted">Green: wins · Blue: draws · Red: losses. Record covers the selected table; recent match form is not inferred from season totals.</p><?php endif; ?>
    <?php if ($analysis['adjustment']): ?><p class="tt-notice">Table points differ from the W/D/L scoring total by <?= sprintf('%+d', $analysis['adjustment']) ?> points. This may reflect deductions or table adjustments; projections keep the displayed points.</p><?php endif; ?>
</section>
<?php include __DIR__ . '/team-tracker-targets.php'; ?>
<section id="tt-scenarios" class="panel">
    <h2>What if? Results calculator</h2>
    <p>Explore a run of results within the <?= $analysis['remaining'] ?> games remaining. Points for a win/draw/loss: <?= implode(' / ', $trackerScoring) ?>.</p>
    <?php if (!$analysis['remaining']): ?><p>The season is complete in this table. There are no remaining games to model.</p><?php else: ?>
    <form class="tt-calculator" data-points="<?= (int)$team['points'] ?>" data-remaining="<?= $analysis['remaining'] ?>" data-played="<?= $games_played ?>" data-win="<?= $trackerScoring['win'] ?>" data-draw="<?= $trackerScoring['draw'] ?>" data-loss="<?= $trackerScoring['loss'] ?>">
        <div class="tt-cards">
        <?php foreach (['wins' => 'Wins', 'draws' => 'Draws', 'losses' => 'Losses'] as $key => $label): ?><label><?= $label ?><input name="<?= $key ?>" type="number" min="0" max="<?= $analysis['remaining'] ?>" step="1" value="0" required></label><?php endforeach; ?>
        </div>
        <p class="tt-result" role="status" aria-live="polite">Enter results to see the new points total and games left.</p>
        <button type="reset">Reset results</button>
    </form><noscript><p>Enable JavaScript to use the calculator. The pace scenarios below work without it.</p></noscript>
    <?php endif; ?>
    <details><summary>Compare run-in points rates</summary><div class="tt-scroll" tabindex="0" role="region" aria-label="Run-in pace scenarios"><table><caption>Future pace is applied only to remaining games</caption><thead><tr><th scope="col">Future PPG</th><th scope="col">Added points (estimate)</th><th scope="col">Final total (estimate)</th></tr></thead><tbody>
    <?php $paces = array_unique([0, 1, 1.5, 2, $analysis['max_result_points']]); sort($paces); foreach ($paces as $pace): if ($pace < $analysis['min_result_points'] || $pace > $analysis['max_result_points']) continue; ?>
    <tr><th scope="row"><?= number_format($pace, 2) ?></th><td><?= number_format($pace * $analysis['remaining'], 1) ?></td><td><?= number_format((int)$team['points'] + $pace * $analysis['remaining'], 1) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></details>
</section>
<?php include __DIR__ . '/team-tracker-scenario-panels.php'; ?>
<section id="tt-battle" class="panel">
    <h2>League position battle</h2>
    <p>Compare the teams within two places. Points gap and games in hand are measured against <?= $escapeTracker($teamName) ?>; a positive number means the listed team has more points or games in hand.</p>
    <div class="tt-scroll" tabindex="0" role="region" aria-label="Nearby teams"><table><thead><tr><th scope="col">Pos</th><th scope="col">Team</th><th scope="col">P</th><th scope="col">Pts</th><th scope="col">GD</th><th scope="col">Points gap</th><th scope="col">Games in hand</th></tr></thead><tbody><?php $trackerTableRows($analysis['nearby']); ?></tbody></table></div>
</section>
<section class="panel">
    <h2>Competition zone watch</h2>
    <?php if (!$analysis['boundaries']): ?><p>No promotion, qualification or relegation zones are configured for this competition and season.</p><?php else: ?><div class="tt-cards">
    <?php foreach ($analysis['boundaries'] as $boundary): $zone = $boundary['zone']; ?>
        <div><h3><?= $escapeTracker($zone['label']) ?></h3><p>Positions <?= (int)$zone['from'] ?>–<?= (int)$zone['to'] ?></p>
        <?php if ($boundary['rival']): ?><strong><?= abs($boundary['gap']) ?> pts <?= $boundary['gap'] > 0 ? 'ahead' : ($boundary['gap'] < 0 ? 'behind' : 'level') ?></strong><p>Compared with #<?= (int)$boundary['rival']['position'] ?> <?= $escapeTracker($boundary['rival']['team_name']) ?></p><p><?= $boundary['games_in_hand'] === 0 ? 'Same games played' : abs($boundary['games_in_hand']) . ($boundary['games_in_hand'] > 0 ? ' games in hand for your team' : ' more games played by your team') ?></p>
        <?php else: ?><p>Boundary team is unavailable in this table.</p><?php endif; ?></div>
    <?php endforeach; ?></div><p class="tt-muted">These are current table gaps, not clinching calculations. Level points can still mean different positions because of tie-breakers. Zones follow the configured rules for this season.</p><?php endif; ?>
    <details open><summary>Relegation Battle — Bottom six comparison</summary><div class="tt-scroll" tabindex="0" role="region" aria-label="Bottom six teams"><table><thead><tr><th scope="col">Pos</th><th scope="col">Team</th><th scope="col">P</th><th scope="col">Pts</th><th scope="col">GD</th><th scope="col">Points gap</th><th scope="col">Games in hand</th></tr></thead><tbody><?php $trackerTableRows($analysis['bottom']); ?></tbody></table></div></details>
</section>
<section id="tt-goals" class="panel">
    <h2>Attack and defence</h2><div class="tt-cards">
    <?php foreach (['gf' => 'Goals scored', 'ga' => 'Goals conceded', 'gd' => 'Goal difference'] as $key => $label): ?><div><h3><?= $label ?></h3><strong><?= $key === 'gd' ? sprintf('%+d', (int)$team[$key]) : (int)$team[$key] ?></strong><p><?= $games_played ? number_format((int)$team[$key] / $games_played, 2) . ' per game' : 'No games played' ?></p></div><?php endforeach; ?>
    </div>
    <?php if ($analysis['league_goal_rate'] !== null && $games_played): ?><p>The selected league table averages <strong><?= number_format($analysis['league_goal_rate'], 2) ?></strong> goals scored per team per game. Your attack is <?= number_format(abs((int)$team['gf'] / $games_played - $analysis['league_goal_rate']), 2) ?> goals per game <?= (int)$team['gf'] / $games_played >= $analysis['league_goal_rate'] ? 'above' : 'below' ?> that average.</p><?php endif; ?>
</section>
<?php include __DIR__ . '/team-tracker-halfway.php'; ?>
<section class="panel tt-notes"><h2>Reading this tracker</h2>
    <p>Every panel uses the same selected table, including its filters, scoring rules and points adjustments. A filtered or hypothetical table describes that selection, rather than the full real-world season.</p>
    <p>Points forecasts continue the current average and are estimates, not survival probabilities. Fixed totals cannot guarantee safety, promotion, qualification or a title. The halfway benchmark is a planning aid, not a historical survival model.</p>
    <p>Use the zone gaps to identify nearby rivals, the target planner to choose a points goal, and the results calculator to test a feasible route toward it.</p>
</section>
<section class="panel tt-hero"><h2>Keep pushing, <?= $escapeTracker($teamName) ?>!</h2><p><?= $analysis['remaining'] ? $analysis['remaining'] . ' games remain. Pick the next points milestone and test a route to it above.' : 'Season complete in this view. Review the final record and milestones above.' ?></p></section>
</div>
<script src="assets/team-tracker.js" defer></script>
