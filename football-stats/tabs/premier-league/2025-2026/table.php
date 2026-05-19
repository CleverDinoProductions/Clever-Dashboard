<?php
require_once __DIR__ . '/../../../includes/table-view.php';

$tableView = football_stats_get_table_view_combined($db, 'PL', 'league_table_PL', $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$standings = $tableView['standings'];
$last_update = $tableView['last_update'];

// Safety calculation
$halfway_games = 19; // First half ends at MW 18
$second_half_start = 19; // Second half starts at MW 19 (MW 18 = winter break)
$safety_target_halfway = 20; // Points needed by game 19 to stay safe
$total_games = 38; // Total games in season
$max_regular_mw = 38; // PL has no playoffs; cap excludes any mw > 38

// Table filter
$table_filter = isset($_GET['table_filter']) && in_array($_GET['table_filter'], ['first_half', 'second_half', 'home', 'away', 'first_half_home', 'first_half_away', 'second_half_home', 'second_half_away'], true) ? $_GET['table_filter'] : 'all';
$_split_season = $tableView['active_season_label'] ?? ($currentMainTab ?? '2025-2026');
$_filter_max_date = ($calcMode === 'by_date' && !empty($tableView['active_date'])) ? $tableView['active_date'] : null;
// In archive (by_matchweek) mode, cap filter queries at the selected matchweek
$_filter_max_mw = ($calcMode === 'by_matchweek' && !empty($tableView['active_matchweek']))
    ? min($max_regular_mw, (int)$tableView['active_matchweek'])
    : $max_regular_mw;
if ($table_filter !== 'all') {
    $filteredStandings = football_stats_compute_filtered_standings($db, 'PL', $_split_season, $table_filter, $halfway_games, 'league_table_PL', $_filter_max_mw, $_filter_max_date, $second_half_start, $max_regular_mw);
    if (!empty($filteredStandings)) {
        $standings = $filteredStandings;
    }
    $_second_half_games = $max_regular_mw - $second_half_start; // MW 19-38 = 20 games
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
$homeStandings = football_stats_compute_filtered_standings($db, 'PL', $_split_season, 'home', $halfway_games, 'league_table_PL', $_filter_max_mw, $_filter_max_date, $second_half_start);
$awayStandings = football_stats_compute_filtered_standings($db, 'PL', $_split_season, 'away', $halfway_games, 'league_table_PL', $_filter_max_mw, $_filter_max_date, $second_half_start);
$safety_target_magic = 40; // Magic number for safety
$safety_target_average = 36; // Average Points needed by end of season to stay safe
$safety_target_low = 34; // Low safety target
$safety_target_recent_low = 27; // Recent low safety target

// Team nicknames, abbreviations, and COMMON NAMES
$team_info = [
    'Arsenal' => [
        'name' => 'Arsenal',
        'common_name' => 'Arsenal',
        'nickname' => 'The Gunners',
        'short' => 'ARS',
        'color' => '#EF0107',
    ],
    'Aston Villa' => [
        'name' => 'Aston Villa',
        'common_name' => 'Villa',
        'nickname' => 'The Villans',
        'short' => 'AVL',
        'color' => '#95BFE5',
    ],
    'Bournemouth' => [
        'name' => 'Bournemouth',
        'common_name' => 'Bournemouth',
        'nickname' => 'The Cherries',
        'short' => 'BOU',
        'color' => '#DA291C',
    ],
    'Brentford' => [
        'name' => 'Brentford',
        'common_name' => 'Brentford',
        'nickname' => 'The Bees',
        'short' => 'BRE',
        'color' => '#D20000',
    ],
    'Brighton and Hove Albion' => [
        'name' => 'Brighton',
        'common_name' => 'Brighton',
        'nickname' => 'The Seagulls',
        'short' => 'BHA',
        'color' => '#0057B8',
    ],
    'Burnley' => [
        'name' => 'Burnley',
        'common_name' => 'Burnley',
        'nickname' => 'The Clarets',
        'short' => 'BUR',
        'color' => '#6C1D45',
    ],
    'Chelsea' => [
        'name' => 'Chelsea',
        'common_name' => 'Chelsea',
        'nickname' => 'The Blues',
        'short' => 'CHE',
        'color' => '#034694',
    ],
    'Crystal Palace' => [
        'name' => 'Crystal Palace',
        'common_name' => 'Palace',
        'nickname' => 'The Eagles',
        'short' => 'CRY',
        'color' => '#1B458F',
    ],
    'Everton' => [
        'name' => 'Everton',
        'common_name' => 'Everton',
        'nickname' => 'The Toffees',
        'short' => 'EVE',
        'color' => '#003399',
    ],
    'Fulham' => [
        'name' => 'Fulham',
        'common_name' => 'Fulham',
        'nickname' => 'The Cottagers',
        'short' => 'FUL',
        'color' => '#000000',
    ],
    'Ipswich Town' => [
        'name' => 'Ipswich',
        'common_name' => 'Ipswich',
        'nickname' => 'The Tractor Boys',
        'short' => 'IPS',
        'color' => '#0033FF',
    ],
    'Leicester City' => [
        'name' => 'Leicester',
        'common_name' => 'Leicester',
        'nickname' => 'The Foxes',
        'short' => 'LEI',
        'color' => '#003090',
    ],
    'Leeds United' => [
        'name' => 'Leeds',
        'common_name' => 'Leeds',
        'nickname' => 'The Whites / Peacocks',
        'short' => 'LEE',
        'color' => '#FFCD00',
    ],
    'Liverpool' => [
        'name' => 'Liverpool',
        'common_name' => 'Liverpool',
        'nickname' => 'The Reds',
        'short' => 'LIV',
        'color' => '#C8102E',
    ],
    'Luton' => [
        'name' => 'Luton',
        'common_name' => 'Luton',
        'nickname' => 'The Hatters',
        'short' => 'LUT',
        'color' => '#FFCD00',
    ],
    'Manchester City' => [
        'name' => 'Manchester City',
        'common_name' => 'Man City',
        'nickname' => 'The Citizens / City',
        'short' => 'MCI',
        'color' => '#6CABDD',
    ],
    'Manchester United' => [
        'name' => 'Manchester United',
        'common_name' => 'Man United',
        'nickname' => 'The Red Devils',
        'short' => 'MUN',
        'color' => '#DA291C',
    ],
    'Newcastle United' => [
        'name' => 'Newcastle',
        'common_name' => 'Newcastle',
        'nickname' => 'The Magpies / Toon',
        'short' => 'NEW',
        'color' => '#241F20',
    ],
    'Nottingham Forest' => [
        'name' => 'Nottingham Forest',
        'common_name' => 'Forest',
        'nickname' => 'The Reds / Forest',
        'short' => 'NFO',
        'color' => '#DD0000',
    ],
    'Sheffield United' => [
        'name' => 'Sheffield United',
        'common_name' => 'Sheffield',
        'nickname' => 'The Blades',
        'short' => 'SHU',
        'color' => '#003399',
    ],
    'Southampton' => [
        'name' => 'Southampton',
        'common_name' => 'Southampton',
        'nickname' => 'The Saints',
        'short' => 'SOU',
        'color' => '#6CABDD',
    ],
    'Sunderland' => [
        'name' => 'Sunderland',
        'common_name' => 'Sunderland',
        'nickname' => 'The Black Cats',
        'short' => 'SUN',
        'color' => '#FF0000',
    ],
    'Tottenham Hotspur' => [
        'name' => 'Tottenham Hotspur',
        'common_name' => 'Spurs',
        'nickname' => 'Spurs',
        'short' => 'TOT',
        'color' => '#132257',
    ],
    'West Ham United' => [
        'name' => 'West Ham',
        'common_name' => 'West Ham',
        'nickname' => 'The Hammers / Irons',
        'short' => 'WHU',
        'color' => '#7A263A',
    ],
    'Wolves' => [
        'name' => 'Wolverhampton Wanderers',
        'common_name' => 'Wolves',
        'nickname' => 'Wolves',
        'short' => 'WOL',
        'color' => '#FDB913',
    ],
    'Wolverhampton Wanderers' => [
        'name' => 'Wolverhampton Wanderers',
        'common_name' => 'Wolves',
        'nickname' => 'Wolves',
        'short' => 'WOL',
        'color' => '#FDB913',
    ],
];

// Helper function to get team info
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
    <h2>Premier League Table 2025/26</h2>
    <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', $currentLeague ?? 'premier-league', $currentSubTab ?? 'table'); ?>
    <?php football_stats_render_table_filter_buttons($table_filter, $currentMainTab ?? '2025-2026', $currentLeague ?? 'premier-league', $currentSubTab ?? 'table'); ?>
    <p class="update-info">
        <?= htmlspecialchars($tableView['updated_label'], ENT_QUOTES, 'UTF-8') ?>:
        <?= $last_update['ts'] ? date('Y-m-d H:i:s', $last_update['ts'] / 1000) : 'No data available yet' ?>
    </p>
    
    <table>
        <tr>
            <th title="Position">Pos</th>
            <th title="Team">Team</th>
            <th title="Played">P</th>
            <th title="Games remaining">GR</th>
            <th title="Points">Pts</th>
            <th title="Wins">W</th>
            <th title="Draws">D</th>
            <th title="Losses">L</th>
            <th title="Goals For">GF</th>
            <th title="Goals Against">GA</th>
            <th title="Goal Difference">GD</th>
        </tr>
        <?php foreach ($standings as $team): ?>
        <?php
            // Get team info
            $info = getTeamInfo($team['team_name'], $team_info);

            //remaining games
            $games_remaining = max(0, ($team['total_expected'] ?? $total_games) - $team['played']);
            if ($games_remaining <= 5) {
                $games_color = '#f04747'; // Red for 5 or fewer games remaining
            } elseif ($games_remaining <= 10) {
                $games_color = '#faa61a'; // Orange for 6-10 games remaining
            } else {
                $games_color = '#43b581'; // Green for more than 10 games remaining
            }

            // Points
            $points = $team['points'];
            if ($points >= $team['played']) {
                $points_color = '#43b581'; // Green if points are greater than or equal to games remaining
            } else {
                $points_color = '#f04747'; // Red if points are less than games remaining
            }
            
            // Highlight rows
            $is_leeds = stripos($team['team_name'], 'Leeds') !== false;
            $row_style = '';
            if ($is_leeds) {
                $row_style = 'background: rgba(29, 66, 138, 0.3); border-left: 4px solid #FFFFFF; border-right: 4px solid #FFCD00;';
            } elseif ($team['position'] >= 18) {
                $row_style = 'background: rgba(244, 71, 71, 0.2); border-left: 4px solid #f04747;';
            } elseif ($team['position'] <= 5) {
                $row_style = 'background: rgba(67, 181, 129, 0.1); border-left: 4px solid #43b581;';
            } elseif ($team['position'] == 6) {
                $row_style = 'background: rgba(88, 101, 242, 0.1); border-left: 4px solid #5865F2;';
            } elseif ($team['position'] == 7) {
                $row_style = 'background: rgba(255, 205, 0, 0.1); border-left: 4px solid #FFCD00;';
            }
            
            // Check if official name differs from common name
            $show_common = ($team['team_name'] !== $info['common_name']);
        ?>
        <tr style="<?= $row_style ?>">
            <td><strong><?= $team['position'] ?></strong></td>
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
            <td style="color: <?= $games_color ?>; font-weight: bold;"><?= $games_remaining ?></td>
            <td style="color: <?= $points_color ?>; font-weight: bold;"><?= $points ?></td>
            <td><?= $team['won'] ?></td>
            <td><?= $team['drawn'] ?></td>
            <td><?= $team['lost'] ?></td>
            <td><?= $team['gf'] ?></td>
            <td><?= $team['ga'] ?></td>
            <td style="color: <?php 
                if ($team['gd'] > 0) {
                    echo '#43b581'; // green
                } elseif ($team['gd'] < 0) {
                    echo '#f04747'; // red
                } else {
                    echo '#dcddde'; // white
                }
            ?>; font-weight: bold;">
                <?= $team['gd'] > 0 ? '+' . $team['gd'] : $team['gd'] ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <div style="margin-top: 20px; display: flex; gap: 20px; font-size: 12px; flex-wrap: wrap;">
        <div><span style="color: #43b581;">■</span> Champions League (1st-4th)</div>
        <div><span style="color: #5865F2;">■</span> Europa League (5th-6th)</div>
        <div><span style="color: #FFCD00;">■</span> Conference League (7th)</div>
        <div><span style="color: #ffffff;">■</span> Leeds United 🤍💛💙</div>
        <div><span style="color: #f04747;">■</span> Relegation to Championship (18th-20th)</div>
        <div style="margin-left: auto; color: #888;">
            💡 Hover over team names for nicknames
        </div>
    </div>
    
    <div style="margin-top: 15px; padding: 15px; background: #40444b; border-radius: 8px; border-left: 4px solid #5865F2;">
        <strong style="color: #FFCD00;">Safety Target:</strong>
        <span style="color: #dcddde;">
            <?= $safety_target_halfway ?> points by game <?= $halfway_games ?> (halfway point)
        </span>
        <br>
        <span style="color: #888; font-size: 12px;">
            Based on 75% rule: Teams hitting this target have 85-90% survival rate
        </span>
    </div>
    <?php football_stats_render_home_away_split($homeStandings, $awayStandings, $team_info); ?>
</div>
