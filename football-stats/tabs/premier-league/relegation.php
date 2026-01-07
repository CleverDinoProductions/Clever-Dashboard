<?php
// RELEGATION BATTLE ANALYZER - Who's at risk?

// Get all teams in bottom 10
$stmt = $db->query("SELECT * FROM league_table WHERE position >= 11 ORDER BY position ASC");
$bottom_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Halfway point constants
$halfway_point = 19;
$target_100pct = 20; // Full target
$target_75pct = 15;  // 75% rule target
$target_50pct = 10;  // Critical zone

// Safety thresholds
$safety_thresholds = [
    'average' => 36,  // Average needed for safety
    'magic' => 40,    // Traditional "magic" number  
    'lowest_historical' => 34,  // Lowest ever (West Brom 2004-05)
    'recent_low' => 27  // More recent threshold (2023-24)
];

// Calculate stats for each team
$teams_analysis = [];

foreach ($bottom_teams as $team) {
    $games_played = $team['played'];
    $current_points = $team['points'];
    $games_to_halfway = max(0, $halfway_point - $games_played);
    $games_remaining = 38 - $games_played;
    
    // Calculate percentages
    $progress_100pct = ($current_points / $target_100pct) * 100;
    $progress_75pct = ($current_points / $target_75pct) * 100;
    
    // Current PPG
    $ppg = $games_played > 0 ? $current_points / $games_played : 0;
    
    // Projected points at halfway
    $projected_halfway = $current_points + ($ppg * $games_to_halfway);
    
    // Points needed to hit targets
    $points_to_20 = max(0, $target_100pct - $current_points);
    $points_to_15 = max(0, $target_75pct - $current_points);
    
    // PPG needed to hit 75% target
    $ppg_needed_75 = $games_to_halfway > 0 ? $points_to_15 / $games_to_halfway : 0;
    
    // Risk assessment
    if ($games_played >= $halfway_point) {
        // Already at halfway - assess what they got
        if ($current_points >= $target_100pct) {
            $risk_level = "SAFE";
            $risk_color = "#43b581";
            $risk_icon = "✓";
            $survival_chance = "95%+";
        } elseif ($current_points >= $target_75pct) {
            $risk_level = "LOW RISK";
            $risk_color = "#5865F2";
            $risk_icon = "✓";
            $survival_chance = "85-90%";
        } elseif ($current_points >= $target_50pct) {
            $risk_level = "MODERATE";
            $risk_color = "#faa61a";
            $risk_icon = "⚠";
            $survival_chance = "50-60%";
        } else {
            $risk_level = "HIGH RISK";
            $risk_color = "#f04747";
            $risk_icon = "⚠";
            $survival_chance = "30% or less";
        }
    } else {
        // Before halfway - project if they'll hit 75%
        if ($progress_100pct >= 100) {
            $risk_level = "SAFE";
            $risk_color = "#43b581";
            $risk_icon = "✓";
            $survival_chance = "95%+";
        } elseif ($progress_75pct >= 100) {
            $risk_level = "LOW RISK";
            $risk_color = "#43b581";
            $risk_icon = "✓";
            $survival_chance = "90%+";
        } elseif ($projected_halfway >= $target_75pct) {
            $risk_level = "MODERATE";
            $risk_color = "#5865F2";
            $risk_icon = "⚡";
            $survival_chance = "75-85%";
        } elseif ($projected_halfway >= $target_50pct) {
            $risk_level = "HIGH RISK";
            $risk_color = "#faa61a";
            $risk_icon = "⚠";
            $survival_chance = "50-70%";
        } else {
            $risk_level = "CRITICAL";
            $risk_color = "#f04747";
            $risk_icon = "🚨";
            $survival_chance = "40% or less";
        }
    }
    
    // Full season projection
    $projected_final = round($ppg * 38, 1);
    
    // Calculate PPG and points needed for each safety threshold
    $ppg_to_thresholds = [];
    
    foreach ($safety_thresholds as $threshold_name => $threshold_points) {
        $points_needed = max(0, $threshold_points - $current_points);
        $ppg_needed = $games_remaining > 0 ? round($points_needed / $games_remaining, 2) : 0;
        
        // Determine feasibility
        if ($current_points >= $threshold_points) {
            $feasibility = 'ACHIEVED';
            $feasibility_color = '#43b581';
        } elseif ($ppg_needed <= 1.0) {
            $feasibility = 'EASY';
            $feasibility_color = '#43b581';
        } elseif ($ppg_needed <= 1.5) {
            $feasibility = 'ACHIEVABLE';
            $feasibility_color = '#5865F2';
        } elseif ($ppg_needed <= 2.0) {
            $feasibility = 'DIFFICULT';
            $feasibility_color = '#faa61a';
        } else {
            $feasibility = 'VERY HARD';
            $feasibility_color = '#f04747';
        }
        
        $ppg_to_thresholds[$threshold_name] = [
            'target' => $threshold_points,
            'points_needed' => $points_needed,
            'ppg_needed' => $ppg_needed,
            'feasibility' => $feasibility,
            'feasibility_color' => $feasibility_color
        ];
    }
    
    $teams_analysis[] = [
        'team' => $team,
        'progress_100pct' => $progress_100pct,
        'progress_75pct' => $progress_75pct,
        'ppg' => $ppg,
        'projected_halfway' => $projected_halfway,
        'projected_final' => $projected_final,
        'points_to_20' => $points_to_20,
        'points_to_15' => $points_to_15,
        'ppg_needed_75' => $ppg_needed_75,
        'games_to_halfway' => $games_to_halfway,
        'games_remaining' => $games_remaining,
        'risk_level' => $risk_level,
        'risk_color' => $risk_color,
        'risk_icon' => $risk_icon,
        'survival_chance' => $survival_chance,
        'ppg_to_thresholds' => $ppg_to_thresholds
    ];
}

