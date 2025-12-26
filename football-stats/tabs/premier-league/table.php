<?php
// Fetch league table
$stmt = $db->query("SELECT * FROM league_table ORDER BY position ASC");
$standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$last_update = $db->query("SELECT MAX(updated_at) as ts FROM league_table")->fetch();

// Safety calculation
$halfway_games = 19;
$safety_target_halfway = 20; // Points needed by game 19 to stay safe

// Team nicknames, abbreviations, and COMMON NAMES
$team_info = [
    'Arsenal' => [
        'common_name' => 'Arsenal',
        'nickname' => 'The Gunners',
        'short' => 'ARS',
        'color' => '#EF0107',
    ],
    'Aston Villa' => [
        'common_name' => 'Villa',
        'nickname' => 'The Villans',
        'short' => 'AVL',
        'color' => '#95BFE5',
    ],
    'Bournemouth' => [
        'common_name' => 'Bournemouth',
        'nickname' => 'The Cherries',
        'short' => 'BOU',
        'color' => '#DA291C',
    ],
    'Brentford' => [
        'common_name' => 'Brentford',
        'nickname' => 'The Bees',
        'short' => 'BRE',
        'color' => '#D20000',
    ],
    'Brighton & Hove Albion' => [
        'common_name' => 'Brighton',
        'nickname' => 'The Seagulls',
        'short' => 'BHA',
        'color' => '#0057B8',
    ],
    'Brighton' => [
        'common_name' => 'Brighton',
        'nickname' => 'The Seagulls',
        'short' => 'BHA',
        'color' => '#0057B8',
    ],
    'Burnley' => [
        'common_name' => 'Burnley',
        'nickname' => 'The Clarets',
        'short' => 'BUR',
        'color' => '#6C1D45',
    ],
    'Chelsea' => [
        'common_name' => 'Chelsea',
        'nickname' => 'The Blues',
        'short' => 'CHE',
        'color' => '#034694',
    ],
    'Crystal Palace' => [
        'common_name' => 'Palace',
        'nickname' => 'The Eagles',
        'short' => 'CRY',
        'color' => '#1B458F',
    ],
    'Everton' => [
        'common_name' => 'Everton',
        'nickname' => 'The Toffees',
        'short' => 'EVE',
        'color' => '#003399',
    ],
    'Fulham' => [
        'common_name' => 'Fulham',
        'nickname' => 'The Cottagers',
        'short' => 'FUL',
        'color' => '#000000',
    ],
    'Ipswich Town' => [
        'common_name' => 'Ipswich',
        'nickname' => 'The Tractor Boys',
        'short' => 'IPS',
        'color' => '#0033A0',
    ],
    'Leeds United' => [
        'common_name' => 'Leeds',
        'nickname' => 'The Whites / Peacocks',
        'short' => 'LEE',
        'color' => '#FFCD00',
    ],
    'Leicester City' => [
        'common_name' => 'Leicester',
        'nickname' => 'The Foxes',
        'short' => 'LEI',
        'color' => '#003090',
    ],
    'Liverpool' => [
        'common_name' => 'Liverpool',
        'nickname' => 'The Reds',
        'short' => 'LIV',
        'color' => '#C8102E',
    ],
    'Manchester City' => [
        'common_name' => 'Man City',
        'nickname' => 'The Citizens / City',
        'short' => 'MCI',
        'color' => '#6CABDD',
    ],
    'Manchester United' => [
        'common_name' => 'Man United',
        'nickname' => 'The Red Devils',
        'short' => 'MUN',
        'color' => '#DA291C',
    ],
    'Newcastle United' => [
        'common_name' => 'Newcastle',
        'nickname' => 'The Magpies / Toon',
        'short' => 'NEW',
        'color' => '#241F20',
    ],
    'Nottingham Forest' => [
        'common_name' => 'Forest',
        'nickname' => 'The Reds / Forest',
        'short' => 'NFO',
        'color' => '#DD0000',
    ],
    'Southampton' => [
        'common_name' => 'Southampton',
        'nickname' => 'The Saints',
        'short' => 'SOU',
        'color' => '#D71920',
    ],
    'Tottenham Hotspur' => [
        'common_name' => 'Spurs',
        'nickname' => 'Spurs',
        'short' => 'TOT',
        'color' => '#132257',
    ],
    'West Ham United' => [
        'common_name' => 'West Ham',
        'nickname' => 'The Hammers / Irons',
        'short' => 'WHU',
        'color' => '#7A263A',
    ],
    'Wolverhampton Wanderers' => [
        'common_name' => 'Wolves',
        'nickname' => 'Wolves',
        'short' => 'WOL',
        'color' => '#FDB913',
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
</style>

<div class="panel">
    <h2>Premier League Table 2025/26</h2>
    <p class="update-info">
        Last updated: <?= date('Y-m-d H:i:s', $last_update['ts'] / 1000) ?>
    </p>
    
    <table>
        <tr>
            <th>Pos</th>
            <th>Team</th>
            <th>P</th>
            <th>W</th>
            <th>D</th>
            <th>L</th>
            <th>GF</th>
            <th>GA</th>
            <th>GD</th>
            <th>Pts</th>
            <th title="Points needed to reach <?= $safety_target_halfway ?> by game <?= $halfway_games ?>">To Safety</th>
        </tr>
        <?php foreach ($standings as $team): ?>
        <?php
            // Get team info
            $info = getTeamInfo($team['team_name'], $team_info);
            
            // Calculate points needed
            $games_left_to_halfway = $halfway_games - $team['played'];
            $points_needed = $safety_target_halfway - $team['points'];
            
            // Color coding for safety column
            if ($points_needed <= 0) {
                $safety_color = '#43b581'; // green - already safe
                $safety_text = '✓ Safe';
            } elseif ($points_needed <= 3) {
                $safety_color = '#faa61a'; // orange - close
                $safety_text = $points_needed . ' pts';
            } else {
                $safety_color = '#f04747'; // red - danger
                $safety_text = $points_needed . ' pts';
            }
            
            // Check if past halfway
            if ($team['played'] >= $halfway_games) {
                if ($team['points'] >= $safety_target_halfway) {
                    $safety_color = '#43b581';
                    $safety_text = '✓ Safe';
                } else {
                    $safety_color = '#f04747';
                    $safety_text = '⚠ Behind';
                }
            }
            
            // Highlight rows
            $is_leeds = stripos($team['team_name'], 'Leeds') !== false;
            $row_style = '';
            if ($is_leeds) {
                $row_style = 'background: rgba(29, 66, 138, 0.3); border-left: 4px solid #FFCD00;';
            } elseif ($team['position'] >= 18) {
                $row_style = 'background: rgba(244, 71, 71, 0.2); border-left: 4px solid #f04747;';
            } elseif ($team['position'] <= 4) {
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
                        <?= htmlspecialchars($team['team_name']) ?>
                    </span>
                    <?php if ($show_common): ?>
                        <span class="team-common">(<?= $info['common_name'] ?>)</span>
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
            <td><strong><?= $team['points'] ?></strong></td>
            <td style="color: <?= $safety_color ?>; font-weight: bold; font-size: 12px;">
                <?= $safety_text ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <div style="margin-top: 20px; display: flex; gap: 20px; font-size: 12px; flex-wrap: wrap;">
        <div><span style="color: #43b581;">■</span> Champions League (1st-4th)</div>
        <div><span style="color: #5865F2;">■</span> Europa League (5th-6th)</div>
        <div><span style="color: #FFCD00;">■</span> Leeds United 🤍💛💙</div>
        <div><span style="color: #f04747;">■</span> Relegation (18th-20th)</div>
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
