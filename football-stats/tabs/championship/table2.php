<?php
// Fetch league table
$stmt = $db->query("SELECT * FROM league_table_ELC ORDER BY position ASC");
$standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$last_update = $db->query("SELECT MAX(updated_at) as ts FROM league_table")->fetch();

// Safety calculation
$halfway_games = 23; // Halfway point in season
$safety_target_halfway = 25; // Points needed by game 23 to stay safe
$total_games = 46; // Total games in season
$safety_target_magic = 40; // Magic number for safety
$safety_target_average = 36; // Average Points needed by end of season to stay safe
$safety_target_low = 34; // Low safety target
$safety_target_recent_low = 27; // Recent low safety target

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

<!-- Custom CSS -->
<!--
    This CSS is designed to enhance the visual presentation of the Championship table.
    It includes styles for team names with tooltips, color-coded performance metrics, and sticky headers.
    The color scheme is inspired by Discord's dark theme, with additional colors to highlight key statistics.
-->
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

/* ============================================
   BADGES & LABELS
   ============================================ */
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    margin-right: 5px;
}

.badge-team {
    background: #40444b;
    color: #dcddde;
}

/* Table - Points Column */
td:last-child {
    font-weight: bold;
    font-size: 16px;
    color: #5865F2;
}

/* Championship Position Colors */
/* Promotion to Premier League (1-2) */
tr:nth-child(2) td:first-child,
tr:nth-child(3) td:first-child {
    color: #43b581;
    font-weight: bold;
}

/* Playoff (3-6) */
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
</style>