// Sort by projected_halfway ascending - most at risk first
usort($teams_analysis, function($a, $b) {
    return $a['projected_halfway'] <=> $b['projected_halfway'];
});

// Count teams by risk level
$risk_counts = [
    'CRITICAL' => 0,
    'HIGH RISK' => 0,
    'MODERATE' => 0,
    'LOW RISK' => 0,
    'SAFE' => 0
];

foreach ($teams_analysis as $analysis) {
    $risk_counts[$analysis['risk_level']]++;
}

// Categorize teams by projected halfway points
$teams_100pct = array_filter($teams_analysis, function($a) use ($target_100pct) {
    return $a['projected_halfway'] >= $target_100pct;
});

$teams_75pct_only = array_filter($teams_analysis, function($a) use ($target_75pct, $target_100pct) {
    return $a['projected_halfway'] >= $target_75pct && $a['projected_halfway'] < $target_100pct;
});

$teams_at_risk = array_filter($teams_analysis, function($a) use ($target_75pct) {
    return $a['projected_halfway'] < $target_75pct;
});
?>

<!-- RELEGATION BATTLE ANALYZER -->

<!-- Hero Section -->
<div class="panel" style="border: 3px solid #f04747; background: linear-gradient(135deg, #f04747 0%, #2e3136 100%);">
    <div style="text-align: center;">
        <h1 style="color: #FFFFFF; font-size: 48px; margin: 0;">⚔️ RELEGATION BATTLE</h1>
        <h2 style="color: #ff6b6b; font-size: 32px; margin: 10px 0;">Who Will Hit the 75% Rule?</h2>
        <p style="color: #dcddde; font-size: 16px; margin-top: 15px;">
            Analyzing bottom 10 teams' chances of reaching 15 points (75% of 20) at halfway
        </p>
    </div>
</div>

