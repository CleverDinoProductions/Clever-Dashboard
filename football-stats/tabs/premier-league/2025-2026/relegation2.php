<?php
/**
 * LEEDS LIONS - RELEGATION BATTLE ANALYZER 2.0
 * Enhancements: FDR Gauntlet, Sack Watch Momentum, & Matchweek 27 Integration
 */

// 1. DATABASE FETCH (Existing logic)
$tableView = football_stats_get_table_view($db, 'PL', 'league_table_PL', $currentMainTab ?? '2025-2026');
$bottom_teams = array_values(array_filter($tableView['standings'], function ($team) {
    return $team['position'] >= 11;
}));

// 2. CONFIGURATION & FDR DATA (Next 4 Matches)
$halfway_point = 19;
$target_100pct = 20; 
$target_75pct = 15;
$safety_thresholds = [
    'average' => 36, 'magic' => 40, 'lowest_historical' => 34, 'recent_low' => 27
];

// FDR Gauntlet Score (1-5): 1 = Easy, 5 = PPG Killer
$fdr_gauntlet = [
    'Leeds United' => 3, 'Crystal Palace' => 4, 'Fulham' => 4, 
    'Nottingham Forest' => 5, 'West Ham United' => 5, 'Burnley' => 3, 'Wolves' => 4
];

// Calculate stats for each team
$teams_analysis = [];

foreach ($bottom_teams as $team) {
    $games_played = $team['played'];
    $current_points = $team['points'];
    $games_to_halfway = max(0, $halfway_point - $games_played);
    $games_remaining = 38 - $games_played;
    
    // Core Calculations
    $progress_100pct = ($current_points / $target_100pct) * 100;
    $progress_75pct = ($current_points / $target_75pct) * 100;
    $ppg = $games_played > 0 ? $current_points / $games_played : 0;
    
    // Bloom's Momentum Metric (Sack Watch)
    // Compares season PPG vs Recent 5-game PPG
    $recent_ppg = isset($team['last_5_points']) ? $team['last_5_points'] / 5 : $ppg;
    $momentum = $recent_ppg - $ppg;
    
    $projected_halfway = $current_points + ($ppg * $games_to_halfway);
    $points_to_20 = max(0, $target_100pct - $current_points);
    $points_to_15 = max(0, $target_75pct - $current_points);
    $ppg_needed_75 = $games_to_halfway > 0 ? $points_to_15 / $games_to_halfway : 0;
    
    // Risk assessment logic (Existing)
    if ($games_played >= $halfway_point) {
        if ($current_points >= $target_100pct) {
            $risk_level = "SAFE"; $risk_color = "#43b581"; $risk_icon = "✓"; $survival_chance = "95%+";
        } elseif ($current_points >= $target_75pct) {
            $risk_level = "LOW RISK"; $risk_color = "#5865F2"; $risk_icon = "✓"; $survival_chance = "85-90%";
        } elseif ($current_points >= $target_50pct) {
            $risk_level = "MODERATE"; $risk_color = "#faa61a"; $risk_icon = "⚠"; $survival_chance = "50-60%";
        } else {
            $risk_level = "HIGH RISK"; $risk_color = "#f04747"; $risk_icon = "⚠"; $survival_chance = "30% or less";
        }
    } else {
        // Before halfway projection (Existing)
        if ($progress_100pct >= 100) {
            $risk_level = "SAFE"; $risk_color = "#43b581"; $risk_icon = "✓"; $survival_chance = "95%+";
        } elseif ($projected_halfway >= $target_75pct) {
            $risk_level = "MODERATE"; $risk_color = "#5865F2"; $risk_icon = "⚡"; $survival_chance = "75-85%";
        } else {
            $risk_level = "CRITICAL"; $risk_color = "#f04747"; $risk_icon = "🚨"; $survival_chance = "40% or less";
        }
    }

    // Full season projection
    $projected_final = round($ppg * 38, 1);
    
    // Threshold feasibility loop (Existing)
    $ppg_to_thresholds = [];
    foreach ($safety_thresholds as $threshold_name => $threshold_points) {
        $points_needed = max(0, $threshold_points - $current_points);
        $ppg_needed = $games_remaining > 0 ? round($points_needed / $games_remaining, 2) : 0;
        
        if ($current_points >= $threshold_points) {
            $feasibility = 'ACHIEVED'; $feasibility_color = '#43b581';
        } elseif ($ppg_needed <= 1.2) {
            $feasibility = 'EASY'; $feasibility_color = '#43b581';
        } elseif ($ppg_needed <= 1.6) {
            $feasibility = 'ACHIEVABLE'; $feasibility_color = '#5865F2';
        } else {
            $feasibility = 'VERY HARD'; $feasibility_color = '#f04747';
        }
        
        $ppg_to_thresholds[$threshold_name] = [
            'target' => $threshold_points, 'points_needed' => $points_needed, 
            'ppg_needed' => $ppg_needed, 'feasibility' => $feasibility, 'feasibility_color' => $feasibility_color
        ];
    }
    
    $teams_analysis[] = [
        'team' => $team, 'ppg' => $ppg, 'momentum' => $momentum,
        'fdr' => $fdr_gauntlet[$team['team_name']] ?? 3,
        'projected_halfway' => $projected_halfway, 'projected_final' => $projected_final,
        'risk_level' => $risk_level, 'risk_color' => $risk_color, 'risk_icon' => $risk_icon,
        'survival_chance' => $survival_chance, 'ppg_to_thresholds' => $ppg_to_thresholds,
        'progress_75pct' => $progress_75pct, 'progress_100pct' => $progress_100pct,
        'games_remaining' => $games_remaining
    ];
}

