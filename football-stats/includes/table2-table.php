<?php
require_once __DIR__ . '/table2-metrics.php';
$escapeTable2 = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$table2Targets = [$competitionRules['comparison_target_one'], $competitionRules['comparison_target_two']];
require __DIR__ . '/table2-styles.php';
?>
<p class="table2-hint">Scroll across to compare targets and scenarios. All figures use the selected table period.</p>
<div class="table2-scroll" role="region" aria-label="League standings, targets and projections" tabindex="0">
<table class="league-table table2-table">
    <caption>Standings and points outlook · <?= $escapeTable2($tableView['active_season_label']) ?> · <?= $escapeTable2(ucwords(str_replace('_', ' ', $table_filter))) ?> matches</caption>
    <thead>
        <tr class="table2-groups">
            <th colspan="7" scope="colgroup">Standings</th>
            <?php foreach ($table2Targets as $target): ?>
                <th colspan="2" scope="colgroup">Target: <?= $escapeTable2($target) ?> points</th>
            <?php endforeach; ?>
            <th colspan="2" scope="colgroup">Current pace</th>
            <th colspan="3" scope="colgroup">Remaining results scenarios</th>
        </tr>
        <tr>
            <th scope="col" title="Position change against the selected comparison">Move</th>
            <th scope="col" title="Position">Pos</th>
            <th scope="col" class="table2-team">Team</th>
            <th scope="col" title="Matches played">Played</th>
            <th scope="col" title="Matches remaining in the selected period">Left</th>
            <th scope="col" title="Current points, including applicable deductions">Points</th>
            <th scope="col" title="Points per game">PPG</th>
            <?php foreach ($table2Targets as $target): ?>
                <th scope="col" title="Additional points to reach <?= $escapeTable2($target) ?>">Pts needed<br>(<?= $escapeTable2($target) ?>)</th>
                <th scope="col" title="Required points per remaining game to reach <?= $escapeTable2($target) ?>">PPG needed<br>(<?= $escapeTable2($target) ?>)</th>
            <?php endforeach; ?>
            <th scope="col" title="Points minus games played, relative to one point per game">Buffer</th>
            <th scope="col" title="Projected final points if current points per game continues">Projected pts</th>
            <th scope="col" title="Final points if every remaining match is won">All wins</th>
            <th scope="col" title="Final points if every remaining match is drawn">All draws</th>
            <th scope="col" title="Estimated final points with half wins and half draws; rounded for an odd number of matches">50/50 W/D</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($standings)): ?>
        <tr><td colspan="16" class="table2-empty">No completed matches available for this selection.</td></tr>
    <?php endif; ?>
    <?php foreach ($standings as $team): ?>
        <?php
        $info = getTeamInfo($team['team_name'], $team_info);
        $metrics = football_stats_table2_metrics($team, $total_games, $resultPoints, $table2Targets);
        // Retained for callers that inspect the rendered period's remaining games.
        $games_remaining = $metrics['remaining'];
        ?>
        <tr <?= football_stats_table_row_attributes($table2CompetitionCode, $tableView['active_season_label'], (int)$team['position']) ?>>
            <td class="movement-column"><?php if (empty($tableView['position_movements'][$team['team_name']])): ?><span aria-label="No position change">—</span><?php else: ?><?php football_stats_render_position_movement($tableView, $team['team_name']); ?><?php endif; ?></td>
            <td><?= (int)$team['position'] ?></td>
            <th scope="row" class="table2-team">
                <div class="team-cell">
                    <?php if (!empty($team['team_crest'])): ?><img src="<?= $escapeTable2($team['team_crest']) ?>" alt="" class="team-crest" loading="lazy" onerror="this.style.display='none'"><?php endif; ?>
                    <span><span class="table2-team-name"><?= $escapeTable2($team['team_name']) ?></span><small><?= $escapeTable2($info['nickname']) ?> · <?= $escapeTable2($info['short']) ?></small></span>
                </div>
            </th>
            <td><?= (int)$team['played'] ?></td>
            <td><?= $metrics['remaining'] ?></td>
            <td class="table2-points"><?= $escapeTable2($team['points']) ?></td>
            <td><?= $metrics['ppg'] === null ? '—' : number_format($metrics['ppg'], 2) ?></td>
            <?php foreach ($metrics['targets'] as $targetMetrics): ?>
                <td><?= $escapeTable2($targetMetrics['needed']) ?></td>
                <td class="<?= $targetMetrics['status'] === 'Reached' ? 'table2-reached' : ($targetMetrics['status'] === 'Out of reach' ? 'table2-unreachable' : '') ?>"><?= $targetMetrics['status'] === 'In reach' ? number_format($targetMetrics['ppg'], 2) : $targetMetrics['status'] ?></td>
            <?php endforeach; ?>
            <td><?= ($metrics['buffer'] > 0 ? '+' : '') . $escapeTable2($metrics['buffer']) ?></td>
            <td class="table2-projection"><?= $metrics['projection'] === null ? '—' : $escapeTable2($metrics['projection']) ?></td>
            <td><?= $escapeTable2($metrics['wins']) ?></td>
            <td><?= $escapeTable2($metrics['draws']) ?></td>
            <td><?= $escapeTable2($metrics['mixed']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<details class="table2-guide">
    <summary>How to read this table</summary>
    <p><strong>PPG</strong> is points divided by games played. <strong>Buffer</strong> compares points with one point per game. <strong>Projected pts</strong> extends the current PPG to the end of the selected period; it is an estimate, not a maximum. A dash means there are no games played to estimate a pace.</p>
    <p><strong>Pts needed</strong> is the gap to each configured target. <strong>PPG needed</strong> uses games remaining; “Out of reach” means the target exceeds the points available under the active scoring rules. Scenarios use <?= $escapeTable2($resultPoints['win']) ?> points per win and <?= $escapeTable2($resultPoints['draw']) ?> per draw. The 50/50 scenario is a rounded estimate.</p>
</details>