<!-- Risk Overview -->
<div class="panel" style="background: #2e3136;">
    <h2>📊 Risk Level Distribution</h2>
    <div style="display: flex; justify-content: space-around; margin-top: 20px; flex-wrap: wrap;">
        <div style="text-align: center; padding: 15px; background: #43b581; border-radius: 8px; min-width: 150px; margin: 5px;">
            <div style="font-size: 36px; font-weight: bold; color: white;"><?php echo $risk_counts['SAFE']; ?></div>
            <div style="font-size: 14px; color: white;">✓ SAFE</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.8);">≥20 pts projected</div>
        </div>
        
        <div style="text-align: center; padding: 15px; background: #5865F2; border-radius: 8px; min-width: 150px; margin: 5px;">
            <div style="font-size: 36px; font-weight: bold; color: white;"><?php echo $risk_counts['LOW RISK']; ?></div>
            <div style="font-size: 14px; color: white;">✓ LOW RISK</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.8);">15-19 pts projected</div>
        </div>
        
        <div style="text-align: center; padding: 15px; background: #5865F2; opacity: 0.8; border-radius: 8px; min-width: 150px; margin: 5px;">
            <div style="font-size: 36px; font-weight: bold; color: white;"><?php echo $risk_counts['MODERATE']; ?></div>
            <div style="font-size: 14px; color: white;">⚡ MODERATE</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.8);">On track for 75%</div>
        </div>
        
        <div style="text-align: center; padding: 15px; background: #faa61a; border-radius: 8px; min-width: 150px; margin: 5px;">
            <div style="font-size: 36px; font-weight: bold; color: white;"><?php echo $risk_counts['HIGH RISK']; ?></div>
            <div style="font-size: 14px; color: white;">⚠ HIGH RISK</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.8);">10-14 pts projected</div>
        </div>
        
        <div style="text-align: center; padding: 15px; background: #f04747; border-radius: 8px; min-width: 150px; margin: 5px;">
            <div style="font-size: 36px; font-weight: bold; color: white;"><?php echo $risk_counts['CRITICAL']; ?></div>
            <div style="font-size: 14px; color: white;">🚨 CRITICAL</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.8);"><10 pts projected</div>
        </div>
    </div>
</div>

<!-- Teams Hitting 100% Target -->
<?php if (count($teams_100pct) > 0): ?>
<div class="panel" style="background: linear-gradient(135deg, #43b581, #6ef2b3); border: 3px solid #FFFFFF;">
    <div style="text-align: center;">
        <h2 style="color: white; font-size: 28px; margin: 0;">
            ✅ EXCELLENT: <?php echo count($teams_100pct); ?> Teams Will Hit 100% Target (20pts)
        </h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin-top: 10px;">
            These teams are projected to have 20+ points at halfway - extremely safe!
        </p>
    </div>
    
    <div style="margin-top: 15px; display: flex; justify-content: center; flex-wrap: wrap; gap: 10px;">
        <?php foreach ($teams_100pct as $analysis): ?>
        <span style="background: rgba(0,0,0,0.3); padding: 8px 15px; border-radius: 6px; color: white; font-weight: bold;">
            <?php echo htmlspecialchars($analysis['team']['team_name']); ?>
            (<?php echo round($analysis['projected_halfway']); ?> pts projected)
        </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Teams Hitting 75% But Not 100% -->
<?php if (count($teams_75pct_only) > 0): ?>
<div class="panel" style="background: linear-gradient(135deg, #5865F2, #7289DA); border: 3px solid #FFFFFF;">
    <div style="text-align: center;">
        <h2 style="color: white; font-size: 28px; margin: 0;">
            ⚡ GOOD: <?php echo count($teams_75pct_only); ?> Teams Will Hit 75% Target (15-19pts)
        </h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin-top: 10px;">
            These teams are projected to have 15-19 points at halfway - on track but could improve!
        </p>
    </div>

    <div style="margin-top: 15px; display: flex; justify-content: center; flex-wrap: wrap; gap: 10px;">
        <?php foreach ($teams_75pct_only as $analysis): ?>
        <span style="background: rgba(0,0,0,0.3); padding: 8px 15px; border-radius: 6px; color: white; font-weight: bold;">
            <?php echo htmlspecialchars($analysis['team']['team_name']); ?>
            (<?php echo round($analysis['projected_halfway']); ?> pts projected)
        </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Teams At Risk -->
