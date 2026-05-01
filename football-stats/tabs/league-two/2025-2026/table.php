<?php
require_once dirname(__DIR__, 3) . '/includes/table-view.php';

// Fetch data - Ensure the 'ELC' code matches your DB sync script
$tableView = football_stats_get_table_view($db, 'L2', 'league_table_L2', $currentMainTab ?? '2025-2026');
$standings = $tableView['standings'];
$last_update = $tableView['last_update'];

// Championship Settings
$total_games = 46;

$team_info = [
    'Accrington Stanley' => ['name' => 'Accrington Stanley', 'common_name' => 'Accrington', 'nickname' => 'Stanley', 'short' => 'ACC', 'color' => '#D11241'],
    'AFC Wimbledon' => ['name' => 'AFC Wimbledon', 'common_name' => 'Wimbledon', 'nickname' => 'The Dons', 'short' => 'WIM', 'color' => '#213D8A'],
    'Barrow' => ['name' => 'Barrow', 'common_name' => 'Barrow', 'nickname' => 'The Bluebirds', 'short' => 'BRW', 'color' => '#005DAA'],
    'Bradford City' => ['name' => 'Bradford City', 'common_name' => 'Bradford', 'nickname' => 'The Bantams', 'short' => 'BRA', 'color' => '#FFB81C'],
    'Carlisle United' => ['name' => 'Carlisle United', 'common_name' => 'Carlisle', 'nickname' => 'The Cumbrians', 'short' => 'CAR', 'color' => '#004A99'],
    'Cheltenham Town' => ['name' => 'Cheltenham Town', 'common_name' => 'Cheltenham', 'nickname' => 'The Robins', 'short' => 'CHE', 'color' => '#E30613'],
    'Chesterfield' => ['name' => 'Chesterfield', 'common_name' => 'Chesterfield', 'nickname' => 'The Spireites', 'short' => 'CHS', 'color' => '#0054A6'],
    'Colchester United' => ['name' => 'Colchester United', 'common_name' => 'Colchester', 'nickname' => 'The U\'s', 'short' => 'COL', 'color' => '#0000FF'],
    'Crewe Alexandra' => ['name' => 'Crewe Alexandra', 'common_name' => 'Crewe', 'nickname' => 'The Railwaymen', 'short' => 'CRE', 'color' => '#D00027'],
    'Doncaster Rovers' => ['name' => 'Doncaster Rovers', 'common_name' => 'Doncaster', 'nickname' => 'Donny', 'short' => 'DON', 'color' => '#E30613'],
    'Fleetwood Town' => ['name' => 'Fleetwood Town', 'common_name' => 'Fleetwood', 'nickname' => 'The Cod Army', 'short' => 'FLE', 'color' => '#E30613'],
    'Gillingham' => ['name' => 'Gillingham', 'common_name' => 'Gillingham', 'nickname' => 'The Gills', 'short' => 'GIL', 'color' => '#0000FF'],
    'Grimsby Town' => ['name' => 'Grimsby Town', 'common_name' => 'Grimsby', 'nickname' => 'The Mariners', 'short' => 'GRI', 'color' => '#000000'],
    'Harrogate Town' => ['name' => 'Harrogate Town', 'common_name' => 'Harrogate', 'nickname' => 'The Town', 'short' => 'HAR', 'color' => '#FFD200'],
    'MK Dons' => ['name' => 'Milton Keynes Dons', 'common_name' => 'MK Dons', 'nickname' => 'The Dons', 'short' => 'MKD', 'color' => '#FFFFFF'],
    'Morecambe' => ['name' => 'Morecambe', 'common_name' => 'Morecambe', 'nickname' => 'The Shrimps', 'short' => 'MOR', 'color' => '#E30613'],
    'Newport County' => ['name' => 'Newport County', 'common_name' => 'Newport', 'nickname' => 'The Exiles', 'short' => 'NEW', 'color' => '#FFB81C'],
    'Notts County' => ['name' => 'Notts County', 'common_name' => 'Notts Co', 'nickname' => 'The Magpies', 'short' => 'NTC', 'color' => '#000000'],
    'Port Vale' => ['name' => 'Port Vale', 'common_name' => 'Port Vale', 'nickname' => 'The Valiants', 'short' => 'PVL', 'color' => '#FFFFFF'],
    'Salford City' => ['name' => 'Salford City', 'common_name' => 'Salford', 'nickname' => 'The Ammies', 'short' => 'SAL', 'color' => '#E30613'],
    'Swindon Town' => ['name' => 'Swindon Town', 'common_name' => 'Swindon', 'nickname' => 'The Robins', 'short' => 'SWI', 'color' => '#E30613'],
    'Tranmere Rovers' => ['name' => 'Tranmere Rovers', 'common_name' => 'Tranmere', 'nickname' => 'The Rovers', 'short' => 'TRA', 'color' => '#FFFFFF'],
    'Walsall' => ['name' => 'Walsall', 'common_name' => 'Walsall', 'nickname' => 'The Saddlers', 'short' => 'WAL', 'color' => '#E30613'],
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
    <?php football_stats_render_table_view_controls($tableView, $currentMainTab ?? '2025-2026', 'league-two', $currentSubTab ?? 'table'); ?>
    
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

                $games_remaining = $total_games - $team['played'];
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
</div>