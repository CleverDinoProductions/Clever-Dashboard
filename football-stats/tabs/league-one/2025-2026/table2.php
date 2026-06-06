<?php
require_once dirname(__DIR__, 3) . '/includes/table-view.php';

$tableView = football_stats_get_table_view($db, 'L1', 'league_table_L1', $currentMainTab ?? '2025-2026');
$standings = $tableView['standings'];
$last_update = $tableView['last_update'];

// Safety calculation Constants
$halfway_games = 23;
$safety_target_halfway = 25;
$total_games = 46;
$max_regular_mw = 46; // Playoff matches have matchweek > 46
if (isset($tableView['active_matchweek'])) {
    $max_regular_mw = min($max_regular_mw, (int)$tableView['active_matchweek']);
}

// Table filter
$table_filter = isset($_GET['table_filter']) && in_array($_GET['table_filter'], ['first_half', 'second_half', 'home', 'away'], true) ? $_GET['table_filter'] : 'all';
$_split_season = $tableView['active_season_label'] ?? ($currentMainTab ?? '2025-2026');
if ($table_filter !== 'all') {
    $filteredStandings = football_stats_compute_filtered_standings($db, 'L1', $_split_season, $table_filter, $halfway_games, 'league_table_L1', $max_regular_mw);
    if (!empty($filteredStandings)) {
        $standings = $filteredStandings;
    }
    $total_games = ($table_filter === 'first_half') ? $halfway_games : (($table_filter === 'second_half') ? ($total_games - $halfway_games) : (int)($total_games / 2));
}
$homeStandings = football_stats_compute_filtered_standings($db, 'L1', $_split_season, 'home', $halfway_games, 'league_table_L1', $max_regular_mw);
$awayStandings = football_stats_compute_filtered_standings($db, 'L1', $_split_season, 'away', $halfway_games, 'league_table_L1', $max_regular_mw);
$safety_target_magic = 40; 
$safety_target_average = 36; 
$safety_target_low = 34; 
$safety_target_recent_low = 27;