<?php if (count($teams_at_risk) > 0): ?>
<div class="panel" style="background: linear-gradient(135deg, #f04747, #ff6b6b); border: 3px solid #FFFFFF;">
    <div style="text-align: center;">
        <h2 style="color: white; font-size: 28px; margin: 0;">
            🚨 DANGER: <?php echo count($teams_at_risk); ?> Teams Won't Hit 75% Rule
        </h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin-top: 10px;">
            These teams are projected to have less than 15 points at halfway - serious relegation danger!
        </p>
    </div>
    
    <div style="margin-top: 15px; display: flex; justify-content: center; flex-wrap: wrap; gap: 10px;">
        <?php foreach ($teams_at_risk as $analysis): ?>
        <span style="background: rgba(0,0,0,0.3); padding: 8px 15px; border-radius: 6px; color: white; font-weight: bold;">
            <?php echo htmlspecialchars($analysis['team']['team_name']); ?>
            (<?php echo round($analysis['projected_halfway']); ?> pts projected)
        </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Detailed Analysis Table -->
<div class="panel">
    <h2>📋 Complete Relegation Battle Analysis</h2>
    <p style="color: #888; font-size: 13px; margin-bottom: 15px;">
        Sorted by projected halfway points (most at risk first)
    </p>
    
    <div style="overflow-x: auto;">
    <table>
        <tr>
            <th>Pos</th>
            <th>Team</th>
            <th>P</th>
            <th>Pts</th>
            <th>PPG</th>
            <th>75% Progress</th>
            <th>100% Progress</th>
            <th>Projected Halfway</th>
            <th>Projected Final</th>
            <th>Risk Level</th>
            <th>Survival %</th>
        </tr>
        <?php foreach ($teams_analysis as $analysis): ?>
        <?php 
        $team = $analysis['team'];
        $is_relegated_zone = $team['position'] >= 18;
        ?>
        <tr style="<?php echo $is_relegated_zone ? 'background: rgba(244, 71, 71, 0.2); border-left: 4px solid #f04747;' : ''; ?>">
            <td><strong><?php echo $team['position']; ?></strong></td>
            <td>
                <strong><?php echo htmlspecialchars($team['team_name']); ?></strong>
                <?php if ($is_relegated_zone): ?>
                <span style="color: #f04747; margin-left: 5px;">⬇</span>
                <?php endif; ?>
            </td>
            <td><?php echo $team['played']; ?></td>
            <td><strong><?php echo $team['points']; ?></strong></td>
            <td><?php echo number_format($analysis['ppg'], 2); ?></td>
            
            <td>
                <div style="background: #40444b; border-radius: 4px; height: 20px; width: 100px; position: relative; overflow: hidden;">
                    <?php
                    $bar_width = min(100, $analysis['progress_75pct']);
                    if ($analysis['progress_75pct'] >= 100) {
                        $bar_color = "#43b581";
                    } elseif ($analysis['progress_75pct'] >= 75) {
                        $bar_color = "#5865F2";
                    } elseif ($analysis['progress_75pct'] >= 50) {
                        $bar_color = "#faa61a";
                    } else {
                        $bar_color = "#f04747";
                    }
                    ?>
                    <div style="background: <?php echo $bar_color; ?>; width: <?php echo $bar_width; ?>%; height: 100%;"></div>
                    <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 11px; font-weight: bold; color: white;">
                        <?php echo round($analysis['progress_75pct']); ?>%
                    </span>
                </div>
            </td>

            <td>
                <div style="background: #40444b; border-radius: 4px; height: 20px; width: 100px; position: relative; overflow: hidden;">
                    <?php
                    $bar_width = min(100, $analysis['progress_100pct']);
                    if ($analysis['progress_100pct'] >= 100) {
                        $bar_color = "#43b581";
                    } elseif ($analysis['progress_100pct'] >= 75) {
                        $bar_color = "#5865F2";
                    } elseif ($analysis['progress_100pct'] >= 50) {
                        $bar_color = "#faa61a";
                    } else {
                        $bar_color = "#f04747";
                    }
                    ?>
                    <div style="background: <?php echo $bar_color; ?>; width: <?php echo $bar_width; ?>%; height: 100%;"></div>
                    <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 11px; font-weight: bold; color: white;">
                        <?php echo round($analysis['progress_100pct']); ?>%
                    </span>
                </div>
            </td>
            
            <td style="font-weight: bold; color: <?php echo $analysis['projected_halfway'] >= 15 ? '#43b581' : '#f04747'; ?>;">
                <?php echo round($analysis['projected_halfway'], 1); ?> pts
            </td>
            
            <td style="font-weight: bold; color: <?php echo $analysis['projected_final'] >= 38 ? '#43b581' : '#f04747'; ?>;">
                <?php echo $analysis['projected_final']; ?> pts
            </td>
            
            <td>
                <span style="background: <?php echo $analysis['risk_color']; ?>; padding: 5px 10px; border-radius: 4px; color: white; font-weight: bold; font-size: 11px; white-space: nowrap;">
                    <?php echo $analysis['risk_icon']; ?> <?php echo $analysis['risk_level']; ?>
                </span>
            </td>
            
            <td style="color: <?php echo $analysis['risk_color']; ?>; font-weight: bold;">
                <?php echo $analysis['survival_chance']; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<!-- PPG Required to Reach Safety Thresholds -->
