<?php
require_once dirname(__DIR__, 3) . '/includes/table-view.php';

$tableView = football_stats_get_table_view_combined($db, 'NL', 'league_table_NL', $currentMainTab ?? '2025-2026');
$standings = $tableView['standings'];
$movementBaselineStandings = $standings;
$last_update = $tableView['last_update'];
$competitionRules = football_stats_get_competition_rules('NL', $tableView['active_season_label'], $db);
$resultPoints = $tableView['custom_result_points'] ?? ['win' => $competitionRules['win_points'], 'draw' => $competitionRules['draw_points'], 'loss' => $competitionRules['loss_points']];

// Safety calculation Constants
$halfway_games = (int)$competitionRules['halfway_games'];
$safety_target_halfway = (int)$competitionRules['safety_target_halfway'];
$total_games = (int)$competitionRules['total_games'];
$max_regular_mw = (int)$competitionRules['regular_matchweeks'];
if (isset($tableView['active_matchweek'])) {
    $max_regular_mw = min($max_regular_mw, (int)$tableView['active_matchweek']);
}

// Table filter
$table_filter = isset($_GET['table_filter']) && in_array($_GET['table_filter'], ['first_half', 'second_half', 'home', 'away'], true) ? $_GET['table_filter'] : 'all';
$_split_season = $tableView['active_season_label'] ?? ($currentMainTab ?? '2025-2026');
if ($table_filter !== 'all') {
    $filteredStandings = football_stats_compute_filtered_standings($db, 'NL', $_split_season, $table_filter, $halfway_games, 'league_table_NL', $max_regular_mw);
    if (!empty($filteredStandings)) {
        $standings = $filteredStandings;
    }
    $total_games = ($table_filter === 'first_half') ? $halfway_games : (($table_filter === 'second_half') ? ($total_games - $halfway_games) : (int)($total_games / 2));
}
$homeStandings = football_stats_compute_filtered_standings($db, 'NL', $_split_season, 'home', $halfway_games, 'league_table_NL', $max_regular_mw);
$awayStandings = football_stats_compute_filtered_standings($db, 'NL', $_split_season, 'away', $halfway_games, 'league_table_NL', $max_regular_mw);
if (!empty($tableView['points_deductions'])) {
    $standings = football_stats_apply_points_deductions($standings, $tableView['points_deductions']);
    $movementBaselineStandings = football_stats_apply_points_deductions($movementBaselineStandings, $tableView['points_deductions']);
}
$tableView = football_stats_apply_movement_preference(
    $tableView,
    $standings,
    $movementBaselineStandings,
    $table_filter !== 'all' && !empty($filteredStandings)
);

// Team metadata
require_once dirname(__DIR__, 3) . '/includes/team-info.php';
$team_info = $team_info_NL;

?>

<?php require __DIR__ . '/../../../includes/table-styles.php'; ?>

