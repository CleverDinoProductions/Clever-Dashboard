<?php
require_once dirname(__DIR__, 3) . '/includes/table-view.php';

$tableView = football_stats_get_table_view($db, 'NL', 'league_table_NL', $currentMainTab ?? '2025-2026');
$standings = $tableView['standings'];
$last_update = $tableView['last_update'];

// Safety calculation Constants
$halfway_games = 23; 
$safety_target_halfway = 25; 
$total_games = 46; 
$safety_target_magic = 40; 
$safety_target_average = 36; 
$safety_target_low = 34; 
$safety_target_recent_low = 27;

// Team metadata
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
    'Scunthorpe United' => ['name' => 'Scunthorpe United', 'common_name' => 'Scunthorpe', 'nickname' => 'The Scots', 'short' => 'SCU', 'color' => '#FFD200'],
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
    <h2>National League Table 2025/26</h2>
    <?php football_stats_render_table_view_controls($tableView, $currentMainTab ?? '2025-2026', 'national-league', $currentSubTab ?? 'table-2'); ?>
    
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
                $games_remaining = $total_games - $team['played'];
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
                } elseif ($pos >= 22) {
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
</div>