<div class="panel">
    <h2>⚽ Points Per Game Needed to Reach Safety Targets</h2>
    <p style="color: #888; font-size: 13px; margin-bottom: 20px;">
        How many PPG each team needs to hit historical safety benchmarks
    </p>
    
    <!-- Threshold Legend -->
    <div style="background: #2c2f33; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="color: #5865F2; margin: 0 0 10px 0;">🎯 Safety Thresholds Explained</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
            <div>
                <strong style="color: #FFCD00;">36 Points (Average)</strong>
                <p style="color: #888; font-size: 12px; margin: 5px 0 0 0;">
                    Average points total of 18th-placed team plus one
                </p>
            </div>
            <div>
                <strong style="color: #43b581;">40 Points (Magic)</strong>
                <p style="color: #888; font-size: 12px; margin: 5px 0 0 0;">
                    Traditional benchmark - only West Ham (42pts, 2002-03) relegated above this
                </p>
            </div>
            <div>
                <strong style="color: #5865F2;">34 Points (Historical Low)</strong>
                <p style="color: #888; font-size: 12px; margin: 5px 0 0 0;">
                    West Brom's survival total (2004-05)
                </p>
            </div>
            <div>
                <strong style="color: #faa61a;">27 Points (Recent Low)</strong>
                <p style="color: #888; font-size: 12px; margin: 5px 0 0 0;">
                    2023-24 season threshold (26 points in 2024-25)
                </p>
            </div>
        </div>
    </div>
    
    <div style="overflow-x: auto;">
    <table>
        <tr>
            <th>Team</th>
            <th>Current</th>
            <th>Games Left</th>
            <th style="background: rgba(255, 205, 0, 0.2);">To 36pts<br><small>(PPG needed)</small></th>
            <th style="background: rgba(67, 181, 129, 0.2);">To 40pts<br><small>(PPG needed)</small></th>
            <th style="background: rgba(88, 101, 242, 0.2);">To 34pts<br><small>(PPG needed)</small></th>
            <th style="background: rgba(250, 166, 26, 0.2);">To 27pts<br><small>(PPG needed)</small></th>
        </tr>
        <?php foreach ($teams_analysis as $analysis): ?>
            <?php 
            $team = $analysis['team'];
            $is_leeds = (stripos($team['team_name'], 'Leeds') !== false);
            ?>
            <tr style="<?php echo $is_leeds ? 'background: rgba(29, 66, 138, 0.3); border: 2px solid #FFCD00;' : ''; ?>">
                <td>
                    <strong <?php echo $is_leeds ? 'style="color: #FFCD00;"' : ''; ?>>
                        <?php echo htmlspecialchars($team['team_name']); ?>
                    </strong>
                </td>
                <td><strong><?php echo $team['points']; ?> pts</strong></td>
                <td><?php echo $analysis['games_remaining']; ?></td>
                
                <!-- 36 Points Target -->
                <td style="text-align: center;">
                    <?php 
                    $target = $analysis['ppg_to_thresholds']['average'];
                    if ($target['points_needed'] == 0): ?>
                        <span style="color: #43b581; font-weight: bold; font-size: 16px;">✓ SAFE</span>
                    <?php else: ?>
                        <div style="font-size: 20px; font-weight: bold; color: <?php echo $target['feasibility_color']; ?>;">
                            <?php echo $target['ppg_needed']; ?>
                        </div>
                        <div style="font-size: 11px; color: #888; margin-top: 3px;">
                            (<?php echo $target['points_needed']; ?> pts needed)
                        </div>
                        <span class="badge" style="background: <?php echo $target['feasibility_color']; ?>; font-size: 10px; margin-top: 5px;">
                            <?php echo $target['feasibility']; ?>
                        </span>
                    <?php endif; ?>
                </td>
                
                <!-- 40 Points Target -->
                <td style="text-align: center;">
                    <?php 
                    $target = $analysis['ppg_to_thresholds']['magic'];
                    if ($target['points_needed'] == 0): ?>
                        <span style="color: #43b581; font-weight: bold; font-size: 16px;">✓ SAFE</span>
                    <?php else: ?>
                        <div style="font-size: 20px; font-weight: bold; color: <?php echo $target['feasibility_color']; ?>;">
                            <?php echo $target['ppg_needed']; ?>
                        </div>
                        <div style="font-size: 11px; color: #888; margin-top: 3px;">
                            (<?php echo $target['points_needed']; ?> pts needed)
                        </div>
                        <span class="badge" style="background: <?php echo $target['feasibility_color']; ?>; font-size: 10px; margin-top: 5px;">
                            <?php echo $target['feasibility']; ?>
                        </span>
                    <?php endif; ?>
                </td>
                
                <!-- 34 Points Target -->
                <td style="text-align: center;">
                    <?php 
                    $target = $analysis['ppg_to_thresholds']['lowest_historical'];
                    if ($target['points_needed'] == 0): ?>
                        <span style="color: #43b581; font-weight: bold; font-size: 16px;">✓ SAFE</span>
                    <?php else: ?>
                        <div style="font-size: 20px; font-weight: bold; color: <?php echo $target['feasibility_color']; ?>;">
                            <?php echo $target['ppg_needed']; ?>
                        </div>
                        <div style="font-size: 11px; color: #888; margin-top: 3px;">
                            (<?php echo $target['points_needed']; ?> pts needed)
                        </div>
                        <span class="badge" style="background: <?php echo $target['feasibility_color']; ?>; font-size: 10px; margin-top: 5px;">
                            <?php echo $target['feasibility']; ?>
                        </span>
                    <?php endif; ?>
                </td>
                
                <!-- 27 Points Target -->
                <td style="text-align: center;">
                    <?php 
                    $target = $analysis['ppg_to_thresholds']['recent_low'];
                    if ($target['points_needed'] == 0): ?>
                        <span style="color: #43b581; font-weight: bold; font-size: 16px;">✓ SAFE</span>
                    <?php else: ?>
                        <div style="font-size: 20px; font-weight: bold; color: <?php echo $target['feasibility_color']; ?>;">
                            <?php echo $target['ppg_needed']; ?>
                        </div>
                        <div style="font-size: 11px; color: #888; margin-top: 3px;">
                            (<?php echo $target['points_needed']; ?> pts needed)
                        </div>
                        <span class="badge" style="background: <?php echo $target['feasibility_color']; ?>; font-size: 10px; margin-top: 5px;">
                            <?php echo $target['feasibility']; ?>
                        </span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    </div>
    
    <!-- Key Insights -->
    <div style="background: #2c2f33; padding: 15px; border-radius: 8px; margin-top: 20px;">
        <h4 style="color: #5865F2; margin: 0 0 10px 0;">💡 PPG Feasibility Guide</h4>
        <ul style="margin: 0; padding-left: 20px; color: #dcddde; line-height: 2;">
            <li><strong style="color: #43b581;">EASY (PPG ≤ 1.0):</strong> Very achievable - normal form will reach target</li>
            <li><strong style="color: #5865F2;">ACHIEVABLE (PPG 1.0-1.5):</strong> Possible with improved form</li>
            <li><strong style="color: #faa61a;">DIFFICULT (PPG 1.5-2.0):</strong> Requires strong run of results</li>
            <li><strong style="color: #f04747;">VERY HARD (PPG > 2.0):</strong> Near impossible - would need exceptional form</li>
        </ul>
    </div>
