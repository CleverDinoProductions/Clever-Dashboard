<?php
require_once dirname(__DIR__, 3) . '/includes/table-view.php';

$tableView = football_stats_get_table_view($db, 'ELC', 'league_table_ELC', $currentMainTab ?? '2025-2026');
$standings = $tableView['standings'];
$last_update = $tableView['last_update'];

// Safety calculation
$halfway_games = 23; // Halfway point in season
$safety_target_halfway = 25; // Points needed by game 23 to stay safe
$total_games = 46; // Total games in season
$safety_target_magic = 50; // Magic number for safety
$safety_target_average = 45; // Average Points needed by end of season to stay safe
$safety_target_low = 42; // Low safety target
$safety_target_recent_low = 35; // Recent low safety target

// Team nicknames, abbreviations, and COMMON NAMES for Championship teams
$team_info = [
    'Birmingham City' => [
        'name' => 'Birmingham City',
        'common_name' => 'Birmingham',
        'nickname' => 'The Blues',
        'short' => 'BIR', // Often BCFC or BIR
        'color' => '#0000FF',
    ],
    'Blackburn Rovers' => [
        'name' => 'Blackburn Rovers',
        'common_name' => 'Blackburn',
        'nickname' => 'Rovers / The Riversiders',
        'short' => 'BLB',
        'color' => '#0054A6',
    ],
    'Bristol City' => [
        'name' => 'Bristol City',
        'common_name' => 'Bristol City',
        'nickname' => 'The Robins',
        'short' => 'BRC',
        'color' => '#BC0000',
    ],
    'Charlton Athletic' => [
        'name' => 'Charlton Athletic',
        'common_name' => 'Charlton',
        'nickname' => 'The Addicks',
        'short' => 'CHA',
        'color' => '#E30613',
    ],
    'Coventry City' => [
        'name' => 'Coventry City',
        'common_name' => 'Coventry',
        'nickname' => 'The Sky Blues',
        'short' => 'COV',
        'color' => '#87CEEB',
    ],
    'Derby County' => [
        'name' => 'Derby County',
        'common_name' => 'Derby',
        'nickname' => 'The Rams',
        'short' => 'DER',
        'color' => '#000000',
    ],
    'Hull City' => [
        'name' => 'Hull City',
        'common_name' => 'Hull',
        'nickname' => 'The Tigers',
        'short' => 'HUL',
        'color' => '#F5A100',
    ],
    'Ipswich Town' => [
        'name' => 'Ipswich Town',
        'common_name' => 'Ipswich',
        'nickname' => 'The Tractor Boys',
        'short' => 'IPS',
        'color' => '#0033FF',
    ],
    'Leicester City' => [
        'name' => 'Leicester City',
        'common_name' => 'Leicester',
        'nickname' => 'The Foxes',
        'short' => 'LEI',
        'color' => '#003090',
    ],
    'Middlesbrough' => [
        'name' => 'Middlesbrough',
        'common_name' => 'Boro',
        'nickname' => 'The Smoggies',
        'short' => 'MID',
        'color' => '#E21B23',
    ],
    'Millwall' => [
        'name' => 'Millwall',
        'common_name' => 'Millwall',
        'nickname' => 'The Lions',
        'short' => 'MIL',
        'color' => '#00254B',
    ],
    'Norwich City' => [
        'name' => 'Norwich City',
        'common_name' => 'Norwich',
        'nickname' => 'The Canaries',
        'short' => 'NOR',
        'color' => '#FFF200',
    ],
    'Oxford United' => [
        'name' => 'Oxford United',
        'common_name' => 'Oxford',
        'nickname' => "The U's",
        'short' => 'OXF',
        'color' => '#FFFF00',
    ],
    'Portsmouth' => [
        'name' => 'Portsmouth',
        'common_name' => 'Pompey',
        'nickname' => 'Pompey',
        'short' => 'POR',
        'color' => '#001489',
    ],
    'Preston North End' => [
        'name' => 'Preston North End',
        'common_name' => 'Preston',
        'nickname' => 'The Lilywhites',
        'short' => 'PNE',
        'color' => '#FFFFFF',
    ],
    'Queens Park Rangers' => [
        'name' => 'Queens Park Rangers',
        'common_name' => 'QPR',
        'nickname' => 'The Hoops',
        'short' => 'QPR',
        'color' => '#0000FF',
    ],
    'Sheffield United' => [
        'name' => 'Sheffield United',
        'common_name' => 'Sheff Utd',
        'nickname' => 'The Blades',
        'short' => 'SHU',
        'color' => '#EE2737',
    ],
    'Sheffield Wednesday' => [
        'name' => 'Sheffield Wednesday',
        'common_name' => 'Sheff Wed',
        'nickname' => 'The Owls',
        'short' => 'SHW',
        'color' => '#0000FF',
    ],
    'Southampton' => [
        'name' => 'Southampton',
        'common_name' => 'Saints',
        'nickname' => 'The Saints',
        'short' => 'SOU',
        'color' => '#D71920',
    ],
    'Stoke City' => [
        'name' => 'Stoke City',
        'common_name' => 'Stoke',
        'nickname' => 'The Potters',
        'short' => 'STK',
        'color' => '#E03A3E',
    ],
    'Swansea City' => [
        'name' => 'Swansea City',
        'common_name' => 'Swansea',
        'nickname' => 'The Swans',
        'short' => 'SWA',
        'color' => '#FFFFFF',
    ],
    'Watford' => [
        'name' => 'Watford',
        'common_name' => 'Watford',
        'nickname' => 'The Hornets',
        'short' => 'WAT',
        'color' => '#FBEE23',
    ],
    'West Bromwich Albion' => [
        'name' => 'West Brom',
        'common_name' => 'West Brom',
        'nickname' => 'The Baggies',
        'short' => 'WBA',
        'color' => '#122F67',
    ],
    'Wrexham' => [
        'name' => 'Wrexham',
        'common_name' => 'Wrexham',
        'nickname' => 'The Red Dragons',
        'short' => 'WRE',
        'color' => '#FF0000',
    ],
];