// Global Counts
$risk_counts = ['CRITICAL' => 0, 'HIGH RISK' => 0, 'MODERATE' => 0, 'LOW RISK' => 0, 'SAFE' => 0];
foreach ($teams_analysis as $analysis) { $risk_counts[$analysis['risk_level']]++; }
?>

<div class="panel" style="border: 3px solid #f04747; background: linear-gradient(135deg, #f04747 0%, #2e3136 100%);">
    <div style="text-align: center;">
        <h1 style="color: #FFFFFF; font-size: 48px; margin: 0;">⚔️ RELEGATION WAR ROOM</h1>
        <h2 style="color: #ff6b6b; font-size: 32px; margin: 10px 0;">Schedule Difficulty & Sack Watch</h2>
        <?php football_stats_render_table_view_controls($tableView, $currentMainTab ?? '2025-2026', $currentLeague ?? 'premier-league', $currentSubTab ?? 'relegation-2'); ?>
    </div>
</div>

<div class="panel" style="background: #2e3136;">
    <h2>📊 Risk Level Distribution</h2>
    <div style="display: flex; justify-content: space-around; margin-top: 20px; flex-wrap: wrap;">
        <?php foreach ($risk_counts as $level => $count): ?>
            <div style="text-align: center; padding: 15px; background: rgba(0,0,0,0.3); border-bottom: 4px solid #fff; border-radius: 8px; min-width: 140px; margin: 5px;">
                <div style="font-size: 32px; font-weight: bold; color: white;"><?php echo $count; ?></div>
                <div style="font-size: 12px; color: #aaa;"><?php echo $level; ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="panel">
    <h2>📋 Complete Relegation Battle Analysis</h2>
    
    <div style="overflow-x: auto;">
    <table>
        <tr>
            <th>Pos</th>
            <th>Team</th>
            <th>Pts</th>
            <th>FDR Gauntlet</th>
            <th>Momentum</th>
            <th>Projected Final</th>
            <th>Risk Status</th>
        </tr>
        <?php foreach ($teams_analysis as $analysis): 
            $is_leeds = (stripos($analysis['team']['team_name'], 'Leeds') !== false);
            $fdr_color = ($analysis['fdr'] >= 4) ? '#f04747' : (($analysis['fdr'] <= 2) ? '#43b581' : '#faa61a');
        ?>
        <tr style="<?php echo $is_leeds ? 'background: rgba(255, 205, 0, 0.1); border-left: 4px solid #FFCD00;' : ''; ?>">
            <td><strong><?php echo $analysis['team']['position']; ?></strong></td>
            <td><strong><?php echo htmlspecialchars($analysis['team']['team_name']); ?></strong></td>
            <td><strong><?php echo $analysis['team']['points']; ?></strong></td>
            <td>
                <span style="background: <?php echo $fdr_color; ?>; padding: 4px 8px; border-radius: 4px; font-size: 11px; color: white; font-weight: bold;">
                    Difficulty: <?php echo $analysis['fdr']; ?>/5
                </span>
            </td>
            <td style="color: <?php echo $analysis['momentum'] >= 0 ? '#43b581' : '#f04747'; ?>;">
                <?php echo $analysis['momentum'] > 0 ? '▲' : '▼'; ?> <?php echo abs(round($analysis['momentum'], 2)); ?>
            </td>
            <td><strong><?php echo $analysis['projected_final']; ?> pts</strong></td>
            <td>
                <span style="background: <?php echo $analysis['risk_color']; ?>; padding: 5px 10px; border-radius: 4px; color: white; font-weight: bold; font-size: 11px;">
                    <?php echo $analysis['risk_icon']; ?> <?php echo $analysis['risk_level']; ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<div class="panel" style="background: #2e3136; border-left: 4px solid #5865F2;">
    <h3>🔍 Tactical Insights</h3>
    <ul style="color: #dcddde; line-height: 2;">
        <li><strong style="color: #FFCD00;">The "Bloom" Gauntlet:</strong> Teams like **West Ham** and **Forest** face FDR 5 schedules. Their survival PPG of 1.36+ is statistically "pinned" to fail during this run.</li>
        <li><strong style="color: #FFCD00;">Sack Momentum:</strong> A negative momentum value (▼) combined with a High FDR is the primary indicator of an upcoming manager change.</li>
        <li><strong style="color: #43b581;">Leeds Stability:</strong> Because we are **SAFE** and have a neutral FDR, we can use the "Two Squad" system to outlast the teams in the "Difficult" PPG zone.</li>
    </ul>
</div>