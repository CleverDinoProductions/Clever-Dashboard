<?php
require_once dirname(__DIR__, 3) . '/includes/table-view.php';

$tableView = football_stats_get_table_view_combined($db, 'NL', 'league_table_NL', $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$standings = $tableView['standings'];
$last_update = $tableView['last_update'];

// National League Settings
$halfway_games = 23;
$total_games = 46;
$max_regular_mw = 46; // Playoff matches have matchweek > 46

// Table filter
$table_filter = isset($_GET['table_filter']) && in_array($_GET['table_filter'], ['first_half', 'second_half', 'home', 'away', 'first_half_home', 'first_half_away', 'second_half_home', 'second_half_away'], true) ? $_GET['table_filter'] : 'all';
$_split_season = $tableView['active_season_label'] ?? ($currentMainTab ?? '2025-2026');
$_filter_max_date = ($calcMode === 'by_date' && !empty($tableView['active_date'])) ? $tableView['active_date'] : null;
if ($table_filter !== 'all') {
    $filteredStandings = football_stats_compute_filtered_standings($db, 'NL', $_split_season, $table_filter, $halfway_games, 'league_table_NL', $max_regular_mw, $_filter_max_date);
    if (!empty($filteredStandings)) {
        $standings = $filteredStandings;
    }
    $_second_half_games = $total_games - $halfway_games;
    if ($table_filter === 'first_half') {
        $total_games = $halfway_games;
    } elseif ($table_filter === 'second_half') {
        $total_games = $_second_half_games;
    } elseif ($table_filter === 'home' || $table_filter === 'away') {
        $total_games = (int)($total_games / 2);
    } elseif ($table_filter === 'first_half_home' || $table_filter === 'first_half_away') {
        $total_games = (int)ceil($halfway_games / 2);
    } else {
        $total_games = (int)ceil($_second_half_games / 2);
    }
}
$homeStandings = football_stats_compute_filtered_standings($db, 'NL', $_split_season, 'home', $halfway_games, 'league_table_NL', $max_regular_mw, $_filter_max_date);
$awayStandings = football_stats_compute_filtered_standings($db, 'NL', $_split_season, 'away', $halfway_games, 'league_table_NL', $max_regular_mw, $_filter_max_date);

$team_info = [
    'Aldershot Town' => ['name' => 'Aldershot Town', 'common_name' => 'Aldershot', 'nickname' => 'The Shots', 'short' => 'ALD', 'color' => '#E30613'],
    'Altrincham' => ['name' => 'Altrincham', 'common_name' => 'Alty', 'nickname' => 'The Robins', 'short' => 'ALT', 'color' => '#E30613'],
    'Barnet' => ['name' => 'Barnet', 'common_name' => 'Barnet', 'nickname' => 'The Bees', 'short' => 'BRN', 'color' => '#FDBE11'],
    'Braintree Town' => ['name' => 'Braintree Town', 'common_name' => 'Braintree', 'nickname' => 'The Iron', 'short' => 'BRA', 'color' => '#FDBE11'],
    'Dagenham & Redbridge' => ['name' => 'Dagenham & Redbridge', 'common_name' => 'Daggers', 'nickname' => 'The Daggers', 'short' => 'DAG', 'color' => '#E30613'],
    'Eastleigh' => ['name' => 'Eastleigh', 'common_name' => 'Eastleigh', 'nickname' => 'The Spitfires', 'short' => 'EAS', 'color' => '#005DAA'],
    'Ebbsfleet United' => ['name' => 'Ebbsfleet United', 'common_name' => 'Ebbsfleet', 'nickname' => 'The Fleet', 'short' => 'EBB', 'color' => '#E30613'],
    'FC Halifax Town' => ['name' => 'FC Halifax Town', 'common_name' => 'Halifax', 'nickname' => 'The Shaymen', 'short' => 'HAL', 'color' => '#005DAA'],
    'Forest Green Rovers' => ['name' => 'Forest Green Rovers', 'common_name' => 'Forest Green', 'nickname' => 'The Green', 'short' => 'FGR', 'color' => '#00FF00'],
    'Gateshead' => ['name' => 'Gateshead', 'common_name' => 'Gateshead', 'nickname' => 'The Heed', 'short' => 'GAT', 'color' => '#FFFFFF'],
    'Hartlepool United' => ['name' => 'Hartlepool United', 'common_name' => 'Hartlepool', 'nickname' => 'The Monkey Hangers', 'short' => 'HAR', 'color' => '#005DAA'],
    'Maidenhead United' => ['name' => 'Maidenhead United', 'common_name' => 'Maidenhead', 'nickname' => 'The Magpies', 'short' => 'MAI', 'color' => '#000000'],
    'Oldham Athletic' => ['name' => 'Oldham Athletic', 'common_name' => 'Oldham', 'nickname' => 'The Latics', 'short' => 'OLD', 'color' => '#005DAA'],
    'Rochdale' => ['name' => 'Rochdale', 'common_name' => 'Rochdale', 'nickname' => 'The Dale', 'short' => 'ROC', 'color' => '#005DAA'],
    'Solihull Moors' => ['name' => 'Solihull Moors', 'common_name' => 'Solihull', 'nickname' => 'The Moors', 'short' => 'SOL', 'color' => '#FFD200'],
    'Southend United' => ['name' => 'Southend United', 'common_name' => 'Southend', 'nickname' => 'The Shrimpers', 'short' => 'SOU', 'color' => '#002D56'],
    'Sutton United' => ['name' => 'Sutton United', 'common_name' => 'Sutton', 'nickname' => 'The Yellows', 'short' => 'SUT', 'color' => '#FFD200'],
    'Tamworth' => ['name' => 'Tamworth', 'common_name' => 'Tamworth', 'nickname' => 'The Lambs', 'short' => 'TAM', 'color' => '#E30613'],
    'Wealdstone' => ['name' => 'Wealdstone', 'common_name' => 'Wealdstone', 'nickname' => 'The Stones', 'short' => 'WEA', 'color' => '#0000FF'],
    'Woking' => ['name' => 'Woking', 'common_name' => 'Woking', 'nickname' => 'The Cards', 'short' => 'WOK', 'color' => '#E30613'],
    'Yeovil Town' => ['name' => 'Yeovil Town', 'common_name' => 'Yeovil', 'nickname' => 'The Glovers', 'short' => 'YEO', 'color' => '#008000'],
    'York City' => ['name' => 'York City', 'common_name' => 'York', 'nickname' => 'The Minstermen', 'short' => 'YOR', 'color' => '#E30613'],
];

function getTeamInfo($team_name, $team_info) {
    if (isset($team_info[$team_name])) return $team_info[$team_name];
    foreach ($team_info as $key => $info) {
        if (stripos($team_name, $key) !== false) return $info;
    }
    return ['name' => $team_name, 'common_name' => $team_name, 'nickname' => 'Unknown', 'short' => strtoupper(substr($team_name, 0, 3)), 'color' => '#888888'];
}
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
    <h2>National League Table 2025/26</h2>
    <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', 'national-league', $currentSubTab ?? 'table'); ?>
    <?php football_stats_render_table_filter_buttons($table_filter, $currentMainTab ?? '2025-2026', 'national-league', $currentSubTab ?? 'table'); ?>
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
                
                if ($pos <= 1) {
                    $row_style = 'background: rgba(67, 181, 129, 0.1); border-left: 4px solid #43b581;';
                    $pos_color = '#43b581';
                } elseif ($pos <= 7) {
                    $row_style = 'background: rgba(88, 101, 242, 0.1); border-left: 4px solid #5865F2;';
                    $pos_color = '#5865F2';
                } elseif ($pos >= 21) {
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