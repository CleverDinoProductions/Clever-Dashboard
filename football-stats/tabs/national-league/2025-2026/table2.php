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
    $standings = $filteredStandings;
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
    
    <?php
    $table2CompetitionCode = 'NL';
    require __DIR__ . '/../../../includes/table2-table.php';
    ?>
    <?php football_stats_render_points_deductions($tableView['points_deductions']); ?>
    <?php football_stats_render_table_zone_legend('NL', $tableView['active_season_label']); ?>
    
    <div style="margin-top: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
        <div style="background: #40444b; padding: 15px; border-radius: 8px; border-left: 4px solid #faa61a; font-size: 13px;">
            <strong style="color: #FFCD00;">Safety Target:</strong><br>
            <span style="color: #dcddde;">
                <?= $safety_target_halfway ?> pts by Game <?= $halfway_games ?> (Halfway Point)
            </span>
            <p style="color: #bbb; font-size: 11px; margin: 5px 0 0 0;">
                Reference target configured for this league and season.
            </p>
        </div>
    </div>
    <?php football_stats_render_home_away_split($homeStandings, $awayStandings, $team_info); ?>
</div>