</div>

<!-- Points Needed Analysis -->
<div class="panel">
    <h2>📊 Points Needed to Hit Targets (Remaining to Halfway)</h2>
    <p style="color: #888; font-size: 13px; margin-bottom: 15px;">
        How many points each team needs in remaining games before Game 19
    </p>
    
    <div style="overflow-x: auto;">
    <table>
        <tr>
            <th>Team</th>
            <th>Current Pts</th>
            <th>Games to Halfway</th>
            <th>To Hit 15 (75%)</th>
            <th>To Hit 20 (100%)</th>
            <th>PPG Needed (75%)</th>
            <th>Feasibility</th>
        </tr>
        <?php foreach ($teams_analysis as $analysis): ?>
        <?php 
        $team = $analysis['team'];
        
        // Feasibility assessment
        if ($analysis['points_to_15'] == 0) {
            $feasibility = "✓ DONE";
            $feasibility_color = "#43b581";
        } elseif ($analysis['games_to_halfway'] == 0) {
            $feasibility = $team['points'] >= 15 ? "✓ HIT IT" : "❌ MISSED";
            $feasibility_color = $team['points'] >= 15 ? "#43b581" : "#f04747";
        } elseif ($analysis['ppg_needed_75'] <= 1.0) {
            $feasibility = "LIKELY";
            $feasibility_color = "#43b581";
        } elseif ($analysis['ppg_needed_75'] <= 1.5) {
            $feasibility = "TOUGH";
            $feasibility_color = "#faa61a";
        } elseif ($analysis['ppg_needed_75'] <= 2.0) {
            $feasibility = "HARD";
            $feasibility_color = "#f04747";
        } else {
            $feasibility = "UNLIKELY";
            $feasibility_color = "#f04747";
        }
        ?>
        <tr>
            <td><strong><?php echo htmlspecialchars($team['team_name']); ?></strong></td>
            <td><?php echo $team['points']; ?></td>
            <td><?php echo $analysis['games_to_halfway']; ?></td>
            <td style="font-weight: bold; color: <?php echo $analysis['points_to_15'] == 0 ? '#43b581' : '#faa61a'; ?>;">
                <?php echo $analysis['points_to_15']; ?> pts
            </td>
            <td style="font-weight: bold; color: <?php echo $analysis['points_to_20'] == 0 ? '#43b581' : '#f04747'; ?>;">
                <?php echo $analysis['points_to_20']; ?> pts
            </td>
            <td><?php echo number_format($analysis['ppg_needed_75'], 2); ?></td>
            <td style="color: <?php echo $feasibility_color; ?>; font-weight: bold;">
                <?php echo $feasibility; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<!-- Individual Team Deep Dives -->