// Team metadata
$team_info = [
    'Barnsley' => ['name' => 'Barnsley', 'common_name' => 'Barnsley', 'nickname' => 'The Tykes', 'short' => 'BAR', 'color' => '#E81734'],
    'Bolton Wanderers' => ['name' => 'Bolton Wanderers', 'common_name' => 'Bolton', 'nickname' => 'The Trotters', 'short' => 'BOL', 'color' => '#FFFFFF'],
    'Blackpool' => ['name' => 'Blackpool', 'common_name' => 'Blackpool', 'nickname' => 'The Seasiders', 'short' => 'BLP', 'color' => '#F68712'],
    'Burton Albion' => ['name' => 'Burton Albion', 'common_name' => 'Burton', 'nickname' => 'The Brewers', 'short' => 'BUR', 'color' => '#FFD200'],
    'Cambridge United' => ['name' => 'Cambridge United', 'common_name' => 'Cambridge', 'nickname' => 'The U\'s', 'short' => 'CAM', 'color' => '#FFD200'],
    'Exeter City' => ['name' => 'Exeter City', 'common_name' => 'Exeter', 'nickname' => 'The Grecians', 'short' => 'EXE', 'color' => '#E81734'],
    'Huddersfield Town' => ['name' => 'Huddersfield Town', 'common_name' => 'Huddersfield', 'nickname' => 'The Terriers', 'short' => 'HUD', 'color' => '#0072CE'],
    'Leyton Orient' => ['name' => 'Leyton Orient', 'common_name' => 'Orient', 'nickname' => 'The O\'s', 'short' => 'LEY', 'color' => '#E81734'],
    'Lincoln City' => ['name' => 'Lincoln City', 'common_name' => 'Lincoln', 'nickname' => 'The Imps', 'short' => 'LIN', 'color' => '#D11241'],
    'Mansfield Town' => ['name' => 'Mansfield Town', 'common_name' => 'Mansfield', 'nickname' => 'The Stags', 'short' => 'MAN', 'color' => '#FDBE11'],
    'Northampton Town' => ['name' => 'Northampton Town', 'common_name' => 'Northampton', 'nickname' => 'The Cobblers', 'short' => 'NOR', 'color' => '#7B1639'],
    'Peterborough United' => ['name' => 'Peterborough United', 'common_name' => 'Peterborough', 'nickname' => 'The Posh', 'short' => 'PET', 'color' => '#0054A6'],
    'Reading' => ['name' => 'Reading', 'common_name' => 'Reading', 'nickname' => 'The Royals', 'short' => 'REA', 'color' => '#004494'],
    'Rotherham United' => ['name' => 'Rotherham United', 'common_name' => 'Rotherham', 'nickname' => 'The Millers', 'short' => 'ROT', 'color' => '#D00027'],
    'Shrewsbury Town' => ['name' => 'Shrewsbury Town', 'common_name' => 'Shrewsbury', 'nickname' => 'The Shrews', 'short' => 'SHR', 'color' => '#FFC20E'],
    'Stevenage' => ['name' => 'Stevenage', 'common_name' => 'Stevenage', 'nickname' => 'The Boro', 'short' => 'STE', 'color' => '#E30613'],
    'Stockport County' => ['name' => 'Stockport County', 'common_name' => 'Stockport', 'nickname' => 'The Hatters', 'short' => 'STP', 'color' => '#004A99'],
    'Wigan Athletic' => ['name' => 'Wigan Athletic', 'common_name' => 'Wigan', 'nickname' => 'The Latics', 'short' => 'WIG', 'color' => '#0033A0'],
    'Wycombe Wanderers' => ['name' => 'Wycombe Wanderers', 'common_name' => 'Wycombe', 'nickname' => 'The Chairboys', 'short' => 'WYC', 'color' => '#002D56'],
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
.team-common { color: #888; font-size: 11px; margin-left: 4px; font-weight: normal; }
.team-tooltip { visibility: hidden; opacity: 0; position: absolute; bottom: 125%; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #2e3136, #40444b); color: white; padding: 10px 15px; border-radius: 8px; white-space: nowrap; z-index: 1000; font-size: 13px; border: 2px solid; box-shadow: 0 4px 12px rgba(0,0,0,0.5); transition: opacity 0.3s ease, visibility 0.3s ease; }
.team-tooltip::after { content: ""; position: absolute; top: 100%; left: 50%; transform: translateX(-50%); border: 6px solid transparent; border-top-color: inherit; }
.team-name:hover .team-tooltip { visibility: visible; opacity: 1; }
.tooltip-nickname { display: block; font-weight: bold; font-size: 14px; margin-bottom: 3px; }
.tooltip-short { display: block; font-size: 11px; color: #dcddde; }

th { position: sticky; top: 0; z-index: 10; background-color: #222; color: white; white-space: nowrap; border-bottom: 2px solid #444; padding: 12px 8px; font-size: 12px; }
table { width: 100%; border-collapse: collapse; }
td { padding: 10px 8px; border-bottom: 1px solid #333; text-align: center; font-size: 13px; color: #dcddde; }
.team-crest { width: 22px; height: 22px; object-fit: contain; vertical-align: middle; margin-right: 10px; }
.team-cell { display: flex; align-items: center; text-align: left; }
.update-info { font-size: 12px; color: #888; margin-bottom: 10px; }
</style>

<div class="panel">
    <h2>League One Table 2025/26</h2>
    <?php football_stats_render_table_view_controls($tableView, $currentMainTab ?? '2025-2026', 'league-one', $currentSubTab ?? 'table-2'); ?>
    <?php football_stats_render_table_filter_buttons($table_filter, $currentMainTab ?? '2025-2026', 'league-one', $currentSubTab ?? 'table-2'); ?>
    <p class="update-info">
        <?= htmlspecialchars($tableView['updated_label']) ?>: 
        <?= $last_update['ts'] ? date('Y-m-d H:i:s', $last_update['ts'] / 1000) : 'Updating...' ?>
    </p>
    
    <table>
        <thead>
            <tr>
                <th title="Position">Pos</th>
                <th style="text-align: left;">Team</th>
                <th>P</th>
                <th title="Games remaining">GR</th>
                <th>Pts</th>
                <th>PPG</th>
                <th title="Required points to reach current performance projection">Pts Req</th>
                <th title="PPG needed to reach 46 points">PPG (46)</th>
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
                $points_needed_46 = max(0, 46 - $team['points']);
                $ppg_needed_46 = ($games_remaining > 0) ? round($points_needed_46 / $games_remaining, 2) : 0;
                $points_needed_max = $max_points_possible - $team['points'];

                // Max scenarios
                $max_w = $team['points'] + ($games_remaining * 3);
                $max_d = $team['points'] + ($games_remaining * 1);
                $max_wd = round($team['points'] + ($games_remaining / 2 * (3 + 1)), 0);

                // Row Highlighting Logic
                $row_style = '';
                if ($pos <= 2) {
                    $row_style = 'background: rgba(67, 181, 129, 0.1); border-left: 4px solid #43b581;';
                } elseif ($pos <= 6) {
                    $row_style = 'background: rgba(88, 101, 242, 0.1); border-left: 4px solid #5865F2;';
                } elseif ($pos >= 21) {
                    $row_style = 'background: rgba(244, 71, 71, 0.15); border-left: 4px solid #f04747;';
                }

                // Dynamic Coloring
                $ppg_color = ($ppg >= 1.5) ? '#43b581' : (($ppg >= 1.0) ? '#faa61a' : '#f04747');
                $buffer_color = ($buffer > 0) ? '#43b581' : (($buffer < 0) ? '#f04747' : '#888');
                $show_common = ($team['team_name'] !== $info['common_name']);
            ?>
            <tr style="<?= $row_style ?>">
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
                <td style="opacity: 0.7;"><?= $games_remaining ?></td>
                <td style="font-weight: bold; color: #5865F2;"><?= $team['points'] ?></td>
                <td style="color: <?= $ppg_color ?>;"><?= number_format($ppg, 2) ?></td>
                <td><?= $points_needed_max ?></td>
                <td style="font-weight: bold;"><?= number_format($ppg_needed_46, 2) ?></td>
                <td><?= $performance ?></td>
                <td style="color: <?= $buffer_color ?>;"><?= ($buffer > 0 ? '+' : '') . $buffer ?></td>
                <td style="font-weight: bold;"><?= $max_points_possible ?></td>
                <td style="color: #43b581;"><?= $max_w ?></td>
                <td style="color: #faa61a;"><?= $max_d ?></td>
                <td style="color: #5865F2;"><?= $max_wd ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
        <div style="background: #1a1c1e; padding: 15px; border-radius: 8px; font-size: 12px;">
            <div style="margin-bottom: 5px;"><span style="color: #43b581;">■</span> Automatic Promotion (1st-2nd)</div>
            <div style="margin-bottom: 5px;"><span style="color: #5865F2;">■</span> Playoffs (3rd-6th)</div>
            <div><span style="color: #f04747;">■</span> Relegation Zone (22nd-24th)</div>
        </div>
        
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