<div class="panel">
    <h2>Championship Table 2025/26</h2>
    <p class="update-info">
        Last updated: <?= date('Y-m-d H:i:s', $last_update['ts'] / 1000) ?>
    </p>
    
    <table>
        <tr>
            <th title="Position">Pos</th>
            <th title="Team">Team</th>
            <th title="Played">P</th>
            <th title="Games remaining">GR</th>
            <th title="Points">Pts</th>
            <th title="Points per Game">PPG</th>
            <th title="Required points needed to reach max points possible">Pts Required</th>
            <th title="Required points per game to reach max points possible">PPG Required</th>
            <th title="PPG needed to reach 46 points">PPG Required (46)</th>
            <th title="Performance points based on current PPG and games remaining">Performance</th>
            <th title="Points relative to games played (The 1:1 Ratio)">+/- Buffer</th>
            <th title="Max Points Possible weighted on current performance">Max Pts</th>
            <th title="Max Points Possible if all remaining games are wins">Max Pts (W)</th>
            <th title="Max Points Possible if all remaining games are draws">Max Pts (D)</th>
            <th title="Max Points Possible if all remaining games are wins/draws (50/50)">Max Pts (W/D)</th>
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
            if ($points > $team['played']) {
                $points_color = '#43b581'; // Green if points are greater than games played
            } else if ($points == $team['played']) {
                $points_color = '#faa61a'; // Orange if points are equal to games played
            } else {
                $points_color = '#f04747'; // Red if points are less than games played
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


            // Calculate max points possible (current points + performance)
            $max_points_possible = round($team['points'] + $performance, 0);
            if ($max_points_possible >= 60) {
                $max_points_possible_color = '#006400'; // Dark Green for 60+ max points possible
            } elseif ($max_points_possible >= 40) {
                $max_points_possible_color = '#00FF00'; // Green for 40-59 max points possible
            } elseif ($max_points_possible >= 30) {
                $max_points_possible_color = '#faa61a'; // Orange for 30-39 max points possible
            } elseif ($max_points_possible >= 20) {
                $max_points_possible_color = '#f04747'; // Red for 20-29 max points possible
            } else {
                $max_points_possible_color = '#8B4513'; // Brown for below 40 max points possible
            }

            // Calculate points required (points needed) to reach max points possible
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

            // Calculate max points possible if all remaining games are wins, draws, or 50/50
            $max_points_possible_win = round($team['points'] + ($games_remaining * 3), 0);
            if ($max_points_possible_win >= 60) {
                $max_points_win_color = '#006400'; // Dark Green for 60+ max points possible
            } elseif ($max_points_possible_win >= 40) {
                $max_points_win_color = '#00FF00'; // Green for 40-59 max points possible
            } elseif ($max_points_possible_win >= 30) {
                $max_points_win_color = '#faa61a'; // Orange for 30-39 max points possible
            } elseif ($max_points_possible_win >= 20) {
                $max_points_win_color = '#f04747'; // Red for 20-29 max points possible
            } else {
                $max_points_win_color = '#8B4513'; // Brown for below 40 max points possible
            }
            $max_points_possible_draw = round($team['points'] + ($games_remaining * 1), 0);
            if ($max_points_possible_draw >= 60) {
                $max_points_draw_color = '#006400'; // Dark Green for 60+ max points possible
            } elseif ($max_points_possible_draw >= 40) {
                $max_points_draw_color = '#00FF00'; // Green for 40-59 max points possible
            } elseif ($max_points_possible_draw >= 30) {
                $max_points_draw_color = '#faa61a'; // Orange for 30-39 max points possible
            } elseif ($max_points_possible_draw >= 20) {
                $max_points_draw_color = '#f04747'; // Red for 20-29 max points possible
            } else {
                $max_points_draw_color = '#8B4513'; // Brown for below 40 max points possible
            }   
            $max_points_possible_win_draw = round($team['points'] + ($games_remaining / 2 * (3 + 1)), 0);
            if ($max_points_possible_win_draw >= 60) {
                $max_points_win_draw_color = '#006400'; // Dark Green for 100+ max points possible
            } elseif ($max_points_possible_win_draw >= 40) {
                $max_points_win_draw_color = '#00FF00'; // Green for 80-99 max points possible
            } elseif ($max_points_possible_win_draw >= 30) {
                $max_points_win_draw_color = '#faa61a'; // Orange for 60-79 max points possible
            } elseif ($max_points_possible_win_draw >= 20) {
                $max_points_win_draw_color = '#f04747'; // Red for 40-59 max points possible
            } else {
                $max_points_win_draw_color = '#8B4513'; // Brown for below 40 max points possible
            }
            
            // Calculate PPG needed to reach 46 points based on current PPG, points and games remaining
            $points_needed_46 = max(0, 46 - $team['points']);
            $ppg_needed_46 = ($games_remaining > 0) ? round($points_needed_46 / $games_remaining, 2) : 0;
            if ($ppg_needed_46 <= 0) {
                $ppg_needed_46_color = '#006400'; // Dark Green for 2+ PPG needed
            } elseif ($ppg_needed_46 <= 2) {
                $ppg_needed_46_color = '#00FF00'; // Green for 3-4 PPG needed
            } elseif ($ppg_needed_46 <= 3) {
                $ppg_needed_46_color = '#ffff00'; // Yellow for 2-4 PPG needed
            } elseif ($ppg_needed_46 <= 5) {
                $ppg_needed_46_color = '#faa61a'; // Orange for 5-6 PPG needed
            } elseif ($ppg_needed_46 <= 10) {
                $ppg_needed_46_color = '#f04747'; // Red for 7-9 PPG needed
            } else {
                $ppg_needed_38_color = '#8B4513'; // Brown for 10+ PPG needed
            }
            
            // Calculate PPG needed to reach max points possible based on current PPG, points and games remaining
            $points_needed_max = max(0, $max_points_possible - $team['points']);
            $ppg_needed = ($games_remaining > 0) ? round($points_needed_max / $games_remaining, 2) : 0;
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
            $is_leeds = stripos($team['team_name'], 'Leeds') !== false;
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
                <span class="team-name">
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
            </td>
            <td><?= $team['played'] ?></td>
            <td style="color: <?= $games_color ?>; font-weight: bold;"><?= $games_remaining ?></td>
            <td style="color: <?= $points_color ?>; font-weight: bold;"><?= $points ?></td>
            <td style="color: <?= $ppg_color ?>; font-weight: bold;"><?= $ppg ?></td>
            <td style="color: <?= $points_needed_color ?>; font-weight: bold;"><?= $points_needed_max ?></td>
            <td style="color: <?= $ppg_required_color ?>; font-weight: bold;"><?= $ppg_needed ?></td>
            <td style="color: <?= $ppg_needed_46_color ?>; font-weight: bold;"><?= $ppg_needed_46 ?></td>
            <td style="color: <?= $performance_color ?>; font-weight: bold;"><?= round($performance, 2) ?></td>
            <td style="color: <?= $buffer_color ?>; font-weight: bold;"><?= $buffer ?></td>
            <td style="color: <?= $max_points_possible_color ?>; font-weight: bold;"><?= $max_points_possible ?></td>
            <td style="color: <?= $max_points_win_color ?>; font-weight: bold;"><?= $max_points_possible_win ?></td>
            <td style="color: <?= $max_points_draw_color ?>; font-weight: bold;"><?= $max_points_possible_draw ?></td>
            <td style="color: <?= $max_points_win_draw_color ?>; font-weight: bold;"><?= $max_points_possible_win_draw ?></td>
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
</div>
