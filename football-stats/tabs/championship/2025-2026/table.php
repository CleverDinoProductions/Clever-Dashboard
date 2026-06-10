<?php
require_once dirname(__DIR__, 3) . '/includes/table-view.php';

$tableView = football_stats_get_table_view_combined($db, 'ELC', 'league_table_ELC', $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$standings = $tableView['standings'];
$last_update = $tableView['last_update'];

// Championship Settings
$halfway_games = 23;
$total_games = 46;
$max_regular_mw = 46; // Playoff matches have matchweek > 46
if (isset($tableView['active_matchweek'])) {
    $max_regular_mw = min($max_regular_mw, (int)$tableView['active_matchweek']);
}

// Table filter
$table_filter = isset($_GET['table_filter']) && in_array($_GET['table_filter'], ['first_half', 'second_half', 'home', 'away'], true) ? $_GET['table_filter'] : 'all';
$_split_season = $tableView['active_season_label'] ?? ($currentMainTab ?? '2025-2026');
if ($table_filter !== 'all') {
    $filteredStandings = football_stats_compute_filtered_standings($db, 'ELC', $_split_season, $table_filter, $halfway_games, 'league_table_ELC', $max_regular_mw);
    if (!empty($filteredStandings)) {
        $standings = $filteredStandings;
    }
    $total_games = ($table_filter === 'first_half') ? $halfway_games : (($table_filter === 'second_half') ? ($total_games - $halfway_games) : (int)($total_games / 2));
}
$homeStandings = football_stats_compute_filtered_standings($db, 'ELC', $_split_season, 'home', $halfway_games, 'league_table_ELC', $max_regular_mw);
$awayStandings = football_stats_compute_filtered_standings($db, 'ELC', $_split_season, 'away', $halfway_games, 'league_table_ELC', $max_regular_mw);

// Team metadata
require_once dirname(__DIR__, 3) . '/includes/team-info.php';
$team_info = $team_info_ELC;

?>

<style>
.team-name { position: relative; cursor: help; display: inline-block; transition: color 0.2s ease; }
.team-name:hover { color: #FFCD00; }
.team-official { color: #dcddde; }
.team-common { color: #888; font-size: 12px; margin-left: 6px; font-weight: normal; }
.team-tooltip { visibility: hidden; opacity: 0; position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #2e3136, #40444b); color: white; padding: 10px 15px; border-radius: 8px; white-space: nowrap; z-index: 1000; font-size: 13px; border: 2px solid; box-shadow: 0 4px 12px rgba(0,0,0,0.5); transition: opacity 0.3s ease, visibility 0.3s ease; }
.team-tooltip::after { content: ""; position: absolute; top: 100%; left: 50%; transform: translateX(-50%); border: 6px solid transparent; border-top-color: inherit; }
.team-name:hover .team-tooltip { visibility: visible; opacity: 1; }
.tooltip-nickname { display: block; font-weight: bold; font-size: 14px; margin-bottom: 3px; }
.tooltip-short { display: block; font-size: 11px; color: #dcddde; }

th { position: sticky; top: 0; z-index: 10; background-color: #222; color: white; white-space: nowrap; border-bottom: 2px solid #444; padding: 10px; }
table { width: 100%; border-collapse: collapse; }
td { padding: 10px; border-bottom: 1px solid #333; text-align: center; }
.team-crest { width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 10px; }
.team-cell { display: flex; align-items: center; text-align: left; }
.update-info { font-size: 12px; color: #888; margin-bottom: 10px; }
</style>

<div class="panel">
    <h2>Championship Table 2025/26</h2>
    <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', 'championship', $currentSubTab ?? 'table'); ?>
    <?php football_stats_render_table_filter_buttons($table_filter, $currentMainTab ?? '2025-2026', 'championship', $currentSubTab ?? 'table'); ?>
    <p class="update-info">
        <?= htmlspecialchars($tableView['updated_label']) ?>: 
        <?= $last_update['ts'] ? date('Y-m-d H:i:s', $last_update['ts'] / 1000) : 'Updating...' ?>
    </p>
    
    <table>
        <thead>
            <tr>
                <th title="Position">Pos</th>
                <th title="Team" style="text-align: left;">Team</th>
                <th>P</th>
                <th title="Games remaining">GR</th>
                <th>Pts</th>
                <th>W</th>
                <th>D</th>
                <th>L</th>
                <th>GF</th>
                <th>GA</th>
                <th>GD</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($standings as $team): ?>
            <?php
                $info = getTeamInfo($team['team_name'], $team_info);
                $pos = (int)$team['position'];
                
                // FIXED ROW HIGHLIGHTING (Works regardless of row count)
                $row_style = '';
                $pos_color = '#dcddde'; // Default color for position number
                $is_leeds = stripos($team['team_name'], 'Leeds') !== false;
                
                if ($is_leeds) {
                    $row_style = 'background: rgba(29, 66, 138, 0.3); border-left: 4px solid #FFFFFF; border-right: 4px solid #FFCD00;'; // Blue and Yellow for Leeds United
                    $pos_color = '#FFCD00'; // Yellow for Leeds United
                } elseif ($pos <= 2) {
                    $row_style = 'background: rgba(67, 181, 129, 0.1); border-left: 4px solid #43b581;';
                    $pos_color = '#43b581';
                } elseif ($pos <= 6) {
                    $row_style = 'background: rgba(88, 101, 242, 0.1); border-left: 4px solid #5865F2;';
                    $pos_color = '#5865F2';
                } elseif ($pos >= 22) {
                    $row_style = 'background: rgba(244, 71, 71, 0.15); border-left: 4px solid #f04747;';
                    $pos_color = '#f04747';
                }

                $games_remaining = max(0, $total_games - $team['played']);
                $show_common = ($team['team_name'] !== $info['common_name']);
            ?>
            <tr style="<?= $row_style ?>">
                <td style="color: <?= $pos_color ?>; font-weight: bold;"><?= $pos ?></td>
                <td>
                    <div class="team-cell">
                    <img src="<?= htmlspecialchars($team['team_crest']) ?>" 
                        alt="<?= htmlspecialchars($info['name']) ?> crest" 
                        class="team-crest"
                        onerror="this.style.display='none'"> <span class="team-name">
                        <span class="team-official">
                            <?= htmlspecialchars($info['name']) ?>
                        </span>
                        <?php if ($show_common): ?>
                            <span class="team-common">(<?= $team['team_name'] ?>)</span>
                        <?php endif; ?>
                
                        <span class="team-tooltip" style="border-color: <?= $info['color'] ?>;">
                            <span class="tooltip-nickname" style="color: <?= $info['color'] ?>;">
                                <?= $info['nickname'] ?>
                            </span>
                            <span class="tooltip-short">
                                Abbreviated: <?= $info['short'] ?>
                            </span>
                        </span>
                    </span>
                </div>
                </td>
                <td><?= $team['played'] ?></td>
                <td style="font-weight: bold; opacity: 0.8;"><?= $games_remaining ?></td>
                <td style="font-weight: bold;"><?= $team['points'] ?></td>
                <td><?= $team['won'] ?></td>
                <td><?= $team['drawn'] ?></td>
                <td><?= $team['lost'] ?></td>
                <td><?= $team['gf'] ?></td>
                <td><?= $team['ga'] ?></td>
                <td style="font-weight: bold; color: <?= $team['gd'] >= 0 ? '#43b581' : '#f04747' ?>;">
                    <?= $team['gd'] > 0 ? '+' . $team['gd'] : $team['gd'] ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 20px; display: flex; gap: 20px; font-size: 12px; flex-wrap: wrap; background: #1a1c1e; padding: 15px; border-radius: 8px;">
        <div><span style="color: #43b581;">■</span> Automatic Promotion (1st-2nd)</div>
        <div><span style="color: #5865F2;">■</span> Playoffs (3rd-6th)</div>
        <div><span style="color: #f04747;">■</span> Relegation (22nd-24th)</div>
        <div style="margin-left: auto; color: #888;">💡 Hover team names for details</div>
    </div>
    <?php football_stats_render_home_away_split($homeStandings, $awayStandings, $team_info); ?>
</div>