<div class="panel">
    <h2>National League Table <?= $tableView['active_season_label'] ?></h2>
    <?php football_stats_render_table_view_controls($tableView, $currentMainTab ?? '2025-2026', 'national-league', $currentSubTab ?? 'table-2'); ?>
    <?php football_stats_render_table_filter_buttons($table_filter, $currentMainTab ?? '2025-2026', 'national-league', $currentSubTab ?? 'table-2'); ?>
    <p class="update-info">
        <?= htmlspecialchars($tableView['updated_label']) ?>: 
        <?= $last_update['ts'] ? date('Y-m-d H:i:s', $last_update['ts'] / 1000) : 'Updating...' ?>
    </p>
    
    <table class="league-table">
        <thead>
            <tr>
                <th class="movement-column" scope="col">Movement</th>
                <th title="Position">Pos</th>
                <th style="text-align: left;">Team</th>
                <th>P</th>
                <th title="Games remaining">GR</th>
                <th>Pts</th>
                <th>PPG</th>
                <th title="Required points to reach current performance projection">Pts Req</th>
                <th title="PPG needed to reach <?= $competitionRules['comparison_target_one'] ?> points">PPG (<?= $competitionRules['comparison_target_one'] ?>)</th>
                <th title="Points based on current PPG trend">Perf</th>
                <th>+/- Buffer</th>
                <th title="Max points based on performance">Max Pts</th>
                <th title="All Wins">Max(W)</th>
                <th title="All Draws">Max(D)</th>
                <th title="50/50 Wins & Draws">Max(W/D)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($standings as $team): ?>
            <?php
                $info = getTeamInfo($team['team_name'], $team_info);
                $pos = (int)$team['position'];
                
                // Logic Calculations
                $ppg = ($team['played'] > 0) ? round($team['points'] / $team['played'], 2) : 0;
                $games_remaining = max(0, $total_games - $team['played']);
                $buffer = $team['points'] - $team['played'];
                $performance = round($ppg * $games_remaining, 0);
                $max_points_possible = (int)($team['points'] + $performance);
                
                // Points Needed Calculations
                $points_needed_46 = max(0, $competitionRules['comparison_target_one'] - $team['points']);
                $ppg_needed_46 = ($games_remaining > 0) ? round($points_needed_46 / $games_remaining, 2) : 0;
                $points_needed_max = $max_points_possible - $team['points'];

                // Max scenarios
                $max_w = $team['points'] + ($games_remaining * $resultPoints['win']);
                $max_d = $team['points'] + ($games_remaining * $resultPoints['draw']);
                $max_wd = round($team['points'] + ($games_remaining / 2 * ($resultPoints['win'] + $resultPoints['draw'])), 0);

                // Use the Premier League Table 2 colour thresholds.
                $points = $team['points'];
                if ($ppg >= 2) {
                    $ppg_color = '#006400'; // Dark Green for 2+ PPG
                } elseif ($ppg >= 1) {
                    $ppg_color = '#00FF00'; // Green for 1-1.99 PPG
                } elseif ($ppg >= 0.75) {
                    $ppg_color = '#faa61a'; // Orange for 0.75-0.99 PPG
                } elseif ($ppg >= 0.5) {
                    $ppg_color = '#f04747'; // Red for 0.5-0.74 PPG
                } else {
                    $ppg_color = '#8B4513'; // Brown for below 0.5 PPG
                }

                if ($games_remaining == 0) {
                    $games_color = '#808080'; // Grey for 0 games remaining meaning the season is over and the team has no more chances to change their fate as their fate is sealed i.e. Survival, Relegation, etc.
                } elseif ($games_remaining <= 3) {
                    $games_color = '#8B0000'; // Dark Red for 2 or fewer games remaining meaning the season is almost over and the team has little chance to change their fate
                } elseif ($games_remaining <= 6) {
                    $games_color = '#FF0000'; // Red for 2-6 games remaining meaning the season is in the final stages and every game is crucial for the team's survival chances
                } elseif ($games_remaining <= 10) {
                    $games_color = '#FF8C00'; // Dark Orange for 7-10 games remaining meaning the team is in the middle of the season and needs to start picking up points soon to ensure safety
                } elseif ($games_remaining <= 15) {
                    $games_color = '#FFA500'; // Orange for 11-15 games remaining meaning the team is in the early stages of the season and has time to recover but needs to be cautious
                } elseif ($games_remaining <= $competitionRules['total_games'] * 0.25) {
                    $games_color = '#FFD700'; // Dark Yellow for when games remaining is more than 75% of the season meaning the team has plenty of time to improve and should focus on building momentum and confidence
                } elseif ($games_remaining <= $competitionRules['total_games'] * 0.5) {
                    $games_color = '#FFFF00'; // Yellow for when games remaining is less than half the season
                }elseif ($games_remaining <= $competitionRules['total_games'] * 0.75) {
                    $games_color = '#006400'; // Dark Green for when games remaining is more than 50% but less than 75% of the season
                } else {
                    $games_color = '#00FF00'; // Green for more than 75% of games remaining
                }

                if ($points > $team['played']) {
                    $points_color = '#43b581'; // Green if points are greater than games played
                } else if ($points == $team['played']) {
                    $points_color = '#faa61a'; // Orange if points are equal to games played
                } else {
                    $points_color = '#f04747'; // Red if points are less than games played
                }

                if ($buffer > 0) {
                    $buffer_color = '#43b581'; // Green for positive buffer
                } elseif ($buffer < 0) {
                    $buffer_color = '#f04747'; // Red for negative buffer
                } else {
                    $buffer_color = '#888'; // Grey for neutral
                }

                if ($performance >= 10) {
                    $performance_color = '#006400'; // Dark Green for 10+ performance
                } elseif ($performance >= 7) {
                    $performance_color = '#00FF00'; // Green for 7-9.99 performance
                } elseif ($performance >= 5) {
                    $performance_color = '#FFFF00'; // Yellow for 5-6.99 performance
                } elseif ($performance >= 3) {
                    $performance_color = '#faa61a'; // Orange for 3-4.99 performance
                } elseif ($performance >= 0) {
                    $performance_color = '#f04747'; // Red for 0-2.99 performance
                } else {
                    $performance_color = '#8B4513'; // Brown for below 0 performance
                }

                if ($max_points_possible >= 60) {
                    $max_points_color = '#006400'; // Dark Green for 60+ max points possible
                } elseif ($max_points_possible >= 38) {
                    $max_points_color = '#00FF00'; // Green for 38-59 max points possible
                } elseif ($max_points_possible >= 37 || $games_remaining <= 5) {
                    $max_points_color = '#faa61a'; // Orange for 30-37 max points possible or 5 or fewer games remaining
                } elseif ($max_points_possible >= 20 || $games_remaining <= 3) {
                    $max_points_color = '#f04747'; // Red for 20-29 max points possible or 3 or fewer games remaining
                } else {
                    $max_points_color = '#8B4513'; // Brown for below 20 max points possible
                }

                if ($points_needed_46 <= 0) {
                    $points_needed_46_color = '#43b581'; // Green if already at or above 38 points
                } elseif ($points_needed_46 <= 10) {
                    $points_needed_46_color = '#00FF00'; // Green for 1-10 points needed
                } elseif ($points_needed_46 <= 20) {
                    $points_needed_46_color = '#faa61a'; // Orange for 11-20 points needed
                } elseif ($points_needed_46 <= 30) {
                    $points_needed_46_color = '#f04747'; // Red for 21-30 points needed
                } else {
                    $points_needed_46_color = '#8B4513'; // Brown for above 30 points needed
                }

                if ($points_needed_max <= 0) {
                    $points_needed_color = '#43b581'; // Green if already at or above max points possible
                } elseif ($points_needed_max <= 10) {
                    $points_needed_color = '#00FF00'; // Green for 1-10 points needed
                } elseif ($points_needed_max <= 20) {
                    $points_needed_color = '#faa61a'; // Orange for 11-20 points needed
                } elseif ($points_needed_max <= 30) {
                    $points_needed_color = '#f04747'; // Red for 21-30 points needed
                } else {
                    $points_needed_color = '#8B4513'; // Brown for above 30 points needed
                }

                if ($max_w >= 60) {
                    $max_points_win_color = '#006400'; // Dark Green for 60+ max points possible
                } elseif ($max_w >= 40) {
                    $max_points_win_color = '#00FF00'; // Green for 40-59 max points possible
                } elseif ($max_w >= 30) {
                    $max_points_win_color = '#faa61a'; // Orange for 30-39 max points possible
                } elseif ($max_w >= 20) {
                    $max_points_win_color = '#f04747'; // Red for 20-29 max points possible
                } else {
                    $max_points_win_color = '#8B4513'; // Brown for below 40 max points possible
                }

                if ($max_d >= 60) {
                    $max_points_draw_color = '#006400'; // Dark Green for 60+ max points possible
                } elseif ($max_d >= 40) {
                    $max_points_draw_color = '#00FF00'; // Green for 40-59 max points possible
                } elseif ($max_d >= 30) {
                    $max_points_draw_color = '#faa61a'; // Orange for 30-39 max points possible
                } elseif ($max_d >= 20) {
                    $max_points_draw_color = '#f04747'; // Red for 20-29 max points possible
                } else {
                    $max_points_draw_color = '#8B4513'; // Brown for below 40 max points possible
                }
                $max_wd = round($team['points'] + ($games_remaining / 2 * ($resultPoints['win'] + $resultPoints['draw'])), 0);
                if ($max_wd >= 60) {
                    $max_points_win_draw_color = '#006400'; // Dark Green for 100+ max points possible
                } elseif ($max_wd >= 40) {
                    $max_points_win_draw_color = '#00FF00'; // Green for 80-99 max points possible
                } elseif ($max_wd >= 30) {
                    $max_points_win_draw_color = '#faa61a'; // Orange for 60-79 max points possible
                } elseif ($max_wd >= 20) {
                    $max_points_win_draw_color = '#f04747'; // Red for 40-59 max points possible
                } else {
                    $max_points_win_draw_color = '#8B4513'; // Brown for below 40 max points possible
                }

                if ($ppg_needed_46 <= 0.5) {
                    $ppg_needed_46_color = '#006400'; // Dark Green for 2+ PPG needed
                } elseif ($ppg_needed_46 <= 1) {
                    $ppg_needed_46_color = '#00FF00'; // Green for 1-1.99 PPG needed
                } elseif ($ppg_needed_46 <= 2) {
                    $ppg_needed_46_color = '#ffff00'; // Yellow for 2-4 PPG needed
                } elseif ($ppg_needed_46 <= 5) {
                    $ppg_needed_46_color = '#faa61a'; // Orange for 5-6 PPG needed
                } elseif ($ppg_needed_46 <= 10) {
                    $ppg_needed_46_color = '#f04747'; // Red for 7-9 PPG needed
                } else {
                    $ppg_needed_46_color = '#8B4513'; // Brown for 10+ PPG needed
                }
                $show_common = ($team['team_name'] !== $info['common_name']);
            ?>
            <tr <?= football_stats_table_row_attributes('NL', $tableView['active_season_label'], (int)$team['position']) ?>>
                <td class="movement-column"><?php football_stats_render_position_movement($tableView, $team['team_name']); ?></td>
                <td><strong><?= $pos ?></strong></td>
                <td>
                    <div class="team-cell">
                        <img src="<?= htmlspecialchars($team['team_crest']) ?>" class="team-crest" onerror="this.style.visibility='hidden'">
                        <span class="team-name">
                            <span class="team-official"><?= htmlspecialchars($info['common_name']) ?></span>
                            <?php if ($show_common): ?>
                                <span class="team-common">(<?= htmlspecialchars($team['team_name']) ?>)</span>
                            <?php endif; ?>
                            <span class="team-tooltip" style="border-color: <?= $info['color'] ?>;">
                                <span class="tooltip-nickname" style="color: <?= $info['color'] ?>;"><?= $info['nickname'] ?></span>
                                <span class="tooltip-short">Short: <?= $info['short'] ?></span>
                            </span>
                        </span>
                    </div>
                </td>
                <td><?= $team['played'] ?></td>
                <td style="color: <?= $games_color ?>; font-weight: bold;"><?= $games_remaining ?></td>
                <td style="color: <?= $points_color ?>; font-weight: bold;"><?= $team['points'] ?></td>
                <td style="color: <?= $ppg_color ?>; font-weight: bold;"><?= number_format($ppg, 2) ?></td>
                <td style="color: <?= $points_needed_color ?>; font-weight: bold;"><?= $points_needed_max ?></td>
                <td style="color: <?= $ppg_needed_46_color ?>; font-weight: bold;"><?= number_format($ppg_needed_46, 2) ?></td>
                <td style="color: <?= $performance_color ?>; font-weight: bold;"><?= $performance ?></td>
                <td style="color: <?= $buffer_color ?>; font-weight: bold;"><?= ($buffer > 0 ? '+' : '') . $buffer ?></td>
                <td style="color: <?= $max_points_color ?>; font-weight: bold;"><?= $max_points_possible ?></td>
                <td style="color: <?= $max_points_win_color ?>; font-weight: bold;"><?= $max_w ?></td>
                <td style="color: <?= $max_points_draw_color ?>; font-weight: bold;"><?= $max_d ?></td>
                <td style="color: <?= $max_points_win_draw_color ?>; font-weight: bold;"><?= $max_wd ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php football_stats_render_points_deductions($tableView['points_deductions']); ?>
    <?php football_stats_render_table_zone_legend('NL', $tableView['active_season_label']); ?>
    
    <div style="margin-top: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
        <div style="background: #40444b; padding: 15px; border-radius: 8px; border-left: 4px solid #faa61a; font-size: 13px;">
            <strong style="color: #FFCD00;">Safety Target:</strong><br>
            <span style="color: #dcddde;">
                <?= $safety_target_halfway ?> pts by Game <?= $halfway_games ?> (Halfway Point)
            </span>
            <p style="color: #bbb; font-size: 11px; margin: 5px 0 0 0;">
                Teams reaching 25pts by halfway historically have a ~90% survival rate.
            </p>
        </div>
    </div>
    <?php football_stats_render_home_away_split($homeStandings, $awayStandings, $team_info); ?>
</div>