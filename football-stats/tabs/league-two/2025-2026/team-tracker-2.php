<?php
// TEAM TRACKER DASHBOARD (League Two) - Data queries
require_once dirname(__DIR__, 3) . '/includes/team-tracker-helpers.php';

$tableView = football_stats_get_table_view_combined($db, 'L2', 'league_table_L2', $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$standings = $tableView['standings'];

$team = football_stats_get_tracker_team($standings);

if (!$team) {
    echo "<div class='panel'><h2>No team data found</h2></div>";
    return;
}

$teamName      = $team['team_name'];
$teamColors    = football_stats_get_team_colors($teamName);
$teamPrimary   = $teamColors['primary'];
$teamSecondary = $teamColors['secondary'];

// Halfway calculations
$halfway_point = 23;
$target_100pct = 20; // Ideal target
$target_75pct = 15;  // Minimum acceptable (75% rule)
$target_50pct = 10;  // Critical zone

$games_played = $team['played'];
$current_points = $team['points'];
$games_to_halfway = max(0, $halfway_point - $games_played);

// Current PPG
$ppg = $games_played > 0 ? $current_points / $games_played : 0;

// Projected halfway points
$projected_halfway = $current_points + ($ppg * $games_to_halfway);

// Progress percentages
$progress_100pct = ($current_points / $target_100pct) * 100;
$progress_75pct = ($current_points / $target_75pct) * 100;

// Points needed for each target
$points_to_100 = max(0, $target_100pct - $current_points);
$points_to_75 = max(0, $target_75pct - $current_points);

// PPG needed to hit targets
$ppg_needed_100 = $games_to_halfway > 0 ? $points_to_100 / $games_to_halfway : 0;
$ppg_needed_75 = $games_to_halfway > 0 ? $points_to_75 / $games_to_halfway : 0;

// Determine status for each target
if ($games_played >= $halfway_point) {
    // Already at halfway - check what we achieved
    if ($current_points >= $target_100pct) {
        $status_100 = 'HIT';
        $status_100_color = '#43b581';
        $status_100_icon = '✅';
        $status_100_text = 'TARGET HIT!';
    } else {
        $status_100 = 'MISSED';
        $status_100_color = '#faa61a';
        $status_100_icon = '⚠️';
        $status_100_text = 'Below ideal';
    }
    
    if ($current_points >= $target_75pct) {
        $status_75 = 'HIT';
        $status_75_color = '#43b581';
        $status_75_icon = '✅';
        $status_75_text = '75% RULE HIT!';
    } else {
        $status_75 = 'MISSED';
        $status_75_color = '#f04747';
        $status_75_icon = '🚨';
        $status_75_text = 'DANGER ZONE!';
    }
} else {
    // Before halfway - show progress
    if ($progress_100pct >= 100) {
        $status_100 = 'ACHIEVED';
        $status_100_color = '#43b581';
        $status_100_icon = '🏆';
        $status_100_text = 'EARLY HIT!';
    } elseif ($projected_halfway >= $target_100pct) {
        $status_100 = 'ON_TRACK';
        $status_100_color = '#43b581';
        $status_100_icon = '✅';
        $status_100_text = 'On track!';
    } elseif ($projected_halfway >= $target_75pct) {
        $status_100 = 'BELOW';
        $status_100_color = '#faa61a';
        $status_100_icon = '⚠️';
        $status_100_text = 'Below ideal';
    } else {
        $status_100 = 'DANGER';
        $status_100_color = '#f04747';
        $status_100_icon = '🚨';
        $status_100_text = 'Danger!';
    }
    
    if ($progress_75pct >= 100) {
        $status_75 = 'ACHIEVED';
        $status_75_color = '#43b581';
        $status_75_icon = '✅';
        $status_75_text = '75% ACHIEVED!';
    } elseif ($projected_halfway >= $target_75pct) {
        $status_75 = 'ON_TRACK';
        $status_75_color = '#43b581';
        $status_75_icon = '✅';
        $status_75_text = 'On track!';
    } elseif ($ppg_needed_75 <= 1.5) {
        $status_75 = 'TOUGH';
        $status_75_color = '#faa61a';
        $status_75_icon = '⚠️';
        $status_75_text = 'Needs work';
    } else {
        $status_75 = 'DANGER';
        $status_75_color = '#f04747';
        $status_75_icon = '🚨';
        $status_75_text = 'CRITICAL!';
    }
}

// Calculate gap to relegation
$relegation_zone = array_filter($standings, function($team) {
    return $team['position'] == 18;
});
$relegation_team = reset($relegation_zone);
$gap_to_relegation = $relegation_team ? $current_points - $relegation_team['points'] : 0;

// Full season projection
$projected_final = round($ppg * 46, 1);

// Safety threshold (historical: 38 points usually safe)
$safety_threshold = 46;
$points_to_safety = max(0, $safety_threshold - $current_points);
$games_remaining_total = 46 - $games_played;
$ppg_needed_safety = $games_remaining_total > 0 ? $points_to_safety / $games_remaining_total : 0;
?>

<!-- HERO SECTION -->
<div class="panel" style="background: linear-gradient(135deg, <?= $teamPrimary ?>, <?= $teamSecondary ?>); border: 3px solid #FFFFFF;">
    <div style="text-align: center;">
        <h1 style="color: #FFFFFF; font-size: 48px; margin: 10px 0;">
            ⚽ <?= htmlspecialchars($teamName) ?>
        </h1>
        <h2 style="color: #FFFFFF; font-size: 24px; margin: 10px 0; opacity: 0.85;">
            League Two 2025/26 · Team Dashboard
        </h2>
        <?php football_stats_render_team_selector($standings, $teamName); ?>
        <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', 'league-two', $currentSubTab ?? 'team-tracker-2'); ?>
    </div>
</div>

<!-- CURRENT STATUS -->
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="panel" style="background: #2e3136; text-align: center;">
        <h3 style="color: #888; font-size: 14px; margin: 0 0 10px 0;">CURRENT POSITION</h3>
        <div style="font-size: 64px; font-weight: bold; color: <?php 
            echo $team['position'] <= 10 ? '#43b581' : 
                ($team['position'] <= 17 ? '#faa61a' : '#f04747'); 
        ?>;">
            <?= $team['position'] ?>
        </div>
        <p style="color: #dcddde; font-size: 16px; margin: 10px 0 0 0;">
            out of 20 teams
        </p>
    </div>
    
    <div class="panel" style="background: #2e3136; text-align: center;">
        <h3 style="color: #888; font-size: 14px; margin: 0 0 10px 0;">CURRENT POINTS</h3>
        <div style="font-size: 64px; font-weight: bold; color: <?= $teamSecondary ?>;">
            <?= $current_points ?>
        </div>
        <p style="color: #dcddde; font-size: 16px; margin: 10px 0 0 0;">
            from <?= $games_played ?> games
        </p>
    </div>
    
    <div class="panel" style="background: #2e3136; text-align: center;">
        <h3 style="color: #888; font-size: 14px; margin: 0 0 10px 0;">GAP TO RELEGATION</h3>
        <div style="font-size: 64px; font-weight: bold; color: <?php 
            echo $gap_to_relegation >= 5 ? '#43b581' : 
                ($gap_to_relegation >= 2 ? '#faa61a' : '#f04747'); 
        ?>;">
            <?= $gap_to_relegation > 0 ? '+' : '' ?><?= $gap_to_relegation ?>
        </div>
        <p style="color: #dcddde; font-size: 16px; margin: 10px 0 0 0;">
            points clear of 18th
        </p>
    </div>
    
    <div class="panel" style="background: #2e3136; text-align: center;">
        <h3 style="color: #888; font-size: 14px; margin: 0 0 10px 0;">POINTS PER GAME</h3>
        <div style="font-size: 64px; font-weight: bold; color: <?php 
            echo $ppg >= 1.2 ? '#43b581' : 
                ($ppg >= 0.8 ? '#faa61a' : '#f04747'); 
        ?>;">
            <?= number_format($ppg, 2) ?>
        </div>
        <p style="color: #dcddde; font-size: 16px; margin: 10px 0 0 0;">
            average PPG
        </p>
    </div>
</div>

<!-- DUAL HALFWAY TARGETS - THE STAR FEATURE! -->
<div class="panel" style="background: linear-gradient(135deg, <?= $teamPrimary ?>, #2e3136); border: 3px solid <?= $teamSecondary ?>;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="color: #FFFFFF; font-size: 36px; margin: 0;">
            🎯 HALFWAY POINT TARGETS
        </h2>
        <p style="color: rgba(255,255,255,0.8); font-size: 16px; margin-top: 10px;">
            Game <?= $halfway_point ?> is the season's halfway point - here's what <?= htmlspecialchars($teamName) ?> needs
        </p>
        <?php if ($games_to_halfway > 0): ?>
            <div style="background: rgba(255,205,0,0.2); padding: 10px; border-radius: 6px; margin-top: 15px; display: inline-block;">
                <span style="color: <?= $teamSecondary ?>; font-weight: bold; font-size: 18px;">
                    ⏰ <?= $games_to_halfway ?> game<?= $games_to_halfway > 1 ? 's' : '' ?> remaining to halfway!
                </span>
            </div>
        <?php else: ?>
            <div style="background: rgba(67,181,129,0.2); padding: 10px; border-radius: 6px; margin-top: 15px; display: inline-block;">
                <span style="color: #43b581; font-weight: bold; font-size: 18px;">
                    ✅ Halfway point reached! Game <?= $games_played ?> of 38 complete
                </span>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- SIDE-BY-SIDE TARGET DISPLAY -->
    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
        
        <!-- 75% RULE (MINIMUM SURVIVAL TARGET) -->
        <div style="background: rgba(0,0,0,0.3); padding: 25px; border-radius: 12px; border: 3px solid <?= $status_75_color ?>;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="color: <?= $teamSecondary ?>; font-size: 14px; margin: 0 0 5px 0; letter-spacing: 1px;">
                    MINIMUM TARGET
                </h3>
                <h2 style="color: white; font-size: 32px; margin: 0;">
                    75% RULE
                </h2>
                <p style="color: rgba(255,255,255,0.7); font-size: 13px; margin: 10px 0 0 0;">
                    Minimum acceptable for survival
                </p>
            </div>
            
            <!-- TARGET: 15 POINTS -->
            <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <div style="color: #888; font-size: 14px; margin-bottom: 5px;">Target Points</div>
                <div style="font-size: 56px; font-weight: bold; color: white;">
                    <?= $target_75pct ?>
                </div>
                <div style="color: rgba(255,255,255,0.7); font-size: 13px; margin-top: 5px;">
                    by Game <?= $halfway_point ?>
                </div>
            </div>
            
            <!-- PROGRESS BAR -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: rgba(255,255,255,0.8); font-size: 13px;">Progress</span>
                    <span style="color: <?= $status_75_color ?>; font-weight: bold; font-size: 13px;">
                        <?= round($progress_75pct) ?>%
                    </span>
                </div>
                <div style="background: #40444b; border-radius: 6px; height: 24px; overflow: hidden; position: relative;">
                    <div style="background: <?= $status_75_color ?>; width: <?= min(100, $progress_75pct) ?>%; height: 100%; transition: width 0.3s;"></div>
                    <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px; font-weight: bold; color: white;">
                        <?= $current_points ?> / <?= $target_75pct ?> pts
                    </span>
                </div>
            </div>
            
            <!-- STATUS -->
            <div style="text-align: center; background: <?= $status_75_color ?>; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <div style="font-size: 32px; margin-bottom: 5px;"><?= $status_75_icon ?></div>
                <div style="color: white; font-weight: bold; font-size: 18px;">
                    <?= $status_75_text ?>
                </div>
            </div>
            
            <!-- DETAILS -->
            <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 6px;">
                <?php if ($games_to_halfway > 0): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: rgba(255,255,255,0.7); font-size: 13px;">Points needed:</span>
                        <span style="color: white; font-weight: bold;"><?= $points_to_75 ?> pts</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: rgba(255,255,255,0.7); font-size: 13px;">Games remaining:</span>
                        <span style="color: white; font-weight: bold;"><?= $games_to_halfway ?> games</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: rgba(255,255,255,0.7); font-size: 13px;">PPG needed:</span>
                        <span style="color: <?= $ppg_needed_75 <= 1.0 ? '#43b581' : ($ppg_needed_75 <= 1.5 ? '#faa61a' : '#f04747') ?>; font-weight: bold;">
                            <?= number_format($ppg_needed_75, 2) ?>
                        </span>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; color: rgba(255,255,255,0.8); font-size: 13px;">
                        Final halfway result: <strong style="color: white;"><?= $current_points ?> points</strong>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- SURVIVAL RATE -->
            <div style="margin-top: 15px; padding: 12px; background: rgba(67,181,129,0.1); border-radius: 6px; border-left: 3px solid #43b581;">
                <div style="color: #43b581; font-size: 12px; font-weight: bold; margin-bottom: 3px;">
                    📊 HISTORICAL SURVIVAL RATE
                </div>
                <div style="color: rgba(255,255,255,0.9); font-size: 13px;">
                    Teams hitting 15+ points at halfway: <strong style="color: #43b581;">85-90% survival</strong>
                </div>
            </div>
        </div>
        
        <!-- 100% RULE (IDEAL TARGET) -->
        <div style="background: rgba(0,0,0,0.3); padding: 25px; border-radius: 12px; border: 3px solid <?= $status_100_color ?>;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="color: <?= $teamSecondary ?>; font-size: 14px; margin: 0 0 5px 0; letter-spacing: 1px;">
                    IDEAL TARGET
                </h3>
                <h2 style="color: white; font-size: 32px; margin: 0;">
                    100% RULE
                </h2>
                <p style="color: rgba(255,255,255,0.7); font-size: 13px; margin: 10px 0 0 0;">
                    Full points-per-game target
                </p>
            </div>
            
            <!-- TARGET: 20 POINTS -->
            <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <div style="color: #888; font-size: 14px; margin-bottom: 5px;">Target Points</div>
                <div style="font-size: 56px; font-weight: bold; color: white;">
                    <?= $target_100pct ?>
                </div>
                <div style="color: rgba(255,255,255,0.7); font-size: 13px; margin-top: 5px;">
                    by Game <?= $halfway_point ?>
                </div>
            </div>
            
            <!-- PROGRESS BAR -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: rgba(255,255,255,0.8); font-size: 13px;">Progress</span>
                    <span style="color: <?= $status_100_color ?>; font-weight: bold; font-size: 13px;">
                        <?= round($progress_100pct) ?>%
                    </span>
                </div>
                <div style="background: #40444b; border-radius: 6px; height: 24px; overflow: hidden; position: relative;">
                    <div style="background: <?= $status_100_color ?>; width: <?= min(100, $progress_100pct) ?>%; height: 100%; transition: width 0.3s;"></div>
                    <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px; font-weight: bold; color: white;">
                        <?= $current_points ?> / <?= $target_100pct ?> pts
                    </span>
                </div>
            </div>
            
            <!-- STATUS -->
            <div style="text-align: center; background: <?= $status_100_color ?>; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <div style="font-size: 32px; margin-bottom: 5px;"><?= $status_100_icon ?></div>
                <div style="color: white; font-weight: bold; font-size: 18px;">
                    <?= $status_100_text ?>
                </div>
            </div>
            
            <!-- DETAILS -->
            <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 6px;">
                <?php if ($games_to_halfway > 0): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: rgba(255,255,255,0.7); font-size: 13px;">Points needed:</span>
                        <span style="color: white; font-weight: bold;"><?= $points_to_100 ?> pts</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: rgba(255,255,255,0.7); font-size: 13px;">Games remaining:</span>
                        <span style="color: white; font-weight: bold;"><?= $games_to_halfway ?> games</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: rgba(255,255,255,0.7); font-size: 13px;">PPG needed:</span>
                        <span style="color: <?= $ppg_needed_100 <= 1.0 ? '#43b581' : ($ppg_needed_100 <= 1.5 ? '#faa61a' : '#f04747') ?>; font-weight: bold;">
                            <?= number_format($ppg_needed_100, 2) ?>
                        </span>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; color: rgba(255,255,255,0.8); font-size: 13px;">
                        Final halfway result: <strong style="color: white;"><?= $current_points ?> points</strong>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- SURVIVAL RATE -->
            <div style="margin-top: 15px; padding: 12px; background: rgba(67,181,129,0.1); border-radius: 6px; border-left: 3px solid #43b581;">
                <div style="color: #43b581; font-size: 12px; font-weight: bold; margin-bottom: 3px;">
                    📊 HISTORICAL SURVIVAL RATE
                </div>
                <div style="color: rgba(255,255,255,0.9); font-size: 13px;">
                    Teams hitting 20+ points at halfway: <strong style="color: #43b581;">95%+ survival</strong>
                </div>
            </div>
        </div>
    </div>
    
    <!-- COMPARISON FOOTER -->
    <div style="margin-top: 25px; padding: 20px; background: rgba(255,205,0,0.1); border-radius: 8px; border: 2px solid <?= $teamSecondary ?>;">
        <h3 style="color: <?= $teamSecondary ?>; margin: 0 0 15px 0; text-align: center;">
            📊 What This Means For <?= htmlspecialchars($teamName) ?>
        </h3>
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <h4 style="color: white; margin: 0 0 8px 0; font-size: 14px;">✅ Hits 75% (15 pts):</h4>
                <ul style="color: rgba(255,255,255,0.9); font-size: 13px; line-height: 1.8; margin: 0; padding-left: 20px;">
                    <li>85-90% chance of survival</li>
                    <li>Need ~23 more points in second half</li>
                    <li>Can afford some bad results</li>
                    <li>Breathing room above relegation</li>
                </ul>
            </div>
            <div>
                <h4 style="color: white; margin: 0 0 8px 0; font-size: 14px;">🏆 Hits 100% (20 pts):</h4>
                <ul style="color: rgba(255,255,255,0.9); font-size: 13px; line-height: 1.8; margin: 0; padding-left: 20px;">
                    <li>95%+ chance of survival</li>
                    <li>Need ~18 more points in second half</li>
                    <li>Comfortable position</li>
                    <li>Likely finish mid-table</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- FULL SEASON PROJECTION -->
<div class="panel">
    <h2 style="color: <?= $teamSecondary ?>;">📈 Full Season Projection</h2>
    <p style="color: #888; font-size: 13px; margin-bottom: 20px;">
        Based on current form (<?= number_format($ppg, 2) ?> PPG)
    </p>
    
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
        <div style="background: #40444b; padding: 20px; border-radius: 8px;">
            <h3 style="color: #888; font-size: 14px; margin: 0 0 10px 0;">Projected Final Points</h3>
            <div style="font-size: 48px; font-weight: bold; color: <?= $projected_final >= 38 ? '#43b581' : ($projected_final >= 35 ? '#faa61a' : '#f04747') ?>;">
                <?= $projected_final ?>
            </div>
            <p style="color: #dcddde; font-size: 13px; margin: 10px 0 0 0;">
                At current <?= number_format($ppg, 2) ?> PPG
            </p>
        </div>
        
        <div style="background: #40444b; padding: 20px; border-radius: 8px;">
            <h3 style="color: #888; font-size: 14px; margin: 0 0 10px 0;">Points to 38 (Safety)</h3>
            <div style="font-size: 48px; font-weight: bold; color: <?= $points_to_safety <= 10 ? '#43b581' : ($points_to_safety <= 20 ? '#faa61a' : '#f04747') ?>;">
                <?= $points_to_safety ?>
            </div>
            <p style="color: #dcddde; font-size: 13px; margin: 10px 0 0 0;">
                Points needed (<?= $games_remaining_total ?> games left)
            </p>
        </div>
        
        <div style="background: #40444b; padding: 20px; border-radius: 8px;">
            <h3 style="color: #888; font-size: 14px; margin: 0 0 10px 0;">PPG Needed for Safety</h3>
            <div style="font-size: 48px; font-weight: bold; color: <?= $ppg_needed_safety <= 1.0 ? '#43b581' : ($ppg_needed_safety <= 1.5 ? '#faa61a' : '#f04747') ?>;">
                <?= number_format($ppg_needed_safety, 2) ?>
            </div>
            <p style="color: #dcddde; font-size: 13px; margin: 10px 0 0 0;">
                To reach 38 points
            </p>
        </div>
    </div>
    
    <!-- Projection context -->
    <div style="margin-top: 20px; padding: 15px; background: #40444b; border-radius: 8px; border-left: 4px solid #5865F2;">
        <h4 style="color: <?= $teamSecondary ?>; margin: 0 0 10px 0;">💡 Context</h4>
        <ul style="color: #dcddde; line-height: 2; margin: 0; padding-left: 20px;">
            <li><strong style="color: #43b581;">38 points</strong> is historically the "safe" threshold (80-85% survival)</li>
            <li><strong style="color: #faa61a;">35-37 points</strong> is the danger zone (50-70% survival)</li>
            <li><strong style="color: #f04747;">Below 35 points</strong> typically leads to relegation</li>
            <li>Leeds needs <strong style="color: <?= $teamSecondary ?>;"><?= number_format($ppg_needed_safety, 2) ?> PPG</strong> in remaining games to hit 38</li>
        </ul>
    </div>
</div>

<!-- DETAILED STATS -->
<div class="panel">
    <h2 style="color: <?= $teamSecondary ?>;">📊 Season Statistics</h2>
    
    <table>
        <tr>
            <th>Stat</th>
            <th>Value</th>
            <th>League Avg</th>
            <th>Rating</th>
        </tr>
        <tr>
            <td>Games Played</td>
            <td><strong><?= $team['played'] ?></strong></td>
            <td>-</td>
            <td>-</td>
        </tr>
        <tr>
            <td>Wins</td>
            <td><strong><?= $team['won'] ?></strong></td>
            <td>~8</td>
            <td><?= $team['won'] >= 8 ? '✅' : '⚠️' ?></td>
        </tr>
        <tr>
            <td>Draws</td>
            <td><strong><?= $team['drawn'] ?></strong></td>
            <td>~5</td>
            <td>-</td>
        </tr>
        <tr>
            <td>Losses</td>
            <td><strong><?= $team['lost'] ?></strong></td>
            <td>~5</td>
            <td><?= $team['lost'] <= 5 ? '✅' : '⚠️' ?></td>
        </tr>
        <tr>
            <td>Goals For</td>
            <td><strong><?= $team['gf'] ?></strong></td>
            <td>~25</td>
            <td><?= $team['gf'] >= 25 ? '✅' : ($team['gf'] >= 20 ? '⚠️' : '🚨') ?></td>
        </tr>
        <tr>
            <td>Goals Against</td>
            <td><strong><?= $team['ga'] ?></strong></td>
            <td>~25</td>
            <td><?= $team['ga'] <= 25 ? '✅' : ($team['ga'] <= 30 ? '⚠️' : '🚨') ?></td>
        </tr>
        <tr>
            <td>Goal Difference</td>
            <td style="color: <?= $team['gd'] >= 0 ? '#43b581' : '#f04747' ?>; font-weight: bold;">
                <?= $team['gd'] > 0 ? '+' : '' ?><?= $team['gd'] ?>
            </td>
            <td>~0</td>
            <td><?= $team['gd'] >= 0 ? '✅' : '🚨' ?></td>
        </tr>
        <tr style="background: rgba(255, 205, 0, 0.1);">
            <td><strong>Points</strong></td>
            <td><strong style="color: <?= $teamSecondary ?>; font-size: 18px;"><?= $team['points'] ?></strong></td>
            <td>~20</td>
            <td><?= $team['points'] >= 20 ? '✅' : ($team['points'] >= 15 ? '⚠️' : '🚨') ?></td>
        </tr>
        <tr>
            <td>Points Per Game</td>
            <td><strong><?= number_format($ppg, 2) ?></strong></td>
            <td>~1.00</td>
            <td><?= $ppg >= 1.0 ? '✅' : ($ppg >= 0.8 ? '⚠️' : '🚨') ?></td>
        </tr>
    </table>
</div>

<!-- LEAGUE CONTEXT -->
<div class="panel">
    <h2 style="color: <?= $teamPrimary ?>;">📍 <?= htmlspecialchars($teamName) ?> in the League Table</h2>
    <p style="color: #888; font-size: 13px; margin-bottom: 15px;">
        Teams around <?= htmlspecialchars($teamName) ?> in the standings
    </p>

    <table>
        <tr>
            <th>Pos</th>
            <th>Team</th>
            <th>P</th>
            <th>GD</th>
            <th>Pts</th>
            <th>Gap</th>
            <th>Projection</th>
        </tr>
        <?php
        $selected_pos = (int)$team['position'];
        $selected_pts = (int)$team['points'];
        $start_pos = max(1, $selected_pos - 5);
        $end_pos = min(24, $selected_pos + 5);

        foreach ($standings as $tableRow):
            if ($tableRow['position'] < $start_pos || $tableRow['position'] > $end_pos) continue;

            $is_selected_row = ($tableRow['team_name'] === $teamName);
            $is_relegation   = ($tableRow['position'] >= 18);

            $row_style = '';
            if ($is_selected_row) {
                $row_style = 'background: rgba(0,0,0,0.35); border-left: 4px solid ' . $teamPrimary . ';';
            } elseif ($is_relegation) {
                $row_style = 'background: rgba(244, 71, 71, 0.2); border-left: 4px solid #f04747;';
            }

            $row_gap  = (int)$tableRow['points'] - $selected_pts;
            $row_ppg2 = $tableRow['played'] > 0 ? $tableRow['points'] / $tableRow['played'] : 0;
            $row_proj2 = round($row_ppg2 * 46, 1);
        ?>
        <tr style="<?= $row_style ?>">
            <td><strong><?= $tableRow['position'] ?></strong></td>
            <td>
                <?php if ($is_selected_row): ?>
                    <strong style="color: <?= $teamSecondary ?>;">⭐ <?= htmlspecialchars($tableRow['team_name']) ?></strong>
                <?php else: ?>
                    <?= htmlspecialchars($tableRow['team_name']) ?>
                <?php endif; ?>
            </td>
            <td><?= $tableRow['played'] ?></td>
            <td style="color: <?= $tableRow['gd'] >= 0 ? '#43b581' : '#f04747' ?>;">
                <?= $tableRow['gd'] > 0 ? '+' : '' ?><?= $tableRow['gd'] ?>
            </td>
            <td><strong><?= $tableRow['points'] ?></strong></td>
            <td style="color: <?= $row_gap > 0 ? '#f04747' : ($row_gap < 0 ? '#43b581' : '#888') ?>;">
                <?php if (!$is_selected_row): ?>
                    <?= $row_gap > 0 ? '+' : '' ?><?= $row_gap ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td style="color: <?= $row_proj2 >= 46 ? '#43b581' : '#f04747' ?>; font-weight: bold;">
                <?= $row_proj2 ?> pts
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <div style="margin-top: 15px; padding: 15px; background: #40444b; border-radius: 8px;">
        <div style="display: flex; gap: 20px; font-size: 12px; flex-wrap: wrap; justify-content: center;">
            <div><span style="color: <?= $teamSecondary ?>;">■</span> Leeds United 🤍💛💙</div>
            <div><span style="color: <?= $teamSecondary ?>;">■</span> <?= htmlspecialchars($teamName) ?> ⭐</div>
            <div><span style="color: #f04747;">■</span> Relegation Zone (18th-20th)</div>
        </div>
    </div>
</div>

<!-- KEY INSIGHTS -->
<div class="panel" style="background: #2e3136; border-left: 4px solid <?= $teamSecondary ?>;">
    <h3 style="color: <?= $teamSecondary ?>;">💡 Key Insights</h3>
    <ul style="color: #dcddde; line-height: 2;">
        <li><strong>Current Status:</strong> <?= $team['position'] ?>th place with <?= $current_points ?> points from <?= $games_played ?> games</li>
        <li><strong>Gap to Safety:</strong> <?= $gap_to_relegation > 0 ? '+' : '' ?><?= $gap_to_relegation ?> points clear of 18th place (relegation zone)</li>
        <li><strong>75% Rule:</strong> <?= $status_75_text ?> (<?= round($progress_75pct) ?>% of 15-point target)</li>
        <li><strong>100% Rule:</strong> <?= $status_100_text ?> (<?= round($progress_100pct) ?>% of 20-point target)</li>
        <?php if ($games_to_halfway > 0): ?>
            <li><strong>Remaining to Halfway:</strong> <?= $games_to_halfway ?> game<?= $games_to_halfway > 1 ? 's' : '' ?> - need <?= $points_to_75 ?> points minimum (75% rule)</li>
        <?php endif; ?>
        <li><strong>Season Projection:</strong> On track for <?= $projected_final ?> points (need 38 for safety)</li>
        <li><strong>Form:</strong> Averaging <?= number_format($ppg, 2) ?> PPG - need <?= number_format($ppg_needed_safety, 2) ?> PPG for safety</li>
    </ul>
</div>

<!-- FOOTER BANNER -->
<div class="panel" style="background: linear-gradient(135deg, <?= $teamPrimary ?>, <?= $teamSecondary ?>); text-align: center;">
    <h2 style="color: white; font-size: 32px; margin: 0;">
        ⚽ <?= htmlspecialchars(strtoupper($teamName)) ?> ⚽
    </h2>
    <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin-top: 10px;">
        Keep the faith - survival is in our hands!
    </p>
</div>