<div class="grid">
    <?php
    // Show top 3 most at risk teams in detail
    $top_risk = array_slice($teams_analysis, 0, 3);
    foreach ($top_risk as $analysis):
        $team = $analysis['team'];
    ?>
    <div class="panel" style="background: #2e3136; border-left: 4px solid <?php echo $analysis['risk_color']; ?>;">
        <h3 style="color: <?php echo $analysis['risk_color']; ?>; margin-bottom: 10px;">
            <?php echo $analysis['risk_icon']; ?> <?php echo htmlspecialchars($team['team_name']); ?>
        </h3>
        
        <div style="margin-bottom: 15px;">
            <div style="font-size: 14px; color: #888;">Current Position</div>
            <div style="font-size: 32px; font-weight: bold; color: <?php echo $analysis['risk_color']; ?>;">
                <?php echo $team['position']; ?>th Place
            </div>
        </div>
        
        <div style="background: #40444b; padding: 15px; border-radius: 6px; margin-bottom: 10px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span style="color: #888; font-size: 13px;">Current Points</span>
                <span style="color: white; font-weight: bold;"><?php echo $team['points']; ?> / 20</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span style="color: #888; font-size: 13px;">75% Progress</span>
                <span style="color: <?php echo $analysis['progress_75pct'] >= 100 ? '#43b581' : '#f04747'; ?>; font-weight: bold;">
                    <?php echo round($analysis['progress_75pct']); ?>%
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span style="color: #888; font-size: 13px;">Projected Halfway</span>
                <span style="color: <?php echo $analysis['projected_halfway'] >= 15 ? '#43b581' : '#f04747'; ?>; font-weight: bold;">
                    <?php echo round($analysis['projected_halfway'], 1); ?> pts
                </span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #888; font-size: 13px;">PPG Needed (75%)</span>
                <span style="color: white; font-weight: bold;"><?php echo number_format($analysis['ppg_needed_75'], 2); ?></span>
            </div>
        </div>
        
        <div style="text-align: center; background: <?php echo $analysis['risk_color']; ?>; padding: 10px; border-radius: 6px;">
            <div style="font-size: 12px; color: white; margin-bottom: 5px;">Survival Probability</div>
            <div style="font-size: 24px; font-weight: bold; color: white;">
                <?php echo $analysis['survival_chance']; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Key Insights -->
