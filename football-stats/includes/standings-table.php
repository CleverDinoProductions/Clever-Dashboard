<?php
$escapeStandings = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
require __DIR__ . '/standings-table-styles.php';
?>
<p class="standings-hint">Scroll across to compare results and goals. All figures use the selected table period.</p>
<div class="standings-scroll" role="region" aria-label="League standings, results and goals" tabindex="0">
<table class="league-table readable-table standings-table">
    <caption>League standings · <?= $escapeStandings($tableView['active_season_label']) ?> · <?= $escapeStandings(ucwords(str_replace('_', ' ', $table_filter))) ?> matches</caption>
    <thead>
        <tr class="standings-groups">
            <th colspan="3" scope="colgroup">Position &amp; team</th>
            <th colspan="3" scope="colgroup">Table progress</th>
            <th colspan="3" scope="colgroup">Match results</th>
            <th colspan="3" scope="colgroup">Goals</th>
        </tr>
        <tr>
            <th scope="col" title="Position change against the selected comparison">Move</th>
            <th scope="col" title="Position">Pos</th>
            <th scope="col" class="standings-team">Team</th>
            <th scope="col" class="standings-group-start" title="Matches played">Played</th>
            <th scope="col" title="Matches remaining in the selected period">Left</th>
            <th scope="col" title="Current points, including applicable deductions">Points</th>
            <th scope="col" class="standings-group-start" title="Matches won">Won</th>
            <th scope="col" title="Matches drawn">Drawn</th>
            <th scope="col" title="Matches lost">Lost</th>
            <th scope="col" class="standings-group-start" title="Goals scored">For</th>
            <th scope="col" title="Goals conceded">Against</th>
            <th scope="col" title="Goals scored minus goals conceded">GD</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($standings)): ?>
        <tr><td colspan="12" class="standings-empty">No completed matches available for this selection.</td></tr>
    <?php endif; ?>
    <?php foreach ($standings as $team): ?>
        <?php
        $info = getTeamInfo($team['team_name'], $team_info);
        $games_remaining = max(0, $total_games - (int)$team['played']);
        $goalDifference = (int)$team['gd'];
        ?>
        <tr <?= football_stats_table_row_attributes($standingsCompetitionCode, $tableView['active_season_label'], (int)$team['position']) ?>>
            <td class="movement-column"><?php if (empty($tableView['position_movements'][$team['team_name']])): ?><span aria-label="No position change">—</span><?php else: ?><?php football_stats_render_position_movement($tableView, $team['team_name']); ?><?php endif; ?></td>
            <td><?= (int)$team['position'] ?></td>
            <th scope="row" class="standings-team">
                <div class="team-cell">
                    <?php if (!empty($team['team_crest'])): ?><img src="<?= $escapeStandings($team['team_crest']) ?>" alt="" class="team-crest" loading="lazy" onerror="this.style.display='none'"><?php endif; ?>
                    <span><span class="standings-team-name"><?= $escapeStandings($team['team_name']) ?></span><small><?= $escapeStandings($info['nickname']) ?> · <?= $escapeStandings($info['short']) ?></small></span>
                </div>
            </th>
            <td class="standings-group-start"><?= (int)$team['played'] ?></td>
            <td><?= $games_remaining ?></td>
            <td class="standings-points"><?= $escapeStandings($team['points']) ?></td>
            <td class="standings-group-start"><?= (int)$team['won'] ?></td>
            <td><?= (int)$team['drawn'] ?></td>
            <td><?= (int)$team['lost'] ?></td>
            <td class="standings-group-start"><?= (int)$team['gf'] ?></td>
            <td><?= (int)$team['ga'] ?></td>
            <td class="<?= $goalDifference > 0 ? 'standings-positive' : ($goalDifference < 0 ? 'standings-negative' : '') ?>"><?= ($goalDifference > 0 ? '+' : '') . $goalDifference ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<details class="standings-guide">
    <summary>How to read this table</summary>
    <p><strong>Move</strong> shows position changes against the selected comparison. A dash means no position change. <strong>Left</strong> is the number of games remaining in the selected period. <strong>Points</strong> includes applicable points deductions, which are listed below the table.</p>
    <p><strong>Won, Drawn and Lost</strong> count match results. <strong>For</strong> and <strong>Against</strong> show goals scored and conceded; <strong>GD</strong> is the difference, with a plus or minus sign. Coloured edges identify league positions using the legend below.</p>
</details>