// Helper function to get team info
function getTeamInfo($team_name, $team_info) {
    // Direct match
    if (isset($team_info[$team_name])) {
        return $team_info[$team_name];
    }
    
    // Partial match (for variations like "Brighton" vs "Brighton & Hove Albion")
    foreach ($team_info as $key => $info) {
        if (stripos($team_name, $key) !== false || stripos($key, $team_name) !== false) {
            return $info;
        }
    }
    
    // Default if not found
    return [
        'common_name' => $team_name,
        'nickname' => 'Unknown',
        'short' => strtoupper(substr($team_name, 0, 3)),
        'color' => '#888888',
    ];
}
?>

<!-- Add custom tooltip CSS -->
<style>
.team-name {
    position: relative;
    cursor: help;
    display: inline-block;
    transition: color 0.2s ease;
}

.team-name:hover {
    color: #FFCD00;
}

.team-official {
    color: #dcddde;
}

.team-common {
    color: #888;
    font-size: 12px;
    margin-left: 6px;
    font-weight: normal;
}

.team-tooltip {
    visibility: hidden;
    opacity: 0;
    position: absolute;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #2e3136, #40444b);
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    white-space: nowrap;
    z-index: 1000;
    font-size: 13px;
    border: 2px solid;
    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.team-tooltip::after {
    content: "";
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-top-color: inherit;
}

.team-name:hover .team-tooltip {
    visibility: visible;
    opacity: 1;
}

.tooltip-nickname {
    display: block;
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 3px;
}

.tooltip-short {
    display: block;
    font-size: 11px;
    color: #dcddde;
}

/* 1. Target the header cells specifically */
th {
    position: sticky;
    top: 0;
    z-index: 10; /* Keeps the header above the scrolling body rows */
    background-color: #222; /* Use your team/site brand color here */
    color: white;
    white-space: nowrap; /* Prevents long titles from breaking the layout */
    border-bottom: 2px solid #444;
}

/* 2. Important fix for tables */
table {
    border-collapse: collapse; /* Required for sticky borders to show up correctly */
}

/* Championship Position Colors */
/* Promotion (1-2) */
tr:nth-child(2) td:first-child,
tr:nth-child(3) td:first-child {
    color: #43b581;
    font-weight: bold;
}

/* Playoffs (3-6) */
tr:nth-child(4) td:first-child,
tr:nth-child(5) td:first-child,
tr:nth-child(6) td:first-child,
tr:nth-child(7) td:first-child {
    color: #5865F2;
    font-weight: bold;
}

/* Relegation Zone (22-24) */
tr:nth-child(23) td:first-child,
tr:nth-child(24) td:first-child,
tr:nth-child(25) td:first-child {
    color: #f04747;
    font-weight: bold;
}

.team-crest {
    width: 24px;
    height: 24px;
    object-fit: contain;
    vertical-align: middle;
    margin-right: 10px;
}

/* Ensure the team cell uses flex for better alignment */
.team-cell {
    display: flex;
    align-items: center;
}
</style>

<div class="panel">
    <h2>Championship Table 2025/26</h2>
    <?php football_stats_render_table_view_controls($tableView, $currentMainTab ?? '2025-2026', $currentLeague ?? 'championship', $currentSubTab ?? 'table'); ?>
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

            // Calculate PPG
            $ppg = ($team['played'] > 0) ? round($team['points'] / $team['played'], 2) : 0;
            if ($ppg >= 2) {
                $ppg_color = '#006400'; // Dark Green for 2+ PPG
            } elseif ($ppg >= 1) {
                $ppg_color = '#00FF00'; // Green for 1-1.99 PPG
            } elseif ($ppg >= 0.75) {
                $ppg_color = '#faa61a'; // Orange for 0.75-0.99 PPG
            } elseif ($ppg >= 0.5) {
                $ppg_color = '#f04747'; // Red for 0.5-0.74 PPG
            } else {
                $ppg_color = '#8B4513'; // Brown for below 0.5 PPG
            }

            //remaining games
            $games_remaining = $total_games - $team['played'];
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

            // Calculate the Buffer (Points minus Games Played)
            $buffer = $team['points'] - $team['played'];
            if ($buffer > 0) {
                $buffer_color = '#43b581'; // Green for positive buffer
            } elseif ($buffer < 0) {
                $buffer_color = '#f04747'; // Red for negative buffer
            } else {
                $buffer_color = '#888'; // Grey for neutral
            }

            // Performance
            $performance = round($ppg * $games_remaining, 0);
            if ($performance >= 10) {
                $performance_color = '#006400'; // Dark Green for 30+ performance
            } elseif ($performance >= 8) {
                $performance_color = '#00FF00'; // Green for 20-29.99 performance
            } elseif ($performance >= 5) {
                $performance_color = '#faa61a'; // Orange for 7-19.99 performance
            } elseif ($performance >= 0) {
                $performance_color = '#f04747'; // Red for 5-6.99 performance
            } else {
                $performance_color = '#8B4513'; // Brown for below 5 performance
            }


            //calculate max points possible
            $max_points_possible = round($team['points'] + $performance, 0);

            // calculate points required
            $points_needed_max = $max_points_possible - $team['points'];
            if ($points_needed_max <= 0) {
                $points_needed_color = '#43b581'; // Green if already at or above max points possible
            } elseif ($points_needed_max <= 10) {
                $points_needed_color = '#00FF00'; // Green for 1-10 points needed
            } elseif ($points_needed_max <= 20) {
                $points_needed_color = '#faa61a'; // Orange for 11-20 points needed
            } elseif ($points_needed_max <= 30) {
                $points_needed_color = '#f04747'; // Red for 21-30 points needed
            } else {
                $points_needed_color = '#8B4513'; // Brown for above 30 points needed
            }

            // Calculate PPG required to reach max points possible calculated using current ppg ($ppg)
            $ppg_needed = round(($points_needed_max / $ppg) / $games_remaining, 2);
            if ($ppg_needed >= 2) {
                $ppg_required_color = '#006400'; // Dark Green for 2+ PPG needed
            } elseif ($ppg_needed >= 1) {
                $ppg_required_color = '#00FF00'; // Green for 1-1.99 PPG needed
            } elseif ($ppg_needed >= 0.75) {
                $ppg_required_color = '#faa61a'; // Orange for 0.75-0.99 PPG needed
            } elseif ($ppg_needed >= 0.5) {
                $ppg_required_color = '#f04747'; // Red for 0.5-0.74 PPG needed
            } else {
                $ppg_required_color = '#8B4513'; // Brown for below 0.5 PPG needed
            }
            
            // Highlight rows
            $row_style = '';
            if ($team['position'] >= 22) {
                $row_style = 'background: rgba(244, 71, 71, 0.2); border-left: 4px solid #f04747;';
            } elseif ($team['position'] <= 2) {
                $row_style = 'background: rgba(67, 181, 129, 0.1); border-left: 4px solid #43b581;';
            } elseif ($team['position'] <= 6) {
                $row_style = 'background: rgba(88, 101, 242, 0.1); border-left: 4px solid #5865F2;';
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
        <div><span style="color: #43b581;">■</span> Promotion to Premier League (1st-2nd)</div>
        <div><span style="color: #5865F2;">■</span> Playoffs (3rd-6th)</div>
        <div><span style="color: #f04747;">■</span> Relegation to League One (22nd-24th)</div>
        <div style="margin-left: auto; color: #888;">
            💡 Hover over team names for nicknames
        </div>
    </div>
</div>
