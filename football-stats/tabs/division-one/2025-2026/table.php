<?php
require_once __DIR__ . '/../../../includes/table-view.php';

$tableView = football_stats_get_table_view_combined($db, 'D1', 'league_table_D1', $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$standings = $tableView['standings'];
$movementBaselineStandings = $standings;
$last_update = $tableView['last_update'];
$competitionRules = football_stats_get_competition_rules('D1', $tableView['active_season_label'], $db);

// Safety calculation
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
    $filteredStandings = football_stats_compute_filtered_standings($db, 'D1', $_split_season, $table_filter, $halfway_games, 'league_table_D1', $max_regular_mw);
    $standings = $filteredStandings;
    $total_games = ($table_filter === 'first_half') ? $halfway_games : (($table_filter === 'second_half') ? ($total_games - $halfway_games) : (int)($total_games / 2));
}
$homeStandings = football_stats_compute_filtered_standings($db, 'D1', $_split_season, 'home', $halfway_games, 'league_table_D1', $max_regular_mw);
$awayStandings = football_stats_compute_filtered_standings($db, 'D1', $_split_season, 'away', $halfway_games, 'league_table_D1', $max_regular_mw);
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

// Team nicknames, abbreviations, and COMMON NAMES
require_once __DIR__ . '/../../../includes/team-info.php';
$team_info = $team_info_PL;

?>

<?php require __DIR__ . '/../../../includes/table-styles.php'; ?>

<div class="panel">
    <h2>Division One Table <?= $tableView['active_season_label']?></h2>
    <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', $currentLeague ?? 'division-one', $currentSubTab ?? 'table'); ?>
    <?php football_stats_render_table_filter_buttons($table_filter, $currentMainTab ?? '2025-2026', $currentLeague ?? 'division-one', $currentSubTab ?? 'table'); ?>
    <p class="update-info">
        <?= htmlspecialchars($tableView['updated_label'], ENT_QUOTES, 'UTF-8') ?>:
        <?= $last_update['ts'] ? date('Y-m-d H:i:s', $last_update['ts'] / 1000) : 'No data available yet' ?>
    </p>
    
    <?php
    $standingsCompetitionCode = 'D1';
    require __DIR__ . '/../../../includes/standings-table.php';
    ?>
    <?php football_stats_render_points_deductions($tableView['points_deductions']); ?>
    <?php football_stats_render_table_zone_legend('D1', $tableView['active_season_label']); ?>
    
    <div style="margin-top: 20px; display: flex; gap: 20px; font-size: 12px; flex-wrap: wrap;">
        <div style="margin-left: auto; color: #888;">
            Team nicknames and abbreviations appear below each name.
        </div>
    </div>
    
    <div style="margin-top: 15px; padding: 15px; background: #40444b; border-radius: 8px; border-left: 4px solid #5865F2;">
        <strong style="color: #FFCD00;">Safety Target:</strong>
        <span style="color: #dcddde;">
            <?= $safety_target_halfway ?> points by game <?= $halfway_games ?> (halfway point)
        </span>
        <br>
        <span style="color: #888; font-size: 12px;">
            Reference target configured for this league and season.
        </span>
    </div>
    <?php football_stats_render_home_away_split($homeStandings, $awayStandings, $team_info); ?>
</div>
