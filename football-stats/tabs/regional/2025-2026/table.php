<?php
require_once dirname(__DIR__, 3) . '/includes/table-view.php';

$tableView = football_stats_get_table_view_combined($db, $regionalCode, $regionalTable, $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$standings = $tableView['standings'];
$movementBaselineStandings = $standings;
$last_update = $tableView['last_update'];
$competitionRules = football_stats_get_competition_rules($regionalCode, $tableView['active_season_label'], $db);

// Regional league Settings
$halfway_games = (int)$competitionRules['halfway_games'];
$total_games = (int)$competitionRules['total_games'];
$max_regular_mw = (int)$competitionRules['regular_matchweeks'];
if (isset($tableView['active_matchweek'])) {
    $max_regular_mw = min($max_regular_mw, (int)$tableView['active_matchweek']);
}

// Table filter
$table_filter = isset($_GET['table_filter']) && in_array($_GET['table_filter'], ['first_half', 'second_half', 'home', 'away'], true) ? $_GET['table_filter'] : 'all';
$_split_season = $tableView['active_season_label'] ?? ($currentMainTab ?? '2025-2026');
if ($table_filter !== 'all') {
    $filteredStandings = football_stats_compute_filtered_standings($db, $regionalCode, $_split_season, $table_filter, $halfway_games, $regionalTable, $max_regular_mw);
    $standings = $filteredStandings;
    $total_games = ($table_filter === 'first_half') ? $halfway_games : (($table_filter === 'second_half') ? ($total_games - $halfway_games) : (int)($total_games / 2));
}
$homeStandings = football_stats_compute_filtered_standings($db, $regionalCode, $_split_season, 'home', $halfway_games, $regionalTable, $max_regular_mw);
$awayStandings = football_stats_compute_filtered_standings($db, $regionalCode, $_split_season, 'away', $halfway_games, $regionalTable, $max_regular_mw);
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

require_once dirname(__DIR__, 3) . '/includes/team-info.php';
$team_info = clever_team_metadata($db, $regionalCode);

?>

<?php require __DIR__ . '/../../../includes/table-styles.php'; ?>

<div class="panel">
    <h2><?= htmlspecialchars($regionalName, ENT_QUOTES, 'UTF-8') ?> Table <?=$tableView['active_season_label']?></h2>
    <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', $currentLeague, $currentSubTab ?? 'table'); ?>
    <?php football_stats_render_table_filter_buttons($table_filter, $currentMainTab ?? '2025-2026', $currentLeague, $currentSubTab ?? 'table'); ?>
    <p class="update-info">
        <?= htmlspecialchars($tableView['updated_label']) ?>: 
        <?= $last_update['ts'] ? date('Y-m-d H:i:s', $last_update['ts'] / 1000) : 'Updating...' ?>
    </p>
    
    <?php
    $standingsCompetitionCode = $regionalCode;
    require __DIR__ . '/../../../includes/standings-table.php';
    ?>
    <?php football_stats_render_points_deductions($tableView['points_deductions']); ?>
    <?php football_stats_render_table_zone_legend($regionalCode, $tableView['active_season_label']); ?>
    

    <?php football_stats_render_home_away_split($homeStandings, $awayStandings, $team_info); ?>
</div>