<div class="panel" style="background: #2e3136; border-left: 4px solid #5865F2;">
    <h3>🔍 Key Insights</h3>
    <ul style="color: #dcddde; line-height: 2;">
        <li><strong style="color: #FFCD00;">The 75% Rule:</strong> Teams with 15 points at halfway have an 85-90% survival rate historically.</li>
        <li><strong style="color: #FFCD00;">Critical Threshold:</strong> Below 10 points at halfway typically leads to relegation (70% chance).</li>
        <li><strong style="color: #FFCD00;">PPG Analysis:</strong> Teams needing 1.5+ PPG to hit 75% are in serious danger - very hard to achieve!</li>
        <li><strong style="color: #FFCD00;">Form Matters:</strong> Current PPG projects future performance - consistency is key to survival.</li>
        <?php if (count($teams_at_risk) > 0): ?>
        <li style="color: #f04747;"><strong><?php echo count($teams_at_risk); ?> teams currently projected to miss 75% target</strong> - they need significant improvement!</li>
        <?php endif; ?>
        <li><strong style="color: #43b581;">The Magic 40:</strong> Only West Ham (2002-03 with 42pts) has been relegated with 40+ points in modern era.</li>
        <li><strong style="color: #faa61a;">Recent Trends:</strong> Survival thresholds have dropped (27pts in 2023-24, 26pts in 2024-25) - weaker bottom teams.</li>
    </ul>
</div>
