<?php
// TEAM SURVIVAL TRACKER (Premier League) - Data queries
require_once dirname(__DIR__, 3) . '/includes/team-tracker-helpers.php';

$tableView = football_stats_get_table_view_combined($db, 'PL', 'league_table_PL', $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$standings = $tableView['standings'];

$team = football_stats_get_tracker_team($standings, 'Leeds United');

if (!$team) {
    echo "<div class='panel' style='border: 2px solid #f04747;'>";
    echo "<h2 style='color: #f04747;'>⚠️ No Team Data Found</h2>";
    echo "<p>Please check that Premier League data has been loaded.</p>";
    $teams = array_column($standings, 'team_name');
    echo "<pre>" . implode("\n", $teams) . "</pre>";
    echo "</div>";
    return;
}

$teamName      = $team['team_name'];
$teamColors    = football_stats_get_team_colors($teamName);
$teamPrimary   = $teamColors['primary'];
$teamSecondary = $teamColors['secondary'];
$teamTextColor = football_stats_get_best_text_color($teamPrimary, $teamSecondary);

// === HALFWAY POINT SAFETY CALCULATIONS ===
$halfway_point = 19; // Halfway through 38-game season
$games_played = $team['played'];
$games_remaining = 38 - $games_played;
$games_to_halfway = max(0, $halfway_point - $games_played);

// Check if we're before or after halfway
$is_first_half = $games_played <= $halfway_point;

if ($is_first_half) {
    // FIRST HALF: Target is 20 points by game 19
    $safety_target = 20;
    $points_to_safety = max(0, $safety_target - $team['points']);
    $ppg_needed = $games_to_halfway > 0 ? round($points_to_safety / $games_to_halfway, 2) : 0;
    $target_label = "20 pts by Halfway (Game 19)";
    $games_label = $games_to_halfway . " games to halfway";
} else {
    // SECOND HALF: Target is 38 points for full season safety based on 38 points over games played so far hence minimum of 1.0 ppg
    $safety_target = 38;
    $points_to_safety = max(0, $safety_target - $team['points']);
    $ppg_needed = $games_remaining > 0 ? round($points_to_safety / $games_remaining, 2) : 0;
    $target_label = "38 pts for Full Season Safety";
    $games_label = $games_remaining . " games remaining";
}

// === SEASON POINT SAFETY CALCULATIONS ===

$half_season_safety_target = 30;
$half_season_points_to_safety = max(0, $half_season_safety_target - $team['points']);
if ($games_remaining > 0) {
    $half_season_ppg_needed = round($half_season_points_to_safety / $games_remaining, 2);
} else {
    $half_season_ppg_needed = 0;
}

$full_season_safety_target = 38;
$full_season_points_to_safety = max(0, $full_season_safety_target - $team['points']);
if ($games_remaining > 0) {
    $full_season_ppg_needed = round($full_season_points_to_safety / $games_remaining, 2);
} else {
    $full_season_ppg_needed = 0;
}

$extended_season_safety_target = 40;
$extended_season_points_to_safety = max(0, $extended_season_safety_target - $team['points']);
if ($games_remaining > 0) {
    $extended_season_ppg_needed = round($extended_season_points_to_safety / $games_remaining, 2);
} else {
    $extended_season_ppg_needed = 0;
}

$guaranteed_season_safety_target = 42;
$guaranteed_season_points_to_safety = max(0, $guaranteed_season_safety_target - $team['points']);
if ($games_remaining > 0) {
    $guaranteed_season_ppg_needed = round($guaranteed_season_points_to_safety / $games_remaining, 2);
} else {
    $guaranteed_season_ppg_needed = 0;
}


$mathematical_season_safety_target = 45;
$mathematical_season_points_to_safety = max(0, $mathematical_season_safety_target - $team['points']);
if ($games_remaining > 0) {
    $mathematical_season_ppg_needed = round($mathematical_season_points_to_safety / $games_remaining, 2);
} else {
    $mathematical_season_ppg_needed = 0;
}


// Calculate 75% rule metrics
$halfway_progress_pct = ($team['points'] / 20) * 100;
$target_75pct = 15; // 75% of 20
$target_100pct = 20; // 100% of 20
$points_to_75pct = max(0, $target_75pct - $team['points']);
$points_to_100pct = max(0, $target_100pct - $team['points']);

// Projected final points
$current_ppg = $games_played > 0 ? $team['points'] / $games_played : 0;
$projected_points = round($current_ppg * 38, 1);

// Projected points at halfway (if before halfway)
if ($is_first_half && $games_to_halfway > 0) {
    $projected_halfway_points = $team['points'] + ($current_ppg * $games_to_halfway);
    $projected_halfway_pct = ($projected_halfway_points / 20) * 100;
} else {
    $projected_halfway_points = $team['points'];
    $projected_halfway_pct = $halfway_progress_pct;
}

// Halfway point status with 75% rule
if ($games_played >= $halfway_point) {
    // After halfway - check what we had
    if ($team['points'] >= 20) {
        $halfway_status = "✅ Hit 20-point target at halfway!";
        $halfway_color = "#43b581";
        $survival_chance = "95%+";
    } elseif ($team['points'] >= 15) {
        $halfway_status = "✅ Hit 75% target (" . $team['points'] . " pts at halfway)";
        $halfway_color = "#43b581";
        $survival_chance = "85-90%";
    } else {
        $halfway_status = "⚠️ Below 75% target at halfway";
        $halfway_color = "#f04747";
        $survival_chance = "50-60%";
    }
} else {
    // Before halfway
    if ($halfway_progress_pct >= 100) {
        $halfway_status = "🔥 ALREADY HIT 20-POINT TARGET!";
        $halfway_color = "#43b581";
        $survival_chance = "95%+";
    } elseif ($halfway_progress_pct >= 75) {
        $halfway_status = "✅ AHEAD OF SCHEDULE! " . $team['points'] . " pts (" . round($halfway_progress_pct) . "%)";
        $halfway_color = "#43b581";
        $survival_chance = "90%+";
    } elseif ($halfway_progress_pct >= 50) {
        $halfway_status = "⚠️ Need to reach 75% target (" . $points_to_75pct . " pts needed)";
        $halfway_color = "#faa61a";
        $survival_chance = "60-70%";
    } else {
        $halfway_status = "🚨 Critical! Far from 75% target";
        $halfway_color = "#f04747";
        $survival_chance = "40% or less";
    }
}

// Season safety status
if ($team['points'] >= 45) {
    $full_season_status = "🏆 Mathematical Safety with " . $team['points'] . " pts!";
    $full_season_color = "#43b581";
    $survival_chance = "100%";
} else if ($team['points'] >= 43) {
    $full_season_status = "🏆 Guaranteed Safety with " . $team['points'] . " pts!";
    $full_season_color = "#43b581";
    $survival_chance = "99%";
} elseif ($team['points'] >= 40) {
    $full_season_status = "🏆 Extended Season Safety with " . $team['points'] . " pts!";
    $full_season_color = "#43b581";
    $survival_chance = "98%+";
} elseif ($team['points'] >= 38) {
    $full_season_status = "✅ Full Season Safety with " . $team['points'] . " pts!";
    $full_season_color = "#43b581";
    $survival_chance = "95%+";
} elseif ($team['points'] >= 30) {
    $full_season_status = "✅ On track for safety with " . $team['points'] . " pts!";
    $full_season_color = "#43b581";
} elseif ($team['points'] >= 20) {
    $full_season_status = "⚠️ Need to reach 38-point target (currently at " . $team['points'] . " pts)";
    $full_season_color = "#faa61a";
} else {
    $full_season_status = "🚨 Critical! Far from 38-point target";
    $full_season_color = "#f04747";
}

$bottom_six = array_values(array_filter($standings, function ($teamRow) {
    return (int) $teamRow['position'] >= 15;
}));

$eighteenth = null;
foreach ($standings as $teamRow) {
    if ((int) $teamRow['position'] === 18) {
        $eighteenth = $teamRow;
        break;
    }
}
$gap_to_18th = (int) $team['points'] - (int) ($eighteenth['points'] ?? 0);

// Survival status
if ($team['position'] <= 17 && $team['points'] >= $safety_target) {
    $survival_status = 'SAFE';
    $status_color = '#43b581';
    $status_icon = '✅';
} elseif ($team['position'] <= 17 && $halfway_progress_pct >= 75) {
    $survival_status = 'ON TRACK';
    $status_color = '#43b581';
    $status_icon = '⚠️';
} elseif ($team['position'] <= 17) {
    $survival_status = 'FIGHTING';
    $status_color = '#faa61a';
    $status_icon = '⚠️';
} else {
    $survival_status = 'DANGER ZONE';
    $status_color = '#f04747';
    $status_icon = '🚨';
}
?>

<!-- TEAM SURVIVAL TRACKER VIEW -->

<!-- Hero Section -->
<div class="panel" style="border: 3px solid <?= $teamSecondary ?>; background: linear-gradient(135deg, <?= $teamPrimary ?> 0%, #2e3136 100%);">
    <div style="text-align: center;">
        <h1 style="color: #FFFFFF; font-size: 48px; margin: 0; text-shadow: 2px 2px 8px rgba(0,0,0,0.8);">
             <img src="<?= htmlspecialchars($team['team_crest']) ?>" style="height: 48px; vertical-align: middle;"> <?= htmlspecialchars($teamName) ?>
        </h1>
        <h2 style="color: <?= $teamTextColor ?>; font-size: 32px; margin: 10px 0; text-shadow: 2px 2px 6px rgba(0,0,0,0.8);">
            Survival Tracker
        </h2>
        <div style="background: <?php echo $status_color; ?>; display: inline-block; padding: 15px 40px; border-radius: 8px; margin-top: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.4);">
            <span style="font-size: 28px; font-weight: bold; color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">
                <?php echo $status_icon; ?> <?php echo $survival_status; ?>
            </span>
        </div>
        <div style="margin-top: 15px; background: <?php echo $halfway_color; ?>; display: inline-block; padding: 10px 25px; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.4);">
            <span style="font-size: 16px; font-weight: bold; color: white; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                <?php echo $halfway_status; ?>
            </span>
        </div>
        <div style="margin-top: 10px; background: <?php echo $full_season_color; ?>; display: inline-block; padding: 10px 25px; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.4);">
            <span style="font-size: 16px; font-weight: bold; color: white; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                <?php echo $full_season_status; ?>
            </span>
        </div>
        <div style="margin-top: 10px;">
            <span style="background: rgba(0,0,0,0.5); padding: 8px 20px; border-radius: 6px; color: white; font-size: 14px; font-weight: bold; border: 2px solid rgba(255,255,255,0.3);">
                📊 Survival Probability: <strong style="color: <?= $teamTextColor ?>;"><?php echo $survival_chance; ?></strong>
            </span>
        </div>
        <?php football_stats_render_team_selector($standings, $teamName); ?>
        <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', $currentLeague ?? 'premier-league', $currentSubTab ?? 'leeds'); ?>
    </div>
</div>

<?php if ($halfway_progress_pct >= 75 && $is_first_half): ?>
<!-- Outstanding Progress Banner -->
<div class="panel" style="background: linear-gradient(135deg, #43b581, #57f287); border: 3px solid <?= $teamSecondary ?>; box-shadow: 0 4px 12px rgba(67,181,129,0.4);">
    <div style="text-align: center;">
        <h2 style="color: white; font-size: 32px; margin: 0; text-shadow: 2px 2px 6px rgba(0,0,0,0.3);">
            🔥 AHEAD OF SCHEDULE! 🔥
        </h2>
        <p style="color: white; font-size: 18px; margin: 10px 0; text-shadow: 1px 1px 4px rgba(0,0,0,0.3); font-weight: bold;">
            <?php echo $team['points']; ?> points after <?php echo $games_played; ?> games - already at <?php echo round($halfway_progress_pct); ?>% of halfway target!
        </p>
        <?php if ($games_to_halfway > 0): ?>
        <p style="color: white; font-size: 16px; margin: 5px 0; text-shadow: 1px 1px 3px rgba(0,0,0,0.3); font-weight: bold;">
            Still <?php echo $games_to_halfway; ?> games to go before halfway - excellent position! 💪
        </p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Season Progress Indicator -->
<div class="panel" style="background: #2e3136;">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">📅 Season Progress</h2>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
        <div style="text-align: center; flex: 1; min-width: 100px;">
            <div style="font-size: 14px; color: #FFFFFF; font-weight: bold;">Games Played</div>
            <div style="font-size: 32px; font-weight: bold; color: #5865F2; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);"><?php echo $games_played; ?></div>
        </div>
        <div style="font-size: 36px; color: #5865F2;">📊</div>
        <div style="text-align: center; flex: 1; min-width: 100px;">
            <div style="font-size: 14px; color: #FFFFFF; font-weight: bold;">Halfway Point</div>
            <div style="font-size: 32px; font-weight: bold; color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);"><?php echo $halfway_point; ?></div>
        </div>
        <div style="font-size: 36px; color: #43b581;">🏁</div>
        <div style="text-align: center; flex: 1; min-width: 100px;">
            <div style="font-size: 14px; color: #FFFFFF; font-weight: bold;">Full Season</div>
            <div style="font-size: 32px; font-weight: bold; color: #43b581; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">38</div>
        </div>
    </div>
    
    <!-- Progress bar showing season progress -->
    <div style="background: #40444b; border-radius: 8px; height: 30px; position: relative; overflow: hidden; border: 2px solid #5865F2;">
        <?php $season_progress = ($games_played / 38) * 100; ?>
        <div style="background: linear-gradient(90deg, #5865F2, <?= $teamSecondary ?>); width: <?php echo $season_progress; ?>%; height: 100%; transition: width 0.5s ease;"></div>
        <?php if ($games_played < $halfway_point): ?>
            <div style="position: absolute; left: 50%; top: 0; bottom: 0; width: 2px; background: <?= $teamSecondary ?>; opacity: 0.8; box-shadow: 0 0 10px rgba(255,205,0,0.6);"></div>
            <div style="position: absolute; left: 50%; top: -28px; font-size: 12px; color: <?= $teamTextColor ?>; font-weight: bold; transform: translateX(-50%); background: rgba(0,0,0,0.8); padding: 3px 8px; border-radius: 4px; border: 1px solid <?= $teamSecondary ?>;">
                ▼ Halfway
            </div>
        <?php endif; ?>
    </div>
    <p style="color: #FFFFFF; font-size: 13px; margin-top: 10px; text-align: center; font-weight: bold;">
        <?php echo $is_first_half ? "First half of season" : "Second half of season"; ?> • 
        <?php echo round($season_progress, 1); ?>% complete
    </p>
</div>

<!-- Key Stats Grid -->
<div class="grid">
    <!-- Current Position -->
    <div class="panel" style="background: #40444b; border-left: 4px solid <?php echo $status_color; ?>;">
        <h3 style="color: <?= $teamTextColor ?>; font-size: 16px; margin-bottom: 10px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">Current Position</h3>
        <div style="font-size: 64px; font-weight: bold; color: <?php echo $status_color; ?>; text-align: center; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
            #<?php echo $team['position']; ?>
        </div>
        <p style="text-align: center; color: #FFFFFF; font-size: 14px; font-weight: bold;">
            <?php echo abs($gap_to_18th); ?> points 
            <?php echo $gap_to_18th >= 0 ? 'above' : 'below'; ?> relegation zone
        </p>
    </div>

    <!-- Current Points -->
    <div class="panel" style="background: #40444b; border-left: 4px solid #5865F2;">
        <h3 style="color: <?= $teamTextColor ?>; font-size: 16px; margin-bottom: 10px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">Current Points</h3>
        <div style="font-size: 64px; font-weight: bold; color: #5865F2; text-align: center; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
            <?php echo $team['points']; ?>
        </div>
        <p style="text-align: center; color: #FFFFFF; font-size: 14px; font-weight: bold;">
            From <?php echo $games_played; ?> games (<?php echo number_format($current_ppg, 2); ?> PPG)
        </p>
    </div>

    <!-- Points to Target -->
    <div class="panel" style="background: #40444b; border-left: 4px solid #faa61a;">
        <h3 style="color: <?= $teamTextColor ?>; font-size: 16px; margin-bottom: 10px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            <?php echo $is_first_half ? "To Halfway Target" : "To Full Safety"; ?>
        </h3>
        <div style="font-size: 64px; font-weight: bold; color: #faa61a; text-align: center; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
            <?php echo $points_to_safety; ?>
        </div>
        <p style="text-align: center; color: #FFFFFF; font-size: 14px; font-weight: bold;">
            Target: <?php echo $safety_target; ?> points
        </p>
    </div>

    <!-- Points to Halfway Safety Target -->
    <div class="panel" style="background: #40444b; border-left: 4px solid #43b581;">
        <h3 style="color: <?= $teamTextColor ?>; font-size: 16px; margin-bottom: 10px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">Points to Halfway Safety Target</h3>
        <div style="font-size: 64px; font-weight: bold; color: #faa61a; text-align: center; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
            <?php echo $half_season_points_to_safety; ?>
        </div>
        <p style="text-align: center; color: #FFFFFF; font-size: 14px; font-weight: bold;">
            Target: <?php echo $half_season_safety_target; ?> points
        </p>
    </div>

    <!-- Points to Full Season Target-->
    <div class="panel" style="background: #40444b; border-left: 4px solid #faa61a;">
        <h3 style="color: <?= $teamTextColor ?>; font-size: 16px; margin-bottom: 10px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">Points to Season Safety Target</h3>
        <div style="font-size: 64px; font-weight: bold; color: #faa61a; text-align: center; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
            <?php echo $full_season_points_to_safety; ?>
        </div>
        <p style="text-align: center; color: #FFFFFF; font-size: 14px; font-weight: bold;">
            Target: <?php echo $full_season_safety_target; ?> points
        </p>
    </div>

    <!-- Extented Season Target -->
    <div class="panel" style="background: #40444b; border-left: 4px solid #faa61a;">
        <h3 style="color: <?= $teamTextColor ?>; font-size: 16px; margin-bottom: 10px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">Extended Season Safety Target</h3>
        <div style="font-size: 64px; font-weight: bold; color: #faa61a; text-align: center; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
            <?php echo $extended_season_points_to_safety; ?>
        </div>
            <p style="text-align: center; color: #FFFFFF; font-size: 14px; font-weight: bold;">
            Target: <?php echo $extended_season_safety_target; ?> points
        </p>
    </div>

    <!-- Guaranteed Season Target -->
    <div class="panel" style="background: #40444b; border-left: 4px solid #faa61a;">
        <h3 style="color: <?= $teamTextColor ?>; font-size: 16px; margin-bottom: 10px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">Guaranteed Season Safety Target</h3>
        <div style="font-size: 64px; font-weight: bold; color: #faa61a; text-align: center; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
            <?php echo $guaranteed_season_points_to_safety; ?>
        </div>
        <p style="text-align: center; color: #FFFFFF; font-size: 14px; font-weight: bold;">
            Target: <?php echo $guaranteed_season_safety_target; ?> points
        </p>
    </div>

    <!-- Mathematical Season Target -->
    <div class="panel" style="background: #40444b; border-left: 4px solid #faa61a;">
        <h3 style="color: <?= $teamTextColor ?>; font-size: 16px; margin-bottom: 10px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">Mathematical Season Safety Target</h3>
        <div style="font-size: 64px; font-weight: bold; color: #faa61a; text-align: center; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
            <?php echo $mathematical_season_points_to_safety; ?>
        </div>
        <p style="text-align: center; color: #FFFFFF; font-size: 14px; font-weight: bold;">
            Target: <?php echo $mathematical_season_safety_target; ?> points
        </p>
    </div>

    <!-- Games Remaining (to halfway) -->
    <div class="panel" style="background: #40444b; border-left: 4px solid #43b581;">
        <h3 style="color: <?= $teamTextColor ?>; font-size: 16px; margin-bottom: 10px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            Games to Halfway
        </h3>
        <div style="font-size: 64px; font-weight: bold; color: #43b581; text-align: center; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
            <?php echo $games_to_halfway ?>
        </div>
        <p style="text-align: center; color: #FFFFFF; font-size: 14px; font-weight: bold;">
            PPG needed: <?php echo $ppg_needed; ?>
        </p>
    </div>

    <!-- Games Remaining (to target) -->
    <div class="panel" style="background: #40444b; border-left: 4px solid #43b581;">
        <h3 style="color: <?= $teamTextColor ?>; font-size: 16px; margin-bottom: 10px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            Games remaining</h3>
        <div style="font-size: 64px; font-weight: bold; color: #43b581; text-align: center; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
            <?php echo $games_remaining; ?>
        </div>
        <p style="text-align: center; color: #FFFFFF; font-size: 14px; font-weight: bold;">
            PPG needed for Full Season: <?php echo $full_season_ppg_needed; ?>
        </p>
        <p style="text-align: center; color: #FFFFFF; font-size: 14px; font-weight: bold;">
            PPG needed for Extended Season: <?php echo $extended_season_ppg_needed; ?>
        </p>
        <p style="text-align: center; color: #FFFFFF; font-size: 14px; font-weight: bold;">
            PPG needed for Guaranteed Season: <?php echo $guaranteed_season_ppg_needed; ?>
        </p>
        <p style="text-align: center; color: #FFFFFF; font-size: 14px; font-weight: bold;">
            PPG needed for Mathematical Season: <?php echo $mathematical_season_ppg_needed; ?>
        </p>
    </div>
</div>

<!-- 75% Rule Progress Bar -->
<div class="panel">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">📊 Halfway Point Progress (20-Point Target)</h2>
    <p style="color: #FFFFFF; font-size: 13px; margin-bottom: 15px; font-weight: bold;">
        The <strong style="color: <?= $teamTextColor ?>;">75% Rule</strong>: Teams with 15+ points (75% of 20) at halfway have an 85-90% survival rate
    </p>
    
    <div style="background: #40444b; border-radius: 8px; height: 50px; position: relative; overflow: hidden; margin-bottom: 10px; border: 2px solid #43b581;">
        <?php 
        $progress_bar_pct = min(100, $halfway_progress_pct);
        
        // Color based on 75% rule
        if ($progress_bar_pct >= 100) {
            $bar_color = "linear-gradient(90deg, #43b581, #57f287)";
        } elseif ($progress_bar_pct >= 75) {
            $bar_color = "linear-gradient(90deg, #43b581, #5865F2)";
        } elseif ($progress_bar_pct >= 50) {
            $bar_color = "linear-gradient(90deg, #faa61a, #fee75c)";
        } else {
            $bar_color = "linear-gradient(90deg, #f04747, #ff6b6b)";
        }
        ?>
        
        <div style="background: <?php echo $bar_color; ?>; width: <?php echo $progress_bar_pct; ?>%; height: 100%; transition: width 0.5s ease;"></div>
        
        <!-- 75% marker line -->
        <div style="position: absolute; left: 75%; top: 0; bottom: 0; width: 3px; background: white; opacity: 0.9; box-shadow: 0 0 10px rgba(255,255,255,0.8);"></div>
        <div style="position: absolute; left: 75%; top: -32px; font-size: 13px; color: white; font-weight: bold; transform: translateX(-50%); background: rgba(0,0,0,0.9); padding: 4px 10px; border-radius: 4px; border: 2px solid white;">
            ▼ 75% (15 pts)
        </div>
        
        <!-- 100% marker line -->
        <div style="position: absolute; right: 0; top: 0; bottom: 0; width: 2px; background: #43b581; opacity: 0.8; box-shadow: 0 0 10px rgba(67,181,129,0.6);"></div>
        <div style="position: absolute; right: 0; top: -32px; font-size: 13px; color: #43b581; font-weight: bold; transform: translateX(50%); background: rgba(0,0,0,0.9); padding: 4px 10px; border-radius: 4px; border: 2px solid #43b581;">
            ▼ 100% (20 pts)
        </div>
        
        <!-- Current progress text -->
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; color: white; font-size: 20px; text-shadow: 2px 2px 6px rgba(0,0,0,0.9);">
            <?php echo $team['points']; ?> / 20 points (<?php echo round($halfway_progress_pct); ?>%)
        </div>
    </div>
    
    <div style="margin-top: 15px; text-align: center;">
        <span style="background: <?php echo $halfway_color; ?>; padding: 10px 20px; border-radius: 6px; font-weight: bold; color: white; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            <?php 
            if ($halfway_progress_pct >= 100) {
                echo "🏆 100% Target Hit! Exceptional!";
            } elseif ($halfway_progress_pct >= 75) {
                echo "⚠️ Need " . $points_to_100pct . " more to hit 100%";
            } else {
                echo "⚠️ Need " . $points_to_75pct . " more to hit 75%";
            }
            ?>
        </span>
    </div>
</div>

<!-- 30 pts progress bars -->
<div class="panel">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">📊 Halfway Safety Progress (30-Point Target)</h2>
    <p style="color: #FFFFFF; font-size: 13px; margin-bottom: 15px; font-weight: bold;">
        Target: 30 points for half season safety (historically 90%+ survival rate)
    </p> 
    <div style="background: #40444b; border-radius: 8px; height: 50px; position: relative; overflow: hidden; margin-bottom: 10px; border: 2px solid #43b581;">
        <?php 
        $half_season_progress_pct = ($team['points'] / 30) * 100;
        
        // Color based on proximity to 30 points
        if ($half_season_progress_pct >= 100) {
            $bar_color = "linear-gradient(90deg, #43b581, #57f287)";
        } elseif ($half_season_progress_pct >= 75) {
            $bar_color = "linear-gradient(90deg, #43b581, #5865F2)";
        } elseif ($half_season_progress_pct >= 50) {
            $bar_color = "linear-gradient(90deg, #faa61a, #fee75c)";
        } else {
            $bar_color = "linear-gradient(90deg, #f04747, #ff6b6b)";
        }
        ?>

        <div style="background: <?php echo $bar_color; ?>; width: <?php echo min(100, $half_season_progress_pct); ?>%; height: 100%; transition: width 0.5s ease;"></div>
        
        <!-- 30-point marker line -->
        <div style="position: absolute; right: 0; top: 0; bottom: 0; width: 3px; background: #43b581; opacity: 0.8; box-shadow: 0 0 10px rgba(67,181,129,0.6);"></div>
        <div style="position: absolute; right: 0; top: -32px; font-size: 13px; color: #43b581; font-weight: bold; transform: translateX(50%); background: rgba(0,0,0,0.9); padding: 4px 10px; border-radius: 4px; border: 2px solid #43b581;">
            ▼ Halfway Safety (30 pts)
        </div>
        
        <!-- Current progress text -->
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; color: white; font-size: 20px; text-shadow: 2px 2px 6px rgba(0,0,0,0.9);">
            <?php echo $team['points']; ?> / 30 points (<?php echo round($half_season_progress_pct); ?>%)
        </div>
    </div>
    <div style="margin-top: 15px; text-align: center;">
        <span style="background: <?php echo $status_color; ?>; padding: 10px 20px; border-radius: 6px; font-weight: bold; color: white; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            <?php 
            if ($half_season_progress_pct >= 100) {
                echo "🏆 Half Season Target Hit! Exceptional!";
            } elseif ($half_season_progress_pct >= 50) {
                echo "⚠️ Need " . (30 - $team['points']) . " more to hit safety";
            } else {
                echo "⚠️ Need " . (30 - $team['points']) . " more to hit safety";
            }
            ?>
        </span>
    </div>

    
</div>

<!-- 38 pts progress bars -->
<div class="panel">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">📊 Full Season Safety Progress (38-Point Target)</h2>
    <p style="color: #FFFFFF; font-size: 13px; margin-bottom: 15px; font-weight: bold;">
        Target: 38 points for full season safety (historically 95%+ survival rate)
    </p> 
    <div style="background: #40444b; border-radius: 8px; height: 50px; position: relative; overflow: hidden; margin-bottom: 10px; border: 2px solid #43b581;">
        <?php 
        $full_season_progress_pct = ($team['points'] / 38) * 100;
        
        // Color based on proximity to 38 points
        if ($full_season_progress_pct >= 100) {
            $bar_color = "linear-gradient(90deg, #43b581, #57f287)";
        } elseif ($full_season_progress_pct >= 75) {
            $bar_color = "linear-gradient(90deg, #43b581, #5865F2)";
        } elseif ($full_season_progress_pct >= 50) {
            $bar_color = "linear-gradient(90deg, #faa61a, #fee75c)";
        } else {
            $bar_color = "linear-gradient(90deg, #f04747, #ff6b6b)";
        }
        ?>
        
        <div style="background: <?php echo $bar_color; ?>; width: <?php echo min(100, $full_season_progress_pct); ?>%; height: 100%; transition: width 0.5s ease;"></div>
        
        <!-- 38-point marker line -->
        <div style="position: absolute; right: 0; top: 0; bottom: 0; width: 3px; background: #43b581; opacity: 0.8; box-shadow: 0 0 10px rgba(67,181,129,0.6);"></div>
        <div style="position: absolute; right: 0; top: -32px; font-size: 13px; color: #43b581; font-weight: bold; transform: translateX(50%); background: rgba(0,0,0,0.9); padding: 4px 10px; border-radius: 4px; border: 2px solid #43b581;">
            ▼ Full Safety (38 pts)
        </div>
        
        <!-- Current progress text -->
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; color: white; font-size: 20px; text-shadow: 2px 2px 6px rgba(0,0,0,0.9);">
            <?php echo $team['points']; ?> / 38 points (<?php echo round($full_season_progress_pct); ?>%)
        </div>
    </div>
    <div style="margin-top: 15px; text-align: center;">
        <span style="background: <?php echo $status_color; ?>; padding: 10px 20px; border-radius: 6px; font-weight: bold; color: white; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            <?php 
            if ($full_season_progress_pct >= 100) {
                echo "🏆 Full Season Target Hit! Exceptional!";
            } elseif ($full_season_progress_pct >= 50) {
                echo "⚠️ Need " . (38 - $team['points']) . " more to hit safety";
            } else {
                echo "⚠️ Need " . (38 - $team['points']) . " more to hit safety";
            }
            ?>
        </span>
    </div>
</div>    

<!-- 40 pt Progress Bar -->
<div class="panel">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">📊 Extended Safety Target Progress (40-Point Target)</h2>
    <p style="color: #FFFFFF; font-size: 13px; margin-bottom: 15px; font-weight: bold;">
        Extended target: 40 points for extra safety margin (historically 98%+ survival rate)
    </p>
    <div style="background: #40444b; border-radius: 8px; height: 50px; position: relative; overflow: hidden; margin-bottom: 10px; border: 2px solid #43b581;">
        <?php 
        $extended_target_progress_pct = ($team['points'] / 40) * 100;  

        // Color based on proximity to 40 points
        if ($extended_target_progress_pct >= 100) {
            $bar_color = "linear-gradient(90deg, #43b581, #57f287)";
        } elseif ($extended_target_progress_pct >= 75) {
            $bar_color = "linear-gradient(90deg, #43b581, #5865F2)";
        } elseif ($extended_target_progress_pct >= 50) {
            $bar_color = "linear-gradient(90deg, #faa61a, #fee75c)";
        } else {
            $bar_color = "linear-gradient(90deg, #f04747, #ff6b6b)";
        }
        ?>
        <div style="background: <?php echo $bar_color; ?>; width: <?php echo min(100, $extended_target_progress_pct); ?>%; height: 100%; transition: width 0.5s ease;"></div>

        <!-- 40-point marker line -->
        <div style="position: absolute; right: 0; top: 0; bottom: 0; width: 3px; background: #43b581; opacity: 0.8; box-shadow: 0 0 10px rgba(67,181,129,0.6);"></div>
        <div style="position: absolute; right: 0; top: -32px; font-size: 13px; color: #43b581; font-weight: bold; transform: translateX(50%); background: rgba(0,0,0,0.9); padding: 4px 10px; border-radius: 4px; border: 2px solid #43b581;">
            ▼ Extended Safety (40 pts)
        </div>  

        <!-- Current progress text -->
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; color: white; font-size: 20px; text-shadow: 2px 2px 6px rgba(0,0,0,0.9);">
            <?php echo $team['points']; ?> / 40 points (<?php echo round($extended_target_progress_pct); ?>%)
        </div>
    </div>
    <div style="margin-top: 15px; text-align: center;">
        <span style="background: <?php echo $status_color; ?>; padding: 10px 20px; border-radius: 6px; font-weight: bold; color: white; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            <?php 
            if ($extended_target_progress_pct >= 100) {
                echo "🏆 Extended Target Hit! Exceptional!";
            } elseif ($extended_target_progress_pct >= 50) {
                echo "⚠️ Need " . (40 - $team['points']) . " more to hit extended target";
            } else {
                echo "⚠️ Need " . (40 - $team['points']) . " more to hit extended target";
            }
            ?>
        </span>
    </div>
</div>

<!-- 42 pt Progress Bar -->
<div class="panel">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">📊 Guaranteed Safety Target Progress (42-Point Target)</h2>
    <p style="color: #FFFFFF; font-size: 13px; margin-bottom: 15px; font-weight: bold;">
        Guaranteed target: 42 points for guaranteed safety margin (historically 99%+ survival rate)
    </p>
    <div style="background: #40444b; border-radius: 8px; height: 50px; position: relative; overflow: hidden; margin-bottom: 10px; border: 2px solid #43b581;">
        <?php 
        $guaranteed_target_progress_pct = ($team['points'] / 42) * 100;

        // Color based on proximity to 42 points
        if ($guaranteed_target_progress_pct >= 100) {
            $bar_color = "linear-gradient(90deg, #43b581, #57f287)";
        } elseif ($guaranteed_target_progress_pct >= 75) {
            $bar_color = "linear-gradient(90deg, #43b581, #5865F2)";
        } elseif ($guaranteed_target_progress_pct >= 50) {
            $bar_color = "linear-gradient(90deg, #faa61a, #fee75c)";
        } else {
            $bar_color = "linear-gradient(90deg, #f04747, #ff6b6b)";
        }
        ?>
        <div style="background: <?php echo $bar_color; ?>; width: <?php echo min(100, $guaranteed_target_progress_pct); ?>%; height: 100%; transition: width 0.5s ease;"></div>

        <!-- 42-point marker line -->
        <div style="position: absolute; right: 0; top: 0; bottom: 0; width: 3px; background: #43b581; opacity: 0.8; box-shadow: 0 0 10px rgba(67,181,129,0.6);"></div>
        <div style="position: absolute; right: 0; top: -32px; font-size: 13px; color: #43b581; font-weight: bold; transform: translateX(50%); background: rgba(0,0,0,0.9); padding: 4px 10px; border-radius: 4px; border: 2px solid #43b581;">
            ▼ Guaranteed Safety (42 pts)
        </div>
        <!-- Current progress text -->
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; color: white; font-size: 20px; text-shadow: 2px 2px 6px rgba(0,0,0,0.9);">
            <?php echo $team['points']; ?> / 42 points (<?php echo round($guaranteed_target_progress_pct); ?>%)
        </div>
    </div>
    <div style="margin-top: 15px; text-align: center;">
        <span style="background: <?php echo $status_color; ?>; padding: 10px 20px; border-radius: 6px; font-weight: bold; color: white; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            <?php 
            if ($guaranteed_target_progress_pct >= 100) {
                echo "🏆 Ultimate Target Hit! Exceptional!";
            } elseif ($guaranteed_target_progress_pct >= 50) {
                echo "⚠️ Need " . (42 - $team['points']) . " more to hit ultimate target";
            } else {
                echo "⚠️ Need " . (42 - $team['points']) . " more to hit ultimate target";
            }
            ?>
        </span>
    </div>
</div>

<!-- 45 pt Progress Bar -->
<div class="panel">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">📊 Mathematical Safety Target Progress (45-Point Target)</h2>
    <p style="color: #FFFFFF; font-size: 13px; margin-bottom: 15px; font-weight: bold;">
        Mathematical target: 45 points for Mathematical safety margin (historically 100% survival rate)
    </p>
    <div style="background: #40444b; border-radius: 8px; height: 50px; position: relative; overflow: hidden; margin-bottom: 10px; border: 2px solid #43b581;">
        <?php 
        $mathematical_target_progress_pct = ($team['points'] / 45) * 100;

        // Color based on proximity to 45 points
        if ($mathematical_target_progress_pct >= 100) {
            $bar_color = "linear-gradient(90deg, #43b581, #57f287)";
        } elseif ($mathematical_target_progress_pct >= 75) {
            $bar_color = "linear-gradient(90deg, #43b581, #5865F2)";
        } elseif ($mathematical_target_progress_pct >= 50) {
            $bar_color = "linear-gradient(90deg, #faa61a, #fee75c)";
        } else {
            $bar_color = "linear-gradient(90deg, #f04747, #ff6b6b)";
        }
        ?>
        <div style="background: <?php echo $bar_color; ?>; width: <?php echo min(100, $mathematical_target_progress_pct); ?>%; height: 100%; transition: width 0.5s ease;"></div>

        <!-- 45-point marker line -->
        <div style="position: absolute; right: 0; top: 0; bottom: 0; width: 3px; background: #43b581; opacity: 0.8; box-shadow: 0 0 10px rgba(67,181,129,0.6);"></div>
        <div style="position: absolute; right: 0; top: -32px; font-size: 13px; color: #43b581; font-weight: bold; transform: translateX(50%); background: rgba(0,0,0,0.9); padding: 4px 10px; border-radius: 4px; border: 2px solid #43b581;">
            ▼ Mathematical Safety (45 pts)
        </div>
        <!-- Current progress text -->
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; color: white; font-size: 20px; text-shadow: 2px 2px 6px rgba(0,0,0,0.9);">
            <?php echo $team['points']; ?> / 45 points (<?php echo round($mathematical_target_progress_pct); ?>%)
        </div>
    </div>
    <div style="margin-top: 15px; text-align: center;">
        <span style="background: <?php echo $status_color; ?>; padding: 10px 20px; border-radius: 6px; font-weight: bold; color: white; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            <?php 
            if ($mathematical_target_progress_pct >= 100) {
                echo "🏆 Ultimate Target Hit! Exceptional!";
            } elseif ($mathematical_target_progress_pct >= 50) {
                echo "⚠️ Need " . (45 - $team['points']) . " more to hit mathematical target"; 
            } else {
                echo "⚠️ Need " . (45 - $team['points']) . " more to hit mathematical target";
            }
            ?>
        </span>
    </div>
</div>

<?php if ($is_first_half && $games_to_halfway > 0): ?>
<!-- Scenarios to Halfway (Next Games) -->
<div class="panel">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">🎯 Scenarios for Next <?php echo $games_to_halfway; ?> Games (To Halfway)</h2>
    <p style="color: #FFFFFF; font-size: 13px; margin-bottom: 15px; font-weight: bold;">
        Current: <?php echo $team['points']; ?> points | Target at game 19: 20 points (100%) or 15 points (75%)
    </p>
    <table>
        <tr>
            <th>Next <?php echo $games_to_halfway; ?> Games</th>
            <th>Points Gained</th>
            <th>Total at Game 19</th>
            <th>% of 20-pt Target</th>
            <th>Status</th>
        </tr>
        <?php
        // Generate scenarios based on games remaining
        $scenarios = [];
        
        if ($games_to_halfway >= 4) {
            $scenarios = [
                ['label' => '4 wins', 'points' => 12],
                ['label' => '3 wins, 1 draw', 'points' => 10],
                ['label' => '3 wins, 1 loss', 'points' => 9],
                ['label' => '2 wins, 2 draws', 'points' => 8],
                ['label' => '2 wins, 1 draw, 1 loss', 'points' => 7],
                ['label' => '2 wins, 2 losses', 'points' => 6],
                ['label' => '1 win, 3 draws', 'points' => 6],
                ['label' => '1 win, 2 draws, 1 loss', 'points' => 5],
                ['label' => '4 draws', 'points' => 4],
                ['label' => '1 win, 1 draw, 2 losses', 'points' => 4],
                ['label' => '1 win, 3 losses', 'points' => 3],
                ['label' => '3 draws, 1 loss,', 'points' => 3],
                ['label' => '2 draws, 2 losses', 'points' => 2],
                ['label' => '1 draw, 3 losses', 'points' => 1],
                ['label' => '4 losses', 'points' => 0],
            ];
        } elseif ($games_to_halfway == 3) {
            $scenarios = [
                ['label' => '3 wins', 'points' => 9],
                ['label' => '2 wins, 1 draw', 'points' => 7],
                ['label' => '2 wins, 1 loss', 'points' => 6],
                ['label' => '1 win, 2 draws', 'points' => 5],
                ['label' => '1 win, 1 draw, 1 loss', 'points' => 4],
                ['label' => '3 draws', 'points' => 3],
                ['label' => '1 win, 2 losses', 'points' => 3],
                ['label' => '2 draws, 1 loss', 'points' => 2],  
                ['label' => '1 draw, 2 losses', 'points' => 1],
                ['label' => '3 losses', 'points' => 0],
            ];
        } elseif ($games_to_halfway == 2) {
            $scenarios = [
                ['label' => '2 wins', 'points' => 6],
                ['label' => '1 win, 1 draw', 'points' => 4],
                ['label' => '1 win, 1 loss', 'points' => 3],
                ['label' => '2 draws', 'points' => 2],
                ['label' => '1 draw, 1 loss', 'points' => 1],
                ['label' => '2 losses', 'points' => 0],
            ];
        } elseif ($games_to_halfway == 1) {
            $scenarios = [
                ['label' => '1 win', 'points' => 3],
                ['label' => '1 draw', 'points' => 1],
                ['label' => '1 loss', 'points' => 0],
            ];
        }
        
        foreach ($scenarios as $scenario):
            $scenario_total = $team['points'] + $scenario['points'];
            $scenario_pct = ($scenario_total / 20) * 100;
            
            if ($scenario_pct >= 100) {
                $row_color = "rgba(67, 181, 129, 0.3)";
                $status = "✅ TARGET HIT!";
                $text_color = "#FFFFFF";
            } elseif ($scenario_pct >= 90) {
                $row_color = "rgba(87, 242, 135, 0.3)";
                $status = "✅ Excellent";
                $text_color = "#FFFFFF";
            } elseif ($scenario_pct >= 75) {
                $row_color = "rgba(88, 101, 242, 0.3)";
                $status = "✅ 75% Rule";
                $text_color = "#FFFFFF";
            } elseif ($scenario_pct >= 65) {
                $row_color = "rgba(250, 166, 26, 0.3)";
                $status = "⚠️ Close to 75%";
                $text_color = "#FFFFFF";
            } else {
                $row_color = "rgba(240, 71, 71, 0.3)";
                $status = "⚠️ Below 75%";
                $text_color = "#FFFFFF";
            }
        ?>
        <tr style="background: <?php echo $row_color; ?>;">
            <td style="color: <?php echo $text_color; ?>; font-weight: bold;"><strong><?php echo $scenario['label']; ?></strong></td>
            <td style="color: <?php echo $text_color; ?>; font-weight: bold;"><?php echo $scenario['points']; ?> pts</td>
            <td style="color: <?php echo $text_color; ?>; font-weight: bold;"><strong><?php echo $scenario_total; ?> pts</strong></td>
            <td style="color: <?php echo $text_color; ?>; font-weight: bold;"><?php echo round($scenario_pct); ?>%</td>
            <td style="color: <?php echo $text_color; ?>; font-weight: bold;"><?php echo $status; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <?php if ($halfway_progress_pct >= 100): ?>
    <p style="color: #43b581; font-weight: bold; text-align: center; margin-top: 15px; font-size: 16px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
        💪 Even 0 points from next <?php echo $games_to_halfway; ?> = still at 100% target!
    </p>
    <?php elseif ($halfway_progress_pct >= 75): ?>
    <p style="color: #43b581; font-weight: bold; text-align: center; margin-top: 15px; font-size: 16px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
        🏆 Even 0 points from next <?php echo $games_to_halfway; ?> = still at 75% target!
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Projection -->
<div class="grid">
    <div class="panel" style="background: #2e3136;">
        <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">🔮 Season Projection</h2>
        <div style="background: #40444b; padding: 20px; border-radius: 8px; margin-top: 15px; border: 2px solid #5865F2;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="flex: 1; min-width: 150px;">
                    <h3 style="color: #FFFFFF; font-size: 14px; margin: 0; font-weight: bold;">Current PPG</h3>
                    <div style="font-size: 36px; font-weight: bold; color: #5865F2; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                        <?php echo number_format($current_ppg, 2); ?>
                    </div>
                </div>
                <div style="font-size: 48px; color: <?= $teamTextColor ?>;">→</div>
                <div style="flex: 1; min-width: 150px;">
                    <h3 style="color: #FFFFFF; font-size: 14px; margin: 0; font-weight: bold;">Projected Final Points</h3>
                    <div style="font-size: 36px; font-weight: bold; color: <?php echo $projected_points >= 38 ? '#43b581' : '#f04747'; ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                        <?php echo $projected_points; ?>
                    </div>
                </div>
            </div>
            <p style="color: #FFFFFF; font-size: 13px; margin-top: 15px; text-align: center; font-weight: bold;">
                <?php 
                if ($projected_points >= 38) {
                    echo "✅ On pace for survival! Keep this form!";
                } else {
                    // Check if the season is already over to avoid dividing by zero
                    if ($games_remaining > 0) {
                        $ppg_improvement = round((38 - $projected_points) / $games_remaining, 2);
                        echo "⚠️ Need to improve PPG by " . $ppg_improvement . " to reach 38 points";
                    } else {
                        echo "🏁 Season over. Final standings are locked in.";
                    }
                }
                ?>
            </p>
        </div>
        
        <?php if ($is_first_half && $games_to_halfway > 0): ?>
        <div style="background: #40444b; padding: 15px; border-radius: 8px; margin-top: 15px; border: 2px solid #43b581;">
            <h3 style="color: <?= $teamTextColor ?>; font-size: 14px; margin: 0 0 10px 0; font-weight: bold; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">Projected at Halfway</h3>
            <div style="font-size: 28px; font-weight: bold; color: #43b581; text-align: center; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                <?php echo round($projected_halfway_points); ?> points
            </div>
            <p style="color: #FFFFFF; font-size: 12px; text-align: center; margin-top: 5px; font-weight: bold;">
                If current form continues (<?php echo round($projected_halfway_pct); ?>% of target)
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- What If Scenarios (Next 6 Games) -->
<div class="panel" style="background: #2e3136;">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">🎯 What If Scenarios (Next 6 Games)</h2>
    <?php
    $six_game_scenarios = [
        ['wins' => 6, 'draws' => 0, 'label' => '6 wins'],
        ['wins' => 5, 'draws' => 1, 'label' => '5 wins, 1 draw'],
        ['wins' => 5, 'draws' => 0, 'label' => '5 wins, 1 loss'],
        ['wins' => 4, 'draws' => 2, 'label' => '4 wins, 2 draws'],
        ['wins' => 4, 'draws' => 1, 'label' => '4 wins, 1 draw, 1 loss'],
        ['wins' => 4, 'draws' => 0, 'label' => '4 wins, 2 losses'],
        ['wins' => 3, 'draws' => 3, 'label' => '3 wins, 3 draws'],
        ['wins' => 3, 'draws' => 2, 'label' => '3 wins, 2 draws, 1 loss'],
        ['wins' => 3, 'draws' => 1, 'label' => '3 wins, 1 draw, 2 losses'],
        ['wins' => 3, 'draws' => 0, 'label' => '3 wins, 3 losses'],
        ['wins' => 2, 'draws' => 4, 'label' => '2 wins, 4 draws'],
        ['wins' => 2, 'draws' => 2, 'label' => '2 wins, 2 draws, 2 losses'],
        ['wins' => 2, 'draws' => 0, 'label' => '2 wins, 4 losses'],
        ['wins' => 1, 'draws' => 5, 'label' => '1 win, 5 draws'],
        ['wins' => 1, 'draws' => 2, 'label' => '1 win, 2 draws, 3 losses'],
        ['wins' => 1, 'draws' => 0, 'label' => '1 win, 5 losses'],
        ['wins' => 0, 'draws' => 6, 'label' => '6 draws'],
        ['wins' => 0, 'draws' => 3, 'label' => '3 draws, 3 losses'],
        ['wins' => 0, 'draws' => 2, 'label' => '2 draws, 4 losses'],
        ['wins' => 0, 'draws' => 1, 'label' => '1 draw, 5 losses'],
        ['wins' => 0, 'draws' => 0, 'label' => '6 losses'],
    ];
    ?>
    <div style="margin-top: 15px;">
        <?php foreach ($six_game_scenarios as $scenario): ?>
            <?php
            $scenario_points = $team['points'] + ($scenario['wins'] * 3) + $scenario['draws'];
            $scenario_gap = $scenario_points - $safety_target;
            ?>
            <div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 2px solid #5865F2;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <span style="font-weight: bold; color: #FFFFFF;"><?php echo $scenario['label']; ?></span>
                    <span style="font-size: 24px; font-weight: bold; color: <?php echo $scenario_gap >= 0 ? '#43b581' : '#faa61a'; ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                        <?php echo $scenario_points; ?> pts
                    </span>
                </div>
                <div style="color: #FFFFFF; font-size: 12px; margin-top: 5px; font-weight: bold;">
                    <?php echo abs($scenario_gap); ?> points <?php echo $scenario_gap >= 0 ? 'above' : 'to'; ?> target
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- What If Scenarios (Next 5 Games) -->
<div class="panel" style="background: #2e3136;">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">🎯 What If Scenarios (Next 5 Games)</h2>
    <?php
    $five_game_scenarios = [
        ['wins' => 5, 'draws' => 0, 'label' => '5 wins'],
        ['wins' => 4, 'draws' => 1, 'label' => '4 wins, 1 draw'],
        ['wins' => 4, 'draws' => 0, 'label' => '4 wins, 1 loss'],
        ['wins' => 3, 'draws' => 2, 'label' => '3 wins, 2 draws'],
        ['wins' => 3, 'draws' => 1, 'label' => '3 wins, 1 draw, 1 loss'],
        ['wins' => 3, 'draws' => 0, 'label' => '3 wins, 2 losses'],
        ['wins' => 2, 'draws' => 3, 'label' => '2 wins, 3 draws'],
        ['wins' => 2, 'draws' => 1, 'label' => '2 wins, 1 draw, 2 losses'],
        ['wins' => 2, 'draws' => 0, 'label' => '2 wins, 3 losses'],
        ['wins' => 1, 'draws' => 4, 'label' => '1 win, 4 draws'],
        ['wins' => 1, 'draws' => 2, 'label' => '1 win, 2 draws, 2 losses'],
        ['wins' => 1, 'draws' => 1, 'label' => '1 win, 1 draw, 3 losses'],
        ['wins' => 1, 'draws' => 0, 'label' => '1 win, 4 losses'],
        ['wins' => 0, 'draws' => 5, 'label' => '5 draws'],
        ['wins' => 0, 'draws' => 4, 'label' => '4 draws, 1 loss'],
        ['wins' => 0, 'draws' => 3, 'label' => '3 draws, 2 losses'],
        ['wins' => 0, 'draws' => 2, 'label' => '2 draws, 3 losses'],
        ['wins' => 0, 'draws' => 1, 'label' => '1 draw, 4 losses'],
        ['wins' => 0, 'draws' => 0, 'label' => '5 losses'],
    ];
    ?>
    <div style="margin-top: 15px;">
        <?php foreach ($five_game_scenarios as $scenario): ?>
            <?php
            $scenario_points = $team['points'] + ($scenario['wins'] * 3) + $scenario['draws'];
            $scenario_gap = $scenario_points - $safety_target;
            ?>
            <div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 2px solid #5865F2;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <span style="font-weight: bold; color: #FFFFFF;"><?php echo $scenario['label']; ?></span>
                    <span style="font-size: 24px; font-weight: bold; color: <?php echo $scenario_gap >= 0 ? '#43b581' : '#faa61a'; ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                        <?php echo $scenario_points; ?> pts
                    </span>
                </div>
                <div style="color: #FFFFFF; font-size: 12px; margin-top: 5px; font-weight: bold;">
                    <?php echo abs($scenario_gap); ?> points <?php echo $scenario_gap >= 0 ? 'above' : 'to'; ?> target
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- What If Scenarios (Next 4 Games) -->
<div class="panel" style="background: #2e3136;">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">🎯 What If Scenarios (Next 4 Games)</h2>
    <?php
    $four_game_scenarios = [
        ['wins' => 4, 'draws' => 0, 'label' => '4 wins'],
        ['wins' => 3, 'draws' => 1, 'label' => '3 wins, 1 draw'],
        ['wins' => 3, 'draws' => 0, 'label' => '3 wins, 1 loss'],
        ['wins' => 2, 'draws' => 2, 'label' => '2 wins, 2 draws'],
        ['wins' => 2, 'draws' => 1, 'label' => '2 wins, 1 draw, 1 loss'],
        ['wins' => 2, 'draws' => 0, 'label' => '2 wins, 2 losses'],
        ['wins' => 1, 'draws' => 3, 'label' => '1 win, 3 draws'],
        ['wins' => 1, 'draws' => 1, 'label' => '1 win, 1 draw, 2 losses'],
        ['wins' => 1, 'draws' => 0, 'label' => '1 win, 3 losses'],
        ['wins' => 0, 'draws' => 4, 'label' => '4 draws'],
        ['wins' => 0, 'draws' => 3, 'label' => '3 draws, 1 loss'],
        ['wins' => 0, 'draws' => 2, 'label' => '2 draws, 2 losses'],
        ['wins' => 0, 'draws' => 1, 'label' => '1 draw, 3 losses'],
        ['wins' => 0, 'draws' => 0, 'label' => '4 losses'],
    ];
    ?>
    <div style="margin-top: 15px;">
        <?php foreach ($four_game_scenarios as $scenario): ?>
            <?php
            $scenario_points = $team['points'] + ($scenario['wins'] * 3) + $scenario['draws'];
            $scenario_gap = $scenario_points - $safety_target;
            ?>
            <div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 2px solid #5865F2;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <span style="font-weight: bold; color: #FFFFFF;"><?php echo $scenario['label']; ?></span>
                    <span style="font-size: 24px; font-weight: bold; color: <?php echo $scenario_gap >= 0 ? '#43b581' : '#faa61a'; ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                        <?php echo $scenario_points; ?> pts
                    </span>
                </div>
                <div style="color: #FFFFFF; font-size: 12px; margin-top: 5px; font-weight: bold;">
                    <?php echo abs($scenario_gap); ?> points <?php echo $scenario_gap >= 0 ? 'above' : 'to'; ?> target
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- What If Scenarios (Next 3 Games) -->
    <div class="panel" style="background: #2e3136;">
        <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">🎯 What If Scenarios (Next 3 Games)</h2>
        <?php
        $three_game_scenarios = [
            ['wins' => 3, 'draws' => 0, 'label' => '3 wins'],
            ['wins' => 2, 'draws' => 1, 'label' => '2 wins, 1 draw'],
            ['wins' => 2, 'draws' => 0, 'label' => '2 wins, 1 loss'],
            ['wins' => 1, 'draws' => 2, 'label' => '1 win, 2 draws'],
            ['wins' => 1, 'draws' => 1, 'label' => '1 win, 1 draw, 1 loss'],
            ['wins' => 1, 'draws' => 0, 'label' => '1 win, 2 losses'],
            ['wins' => 0, 'draws' => 3, 'label' => '3 draws'],
            ['wins' => 0, 'draws' => 2, 'label' => '2 draws, 1 loss'],
            ['wins' => 0, 'draws' => 1, 'label' => '1 draw, 2 losses'],
            ['wins' => 0, 'draws' => 0, 'label' => '3 losses']
        ];
        ?>
        <div style="margin-top: 15px;">
            <?php foreach ($three_game_scenarios as $scenario): ?>
                <?php 
                $scenario_points = $team['points'] + ($scenario['wins'] * 3) + $scenario['draws'] * 1;
                $scenario_gap = $scenario_points - $safety_target;
                ?>
                <div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 2px solid #5865F2;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <span style="font-weight: bold; color: #FFFFFF;"><?php echo $scenario['label']; ?></span>
                        <span style="font-size: 24px; font-weight: bold; color: <?php echo $scenario_gap >= 0 ? '#43b581' : '#faa61a'; ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                            <?php echo $scenario_points; ?> pts
                        </span>
                    </div>
                    <div style="color: #FFFFFF; font-size: 12px; margin-top: 5px; font-weight: bold;">
                        <?php echo abs($scenario_gap); ?> points <?php echo $scenario_gap >= 0 ? 'above' : 'to'; ?> target
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<!-- What If Scenarios (Next 2 Games) -->
<div class="panel" style="background: #2e3136;">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">🎯 What If Scenarios (Next 2 Games)</h2>
    <?php
    $two_game_scenarios = [
            ['wins' => 2, 'draws' => 0, 'label' => '2 wins'],
            ['wins' => 1, 'draws' => 1, 'label' => '1 win, 1 draw'],
            ['wins' => 1, 'draws' => 0, 'label' => '1 win, 1 loss'],
            ['wins' => 0, 'draws' => 2, 'label' => '2 draws'],
            ['wins' => 0, 'draws' => 1, 'label' => '1 draw, 1 loss'],
            ['wins' => 0, 'draws' => 0, 'label' => '2 losses']
    ];
    ?>
    <div style="margin-top: 15px;">
        <?php foreach ($two_game_scenarios as $scenario): ?>
            <?php 
            $scenario_points = $team['points'] + ($scenario['wins'] * 3) + $scenario['draws'] * 1;
            $scenario_gap = $scenario_points - $safety_target;
            ?>
            <div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 2px solid #5865F2;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <span style="font-weight: bold; color: #FFFFFF;"><?php echo $scenario['label']; ?></span>
                    <span style="font-size: 24px; font-weight: bold; color: <?php echo $scenario_gap >= 0 ? '#43b581' : '#faa61a'; ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                        <?php echo $scenario_points; ?> pts
                    </span>
                </div>
                <div style="color: #FFFFFF; font-size: 12px; margin-top: 5px; font-weight: bold;">
                    <?php echo abs($scenario_gap); ?> points <?php echo $scenario_gap >= 0 ? 'above' : 'to'; ?> target
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- What If Scenarios (Next Game) -->
<div class="panel" style="background: #2e3136;">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">🎯 What If Scenarios (Next Game)</h2>
    <?php
        $next_game_scenarios = [
            ['result' => 'win', 'points' => 3, 'label' => 'Win'],
            ['result' => 'draw', 'points' => 1, 'label' => 'Draw'],
            ['result' => 'loss', 'points' => 0, 'label' => 'Loss']
        ];  
    ?>
    <div style="margin-top: 15px;">
        <?php foreach ($next_game_scenarios as $scenario): ?>
            <?php 
            $scenario_points = $team['points'] + $scenario['points'];
            $scenario_gap = $scenario_points - $safety_target;
            ?>
            <div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 2px solid #5865F2;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <span style="font-weight: bold; color: #FFFFFF;"><?php echo $scenario['label']; ?></span>
                    <span style="font-size: 24px; font-weight: bold; color: <?php echo $scenario_gap >= 0 ? '#43b581' : '#faa61a'; ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                        <?php echo $scenario_points; ?> pts
                    </span>
                </div>
                <div style="color: #FFFFFF; font-size: 12px; margin-top: 5px; font-weight: bold;">
                    <?php echo abs($scenario_gap); ?> points <?php echo $scenario_gap >= 0 ? 'above' : 'to'; ?> target
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<!-- What If Scenarios (Full Season based on PPG for points between 0.0 and 0.9) -->
<div class="panel" style="background: #2e3136;">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">🎯 What If Scenarios (Full Season Projection)</h2>
    <?php
    $season_scenarios = [
        ['ppg' => 0.9, 'label' => '0.9 PPG (39 pts)'],
        ['ppg' => 0.8, 'label' => '0.8 PPG (36 pts)'],
        ['ppg' => 0.7, 'label' => '0.7 PPG (33 pts)'],
        ['ppg' => 0.6, 'label' => '0.6 PPG (30 pts)'],
        ['ppg' => 0.5, 'label' => '0.5 PPG (27 pts)'],
        ['ppg' => 0.4, 'label' => '0.4 PPG (24 pts)'],
        ['ppg' => 0.3, 'label' => '0.3 PPG (21 pts)'],
        ['ppg' => 0.2, 'label' => '0.2 PPG (18 pts)'],
        ['ppg' => 0.1, 'label' => '0.1 PPG (15 pts)'],
        ['ppg' => 0.0, 'label' => '0.0 PPG (12 pts)']
    ];
    ?>
    <div style="margin-top: 15px;">
        <?php foreach ($season_scenarios as $scenario): ?>
            <?php 
            $scenario_points = round($scenario['ppg'] * 38);
            $scenario_gap = $scenario_points - $safety_target;
            $team_comparison = $scenario_points - $team['points'];
            ?>
            <div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 2px solid #5865F2;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <span style="font-weight: bold; color: #FFFFFF;"><?php echo $scenario['label']; ?></span>
                    <span style="font-size: 24px; font-weight: bold; color: <?php echo $scenario_gap >= 0 ? '#43b581' : '#faa61a'; ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                        <?php echo $scenario_points; ?> pts
                    </span>
                </div>
                <div style="color: #FFFFFF; font-size: 12px; margin-top: 5px; font-weight: bold;">
                    <?php echo abs($scenario_gap); ?> points <?php echo $scenario_gap >= 0 ? 'above' : 'to'; ?> target
                </div>
                <div style="color: #FFFFFF; font-size: 12px; margin-top: 5px; font-weight: bold;">
                    <?php echo abs($team_comparison); ?> points <?php echo $team_comparison >= 0 ? 'above' : 'below'; ?> <?= htmlspecialchars($teamName) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- What If Scenarios (Full Season based on PPG for points between 1.0 and 2.0) -->
<div class="panel" style="background: #2e3136;">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">🎯 What If Scenarios (Full Season Projection)</h2>
    <?php
    $season_scenarios = [
        ['ppg' => 2.0, 'label' => '2.0 PPG (70 pts)'],
        ['ppg' => 1.9, 'label' => '1.9 PPG (66 pts)'],
        ['ppg' => 1.8, 'label' => '1.8 PPG (63 pts)'],
        ['ppg' => 1.7, 'label' => '1.7 PPG (60 pts)'],
        ['ppg' => 1.6, 'label' => '1.6 PPG (56 pts)'],
        ['ppg' => 1.5, 'label' => '1.5 PPG (57 pts)'],
        ['ppg' => 1.4, 'label' => '1.4 PPG (53 pts)'],
        ['ppg' => 1.3, 'label' => '1.3 PPG (49 pts)'],
        ['ppg' => 1.2, 'label' => '1.2 PPG (48 pts)'],
        ['ppg' => 1.1, 'label' => '1.1 PPG (44 pts)'],
        ['ppg' => 1.0, 'label' => '1.0 PPG (46 pts)'],
    ];
    ?>
    <div style="margin-top: 15px;">
        <?php foreach ($season_scenarios as $scenario): ?>
            <?php 
            $scenario_points = round($scenario['ppg'] * 38);
            $scenario_gap = $scenario_points - $safety_target;
            $team_comparison = $scenario_points - $team['points'];
            ?>
            <div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 2px solid #5865F2;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <span style="font-weight: bold; color: #FFFFFF;"><?php echo $scenario['label']; ?></span>
                    <span style="font-size: 24px; font-weight: bold; color: <?php echo $scenario_gap >= 0 ? '#43b581' : '#faa61a'; ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                        <?php echo $scenario_points; ?> pts
                    </span>
                </div>
                <div style="color: #FFFFFF; font-size: 12px; margin-top: 5px; font-weight: bold;">
                    <?php echo abs($scenario_gap); ?> points <?php echo $scenario_gap >= 0 ? 'above' : 'to'; ?> target
                </div>
                <div style="color: #FFFFFF; font-size: 12px; margin-top: 5px; font-weight: bold;">
                    <?php echo abs($team_comparison); ?> points <?php echo $team_comparison >= 0 ? 'above' : 'below'; ?> <?= htmlspecialchars($teamName) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- What If Scenarios (Full Season based on PPG for points between 2.0 and 3.0) -->
<div class="panel" style="background: #2e3136;">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">🎯 What If Scenarios (Full Season Projection)</h2>
    <?php
    $season_scenarios = [
        ['ppg' => 3.0, 'label' => '3.0 PPG (78 pts)'],
        ['ppg' => 2.9, 'label' => '2.9 PPG (75 pts)'],
        ['ppg' => 2.8, 'label' => '2.8 PPG (72 pts)'],
        ['ppg' => 2.7, 'label' => '2.7 PPG (68 pts)'],
        ['ppg' => 2.6, 'label' => '2.6 PPG (64 pts)'],
        ['ppg' => 2.5, 'label' => '2.5 PPG (70 pts)'],
        ['ppg' => 2.4, 'label' => '2.4 PPG (66 pts)'],
        ['ppg' => 2.3, 'label' => '2.3 PPG (62 pts)'],
        ['ppg' => 2.2, 'label' => '2.2 PPG (60 pts)'],
        ['ppg' => 2.1, 'label' => '2.1 PPG (56 pts)'],
        ['ppg' => 2.0, 'label' => '2.0 PPG (58 pts)']
    ];
    ?>
    <div style="margin-top: 15px;">
        <?php foreach ($season_scenarios as $scenario): ?>
            <?php 
            $scenario_points = round($scenario['ppg'] * 38);
            $scenario_gap = $scenario_points - $safety_target;
            $team_comparison = $scenario_points - $team['points'];
            ?>
            <div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 2px solid #5865F2;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <span style="font-weight: bold; color: #FFFFFF;"><?php echo $scenario['label']; ?></span>
                    <span style="font-size: 24px; font-weight: bold; color: <?php echo $scenario_gap >= 0 ? '#43b581' : '#faa61a'; ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                        <?php echo $scenario_points; ?> pts
                    </span>
                </div>
                <div style="color: #FFFFFF; font-size: 12px; margin-top: 5px; font-weight: bold;">
                    <?php echo abs($scenario_gap); ?> points <?php echo $scenario_gap >= 0 ? 'above' : 'to'; ?> target
                </div>
                <div style="color: #FFFFFF; font-size: 12px; margin-top: 5px; font-weight: bold;">
                    <?php echo abs($team_comparison); ?> points <?php echo $team_comparison >= 0 ? 'above' : 'below'; ?> <?= htmlspecialchars($teamName) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- League Position Battle -->
<?php
$_ctx_pos = (int)$team['position'];
$_ctx_teams = array_values(array_filter($standings, function ($teamRow) use ($_ctx_pos) {
    $pos = (int)$teamRow['position'];
    return $pos >= max(1, $_ctx_pos - 3) && $pos <= $_ctx_pos + 3;
}));
?>
<div class="panel">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">📍 League Position Battle</h2>
    <p style="color: #FFFFFF; font-size: 13px; margin-bottom: 15px; font-weight: bold;">
        <?= htmlspecialchars($teamName) ?> and the teams around them
    </p>
    <table>
        <tr>
            <th>Pos</th>
            <th>Team</th>
            <th>P</th>
            <th>GD</th>
            <th>Pts</th>
            <th>Points Gap</th>
            <th>Projection</th>
        </tr>
        <?php foreach ($_ctx_teams as $_ctx_teamRow): ?>
        <?php
        $_ctx_selected = ($_ctx_teamRow['team_name'] === $teamName);
        $_ctx_gap = (int)$_ctx_teamRow['points'] - (int)$team['points'];
        $_ctx_ppg = $_ctx_teamRow['played'] > 0 ? $_ctx_teamRow['points'] / $_ctx_teamRow['played'] : 0;
        $_ctx_projection = round($_ctx_ppg * 38, 1);
        ?>
        <tr style="<?php echo $_ctx_selected ? 'background: rgba(0,0,0,0.4); border: 2px solid ' . $teamPrimary . ';' : ''; ?>">
            <td style="color: #FFFFFF; font-weight: bold;"><strong><?php echo $_ctx_teamRow['position']; ?></strong></td>
            <td style="<?php echo $_ctx_selected ? 'color: ' . $teamTextColor . '; font-weight: bold;' : 'color: #FFFFFF; font-weight: bold;'; ?>">
                <?php echo $_ctx_selected ? '⭐ ' : ''; ?><?php echo htmlspecialchars($_ctx_teamRow['team_name']); ?>
            </td>
            <td style="color: #FFFFFF; font-weight: bold;"><?php echo $_ctx_teamRow['played']; ?></td>
            <td style="color: <?php echo $_ctx_teamRow['gd'] >= 0 ? '#43b581' : '#f04747'; ?>; font-weight: bold;">
                <?php echo ($_ctx_teamRow['gd'] > 0 ? '+' : '') . $_ctx_teamRow['gd']; ?>
            </td>
            <td style="color: #FFFFFF; font-weight: bold;"><strong><?php echo $_ctx_teamRow['points']; ?></strong></td>
            <td style="color: <?php echo $_ctx_gap > 0 ? '#f04747' : ($_ctx_gap < 0 ? '#43b581' : '#FFFFFF'); ?>; font-weight: bold;">
                <?php echo $_ctx_gap > 0 ? '+' : ''; echo $_ctx_gap; ?>
            </td>
            <td style="color: <?php echo $_ctx_projection >= 38 ? '#43b581' : '#f04747'; ?>; font-weight: bold;">
                <?php echo $_ctx_projection; ?> pts
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Relegation Battle Comparison -->
<div class="panel">
    <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">⚔️ Relegation Battle - Bottom 6 Comparison</h2>
    <p style="color: #FFFFFF; font-size: 13px; margin-bottom: 15px; font-weight: bold;">
        Teams fighting for survival
    </p>
    <table>
        <tr>
            <th>Pos</th>
            <th>Team</th>
            <th>P</th>
            <th>GD</th>
            <th>Pts</th>
            <th>Points Gap</th>
            <th>Projection</th>
        </tr>
        <?php foreach ($bottom_six as $teamRow): ?>
        <?php
        $is_selected = ($teamRow['team_name'] === $teamName);
        $gap = (int)$teamRow['points'] - (int)$team['points'];
        $team_ppg = $teamRow['played'] > 0 ? $teamRow['points'] / $teamRow['played'] : 0;
        $team_projection = round($team_ppg * 38, 1);
        ?>
        <tr style="<?php echo $is_selected ? 'background: rgba(0,0,0,0.4); border: 2px solid ' . $teamPrimary . ';' : ''; ?>">
            <td style="color: #FFFFFF; font-weight: bold;"><strong><?php echo $teamRow['position']; ?></strong></td>
            <td style="<?php echo $is_selected ? 'color: ' . $teamTextColor . '; font-weight: bold;' : 'color: #FFFFFF; font-weight: bold;'; ?>">
                <?php echo $is_selected ? '⭐ ' : ''; ?>
                <?php echo htmlspecialchars($teamRow['team_name']); ?>
            </td>
            <td style="color: #FFFFFF; font-weight: bold;"><?php echo $teamRow['played']; ?></td>
            <td style="color: <?php echo $teamRow['gd'] >= 0 ? '#43b581' : '#f04747'; ?>; font-weight: bold;">
                <?php echo ($teamRow['gd'] > 0 ? '+' : '') . $teamRow['gd']; ?>
            </td>
            <td style="color: #FFFFFF; font-weight: bold;"><strong><?php echo $teamRow['points']; ?></strong></td>
            <td style="color: <?php echo $gap > 0 ? '#f04747' : ($gap < 0 ? '#43b581' : '#FFFFFF'); ?>; font-weight: bold;">
                <?php echo $gap > 0 ? '+' : ''; echo $gap; ?>
            </td>
            <td style="color: <?php echo $team_projection >= 38 ? '#43b581' : '#f04747'; ?>; font-weight: bold;">
                <?php echo $team_projection; ?> pts
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Team Stats Summary -->
<div class="grid">
    <div class="panel" style="background: #2e3136;">
        <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">📊 Attack</h2>
        <div style="text-align: center;">
            <div style="font-size: 48px; font-weight: bold; color: #43b581; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
                <?php echo $team['gf']; ?>
            </div>
            <p style="color: #FFFFFF; font-size: 14px; font-weight: bold;">Goals Scored</p>
            <p style="color: #5865F2; font-size: 20px; font-weight: bold; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                <?php echo number_format($games_played > 0 ? $team['gf'] / $games_played : 0, 2); ?> per game
            </p>
        </div>
    </div>

    <div class="panel" style="background: #2e3136;">
        <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">🛡️ Defense</h2>
        <div style="text-align: center;">
            <div style="font-size: 48px; font-weight: bold; color: #f04747; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
                <?php echo $team['ga']; ?>
            </div>
            <p style="color: #FFFFFF; font-size: 14px; font-weight: bold;">Goals Conceded</p>
            <p style="color: #faa61a; font-size: 20px; font-weight: bold; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                <?php echo number_format($games_played > 0 ? $team['ga'] / $games_played : 0, 2); ?> per game
            </p>
        </div>
    </div>

    <div class="panel" style="background: #2e3136;">
        <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">⚽️ Goal Difference</h2>
        <div style="text-align: center;">
            <div style="font-size: 48px; font-weight: bold; color: #5865F2; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
                <?php echo number_format($team['gd'], 0); ?>
            </div>
            <p style="color: #FFFFFF; font-size: 14px; font-weight: bold;">Goal Difference</p>
            <p style="color: #43b581; font-size: 20px; font-weight: bold; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                <?php echo number_format($games_played > 0 ? $team['gd'] / $games_played : 0, 2); ?> per game
        </div>
    </div>

    <div class="panel" style="background: #2e3136;">
        <h2 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">📈 Form</h2>
        <div style="text-align: center;">
            <div style="font-size: 48px; font-weight: bold; color: #5865F2; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
                W<?php echo $team['won']; ?>
                D<?php echo $team['drawn']; ?>
                L<?php echo $team['lost']; ?>
            </div>
            <p style="color: #FFFFFF; font-size: 14px; font-weight: bold;">Record This Season</p>
            <p style="color: #43b581; font-size: 20px; font-weight: bold; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                <?php echo $team['won'] + $team['drawn'] ?> undefeated games<br>
            </p>
            <p style="color: #FFFFFF; font-size: 14px; font-weight: bold;">Record This Season</p>
            <p style="color: #43b581; font-size: 20px; font-weight: bold; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
                <?php echo ($games_played > 0 ? round(($team['won'] / $games_played) * 100, 1) : 0); ?>% win rate<br>
                <?php echo ($games_played > 0 ? round(($team['drawn'] / $games_played) * 100, 1) : 0); ?>% draw rate<br>
                <?php echo ($games_played > 0 ? round(($team['lost'] / $games_played) * 100, 1) : 0); ?>% loss rate<br>
                <?php echo ($games_played > 0 ? round((($team['won'] + $team['drawn']) / $games_played) * 100, 1) : 0); ?>% undefeated rate
            </p>
        </div>
    </div>
</div>

<!-- 75% Rule Explainer -->
<div class="panel" style="background: #2e3136; border-left: 4px solid #5865F2;">
    <h3 style="color: <?= $teamTextColor ?>; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">📚 The 75% Rule for Survival</h3>
    <p style="color: #FFFFFF; line-height: 1.6; font-weight: bold;">
        <strong style="color: <?= $teamTextColor ?>;">Historical Analysis:</strong> Teams with <strong style="color: #43b581;">15+ points at halfway</strong> 
        (75% of 20-point target) have an <strong style="color: #43b581;">85-90% survival rate</strong>.
    </p>
    <p style="color: #FFFFFF; line-height: 1.6; font-weight: bold;">
        <?= htmlspecialchars($teamName) ?> currently at <strong style="color: <?= $teamTextColor ?>;"><?php echo $team['points']; ?> points</strong> 
        after <strong style="color: #5865F2;"><?php echo $games_played; ?> games</strong>
        = <strong style="color: #43b581;"><?php echo round($halfway_progress_pct); ?>%</strong> of halfway target.
    </p>
    <?php if ($halfway_progress_pct >= 100): ?>
        <p style="color: #43b581; font-weight: bold; font-size: 16px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            🏆 EXCEPTIONAL! Already hit the 20-point target!
        </p>
    <?php elseif ($halfway_progress_pct >= 90): ?>
        <p style="color: #43b581; font-weight: bold; font-size: 16px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            🎉 Excellent position! On track for survival!
        </p>
    <?php elseif ($halfway_progress_pct >= 80): ?>
        <p style="color: #57f287; font-weight: bold; font-size: 16px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            💪 Outstanding! On track for survival!
        </p>
    <?php elseif ($halfway_progress_pct >= 75): ?>
        <p style="color: #43b581; font-weight: bold; font-size: 16px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            ✅ Excellent position! On pace for survival!
        </p>
    <?php elseif ($halfway_progress_pct >= 50): ?>
        <p style="color: #faa61a; font-weight: bold; font-size: 16px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            ⚠️ Need to hit 75% by game 19 - improvement required!
        </p>
    <?php else: ?>
        <p style="color: #f04747; font-weight: bold; font-size: 16px; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">
            🚨 Critical! Major improvement needed to reach 15-point halfway target!
        </p>
    <?php endif; ?>
</div>

<!-- Motivational Message -->
<div class="panel" style="background: linear-gradient(135deg, <?= $teamPrimary ?>, <?= $teamSecondary ?>); text-align: center; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.4);">
    <h2 style="color: white; font-size: 32px; margin: 0; text-shadow: 2px 2px 6px rgba(0,0,0,0.5);">
        <?php
        $tn = strtoupper(htmlspecialchars($teamName));
        if ($halfway_progress_pct >= 100) {
            echo "🏆 EXCEPTIONAL FORM! KEEP IT UP $tn!";
        } elseif ($halfway_progress_pct >= 75 && $is_first_half) {
            echo "💪 OUTSTANDING POSITION! KEEP GOING $tn!";
        } elseif ($team['position'] >= 18) {
            echo "🚨 IN THE FIGHT! KEEP PUSHING $tn!";
        } elseif ($points_to_safety <= 5) {
            echo "💪 ALMOST THERE! PUSH ON $tn!";
        } else {
            echo "✅ STRONG POSITION! STAY FOCUSED $tn!";
        }
        ?>
    </h2>
    <p style="color: white; font-size: 18px; margin-top: 15px; text-shadow: 1px 1px 4px rgba(0,0,0,0.5); font-weight: bold;">
        <?php 
        if ($is_first_half) {
            if ($games_to_halfway > 0) {
                echo $games_to_halfway . " games to halfway. ";
                if ($points_to_75pct > 0) {
                    echo $points_to_75pct . " points to hit 75% rule!";
                } else {
                    echo "Already at 75%+ - Brilliant!";
                }
            } else {
                echo "Reached halfway with " . $team['points'] . " points!";
            }
        } else {
            echo $games_remaining . " games to go. " . $points_to_safety . " points needed for safety!";
        }
        ?>
    </p>
</div>
