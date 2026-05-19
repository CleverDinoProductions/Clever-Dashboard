<?php
require_once dirname(__DIR__, 3) . '/includes/table-view.php';

$tableView = football_stats_get_table_view_combined($db, 'L2', 'league_table_L2', $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$standings = $tableView['standings'];
$last_update = $tableView['last_update'];

// League Two Settings
$halfway_games = 23;
$total_games = 46;
$max_regular_mw = 46; // Playoff matches have matchweek > 46

// Table filter
$table_filter = isset($_GET['table_filter']) && in_array($_GET['table_filter'], ['first_half', 'second_half', 'home', 'away', 'first_half_home', 'first_half_away', 'second_half_home', 'second_half_away'], true) ? $_GET['table_filter'] : 'all';
$_split_season = $tableView['active_season_label'] ?? ($currentMainTab ?? '2025-2026');
$_filter_max_date = ($calcMode === 'by_date' && !empty($tableView['active_date'])) ? $tableView['active_date'] : null;
$_filter_max_mw = ($calcMode === 'by_matchweek' && !empty($tableView['active_matchweek']))
    ? min($max_regular_mw, (int)$tableView['active_matchweek'])
    : $max_regular_mw;
if ($table_filter !== 'all') {
    $filteredStandings = football_stats_compute_filtered_standings($db, 'L2', $_split_season, $table_filter, $halfway_games, 'league_table_L2', $_filter_max_mw, $_filter_max_date, null, $max_regular_mw);
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
$homeStandings = football_stats_compute_filtered_standings($db, 'L2', $_split_season, 'home', $halfway_games, 'league_table_L2', $_filter_max_mw, $_filter_max_date);
$awayStandings = football_stats_compute_filtered_standings($db, 'L2', $_split_season, 'away', $halfway_games, 'league_table_L2', $_filter_max_mw, $_filter_max_date);

$team_info = [
    'Accrington Stanley' => ['name' => 'Accrington Stanley', 'common_name' => 'Accrington', 'nickname' => 'Stanley', 'short' => 'ACC', 'color' => '#D11241'],
    'Barnet' => ['name' => 'Barnet', 'common_name' => 'Barnet', 'nickname' => 'The Barons', 'short' => 'BAR', 'color' => '#000000'],
    'Barrow' => ['name' => 'Barrow', 'common_name' => 'Barrow', 'nickname' => 'The Bluebirds', 'short' => 'BRW', 'color' => '#005DAA'],
    'Bromley' => ['name' => 'Blomley', 'common_name' => 'Blomley', 'nickname' => 'The Bombers', 'short' => 'BLO', 'color' => '#FFB81C'],
    'Bristol Rovers' => ['name' => 'Bristol Rovers', 'common_name' => 'Bristol', 'nickname' => 'The Rovers', 'short' => 'BRS', 'color' => '#FFCD00'],
    'Bromley' => ['name' => 'Bromley', 'common_name' => 'Bromley', 'nickname' => 'The Bombers', 'short' => 'BRW', 'color' => '#FFB81C'],
    'Cambridge United' => ['name' => 'Cambridge United', 'common_name' => 'Cambridge', 'nickname' => 'The U\'s', 'short' => 'CAM', 'color' => '#FFD200'],
    'Cheltenham Town' => ['name' => 'Cheltenham Town', 'common_name' => 'Cheltenham', 'nickname' => 'The Tows', 'short' => 'CHT', 'color' => '#FFCD00'],
    'Chesterfield' => ['name' => 'Chesterfield', 'common_name' => 'Chesterfield', 'nickname' => 'The Spireites', 'short' => 'CHS', 'color' => '#0054A6'],
    'Colchester United' => ['name' => 'Colchester United', 'common_name' => 'Colchester', 'nickname' => 'The U\'s', 'short' => 'COL', 'color' => '#FFD200'],
    'Crawley Town' => ['name' => 'Crawley Town', 'common_name' => 'Crawley', 'nickname' => 'The Town', 'short' => 'CRA', 'color' => '#FFCD00'],
    'Crewe Alexandra' => ['name' => 'Crewe Alexandra', 'common_name' => 'Crewe', 'nickname' => 'The Alexandra', 'short' => 'CRE', 'color' => '#000000'],
    'Fleetwood Town' => ['name' => 'Fleetwood Town', 'common_name' => 'Fleetwood', 'nickname' => 'The Town', 'short' => 'FLE', 'color' => '#FFCD00'],
    'Gillingham' => ['name' => 'Gillingham', 'common_name' => 'Gillingham', 'nickname' => 'The Gills', 'short' => 'GIL', 'color' => '#FFCD00'],
    'Grimsby Town' => ['name' => 'Grimsby Town', 'common_name' => 'Grimsby', 'nickname' => 'The Town', 'short' => 'GRM', 'color' => '#FFCD00'],
    'Harrogate Town' => ['name' => 'Harrogate Town', 'common_name' => 'Harrogate', 'nickname' => 'The Town', 'short' => 'HAR', 'color' => '#FFCD00'],
    'Milton Keynes Dons' => ['name' => 'Milton Keynes Dons', 'common_name' => 'MK Dons', 'nickname' => 'The Dons', 'short' => 'MKD', 'color' => '#FFCD00'],
    'Newport County' => ['name' => 'Newport County', 'common_name' => 'Newport', 'nickname' => 'The County', 'short' => 'NEW', 'color' => '#FFCD00'],
    'Notts County' => ['name' => 'Notts County', 'common_name' => 'Notts', 'nickname' => 'The County', 'short' => 'NOT', 'color' => '#FFCD00'],
    'Oldham Athletic' => ['name' => 'Oldham Athletic', 'common_name' => 'Oldham', 'nickname' => 'The Athletics', 'short' => 'OLD', 'color' => '#FFCD00'],
    'Salford City' => ['name' => 'Salford City', 'common_name' => 'Salford', 'nickname' => 'The City', 'short' => 'SAL', 'color' => '#FFCD00'],
    'Shrewsbury Town' => ['name' => 'Shrewsbury Town', 'common_name' => 'Shrewsbury', 'nickname' => 'The Shrews', 'short' => 'SHR', 'color' => '#FFCD00'],
    'Swindon Town' => ['name' => 'Swindon Town', 'common_name' => 'Swindon', 'nickname' => 'The Town', 'short' => 'SWI', 'color' => '#FFCD00'],
    'Tranmere Rovers' => ['name' => 'Tranemere Rovers', 'common_name' => 'Tranmere', 'nickname' => 'The Rovers', 'short' => 'TRA', 'color' => '#FFCD00'],
    'Walsall' => ['name' => 'Walsall', 'common_name' => 'Walsall', 'nickname' => 'The Wals', 'short' => 'WAL', 'color' => '#FFCD00'],
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
    <h2>League Two Table 2025/26</h2>
    <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', 'league-two', $currentSubTab ?? 'table'); ?>
    <?php football_stats_render_table_filter_buttons($table_filter, $currentMainTab ?? '2025-2026', 'league-two', $currentSubTab ?? 'table'); ?>
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
                
                if ($pos <= 3) {
                    $row_style = 'background: rgba(67, 181, 129, 0.1); border-left: 4px solid #43b581;';
                    $pos_color = '#43b581';
                } elseif ($pos <= 7) {
                    $row_style = 'background: rgba(88, 101, 242, 0.1); border-left: 4px solid #5865F2;';
                    $pos_color = '#5865F2';
                } elseif ($pos >= 23) {
                    $row_style = 'background: rgba(244, 71, 71, 0.15); border-left: 4px solid #f04747;';
                    $pos_color = '#f04747';
                }

                $games_remaining = max(0, ($team['total_expected'] ?? $total_games) - $team['played']);
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