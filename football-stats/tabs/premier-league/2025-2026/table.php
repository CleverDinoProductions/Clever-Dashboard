<?php
require_once __DIR__ . '/../../../includes/table-view.php';

$tableView = football_stats_get_table_view_combined($db, 'PL', 'league_table_PL', $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$standings = $tableView['standings'];
$last_update = $tableView['last_update'];

// Safety calculation
$halfway_games = 19; // Halfway point in season
$safety_target_halfway = 20; // Points needed by game 19 to stay safe
$total_games = 38; // Total games in season
$max_regular_mw = 38; // Playoff matches have matchweek > 38
if (isset($tableView['active_matchweek'])) {
    $max_regular_mw = min($max_regular_mw, (int)$tableView['active_matchweek']);
}

// Table filter
$table_filter = isset($_GET['table_filter']) && in_array($_GET['table_filter'], ['first_half', 'second_half', 'home', 'away'], true) ? $_GET['table_filter'] : 'all';
$_split_season = $tableView['active_season_label'] ?? ($currentMainTab ?? '2025-2026');
if ($table_filter !== 'all') {
    $filteredStandings = football_stats_compute_filtered_standings($db, 'PL', $_split_season, $table_filter, $halfway_games, 'league_table_PL', $max_regular_mw);
    if (!empty($filteredStandings)) {
        $standings = $filteredStandings;
    }
    $total_games = ($table_filter === 'first_half') ? $halfway_games : (($table_filter === 'second_half') ? ($total_games - $halfway_games) : (int)($total_games / 2));
}
$homeStandings = football_stats_compute_filtered_standings($db, 'PL', $_split_season, 'home', $halfway_games, 'league_table_PL', $max_regular_mw);
$awayStandings = football_stats_compute_filtered_standings($db, 'PL', $_split_season, 'away', $halfway_games, 'league_table_PL', $max_regular_mw);
$safety_target_magic = 40; // Magic number for safety
$safety_target_average = 36; // Average Points needed by end of season to stay safe
$safety_target_low = 34; // Low safety target
$safety_target_recent_low = 27; // Recent low safety target

// Team nicknames, abbreviations, and COMMON NAMES
require_once __DIR__ . '/../../../includes/team-info.php';
$team_info = $team_info_PL;

/**
 * Evaluates position highlight classes for min/max thresholds
 */
function get_table_row_style(array $team, int $uclMin = 4, int $uclMax = 5, int $uelMin = 5, int $uelMax = 7, int $ueclMax = 8, int $relegationMin = 18): string {
    $pos = (int)$team['position'];
    $classes = [];

    // Left Border & Fill (Minimum Guaranteed Spots)
    if ($pos <= $uclMin) {
        $classes[] = 'row-ucl-min';
    } elseif ($pos <= $uelMin) {
        $classes[] = 'row-uel-min';
    }

    // Right Border (Maximum Potential Spots via Cup Drop-Downs)
    if ($pos <= $uclMax) {
        $classes[] = 'row-ucl';
    } elseif ($pos <= $uelMax) {
        $classes[] = 'row-uel';
    } elseif ($pos <= $ueclMax) {
        $classes[] = 'row-uecl';
    }

    // Relegation Check
    if ($pos >= $relegationMin) {
        $classes[] = 'row-relegation';
    }

    return !empty($classes) ? 'class="' . implode(' ', $classes) . '"' : '';
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

:root {
  --color-ucl: #006400;       /* Champions League: Dark Green */
  --color-uel: #5865F2;       /* Europa League: Blurple / Soft Blue */
  --color-uecl: #FFCD00;      /* Conference League: Gold / Yellow */
  --color-relegation: #f04747;/* Relegation: Red */
}

/* Left Borders & Background Fills (Minimum Spots) */
.row-ucl-min { background: rgba(0, 100, 0, 0.15); border-left: 4px solid var(--color-ucl); }
.row-uel-min { background: rgba(88, 101, 242, 0.15); border-left: 4px solid var(--color-uel); }
.row-uecl-min { background: rgba(255, 205, 0, 0.15); border-left: 4px solid var(--color-uecl); }

/* Right Borders (Maximum Potential Spots) */
.row-ucl { border-right: 4px solid var(--color-ucl); }
.row-uel { border-right: 4px solid var(--color-uel); }
.row-uecl { border-right: 4px solid var(--color-uecl); }

/* Relegation */
.row-relegation { background: rgba(240, 71, 71, 0.15); border-left: 4px solid var(--color-relegation); }
</style>

<div class="panel">
    <h2>Premier League Table <?= $tableView['active_season_label']?></h2>
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
            
            // Dynamic row highlight class (Defaults: UCL 1-4, UEL 5-6, UECL 7, Relegation 18-20)
            $row_attribute = get_table_row_style($team, 4, 5, 5, 7, 8, 18);

            //remaining games
            $games_remaining = max(0, $total_games - $team['played']);
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
            $row_style = '';
            if ($team['position'] >= 18) {
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
        <tr <?= $row_attribute ?>>
            <td><strong><?= $team['position'] ?></strong></td>
            <td>
                <div class="team-cell">
                    <img src="<?= htmlspecialchars($team['team_crest']) ?>" 
                        alt="<?= htmlspecialchars($info['name']) ?> crest" 
                        class="team-crest" 
                        onerror="this.style.display='none'">
                    <span class="team-name">
                        <span class="team-official"><?= htmlspecialchars($info['name']) ?></span>
                        <?php if ($show_common): ?>
                            <span class="team-common">(<?= $team['team_name'] ?>)</span>
                        <?php endif; ?>
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
