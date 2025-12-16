<?php
// RELEGATION BATTLE ANALYZER - Who's at risk? + Six-Pointer Tracker

// Get all teams in bottom 10
$stmt = $db->query("
    SELECT * FROM league_table 
    WHERE position >= 11
    ORDER BY position ASC
");
$bottom_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Leeds data for six-pointer impact analysis
$stmt = $db->prepare("SELECT * FROM league_table WHERE team_name = 'Leeds United' OR team_name LIKE '%Leeds%'");
$stmt->execute();
$leeds = $stmt->fetch(PDO::FETCH_ASSOC);

// Halfway point constants
$halfway_point = 19;
$target_100pct = 20; // Full target
$target_95pct = 18;  // 95% rule
$target_90pct = 17;  // 90% rule
$target_85pct = 16;  // 85% rule
$target_75pct = 15;  // 75% rule
$target_50pct = 10;  // Critical zone

// Calculate stats for each team
$teams_analysis = [];

foreach ($bottom_teams as $team) {
    $games_played = $team['played'];
    $current_points = $team['points'];
    $games_to_halfway = max(0, $halfway_point - $games_played);
    
    // Calculate percentages
    $progress_pct = ($current_points / $target_100pct) * 100;
    $progress_95_pct = ($current_points / $target_95pct) * 100;
    $progress_90_pct = ($current_points / $target_90pct) * 100;
    $progress_85_pct = ($current_points / $target_85pct) * 100;
    $progress_75_pct = ($current_points / $target_75pct) * 100;
    
    // Current PPG
    $ppg = $games_played > 0 ? $current_points / $games_played : 0;
    
    // Projected points at halfway
    $projected_halfway = $current_points + ($ppg * $games_to_halfway);
    
    // Points needed to hit targets
    $points_to_20 = max(0, $target_100pct - $current_points);
    $points_to_18 = max(0, $target_95pct - $current_points);
    $points_to_17 = max(0, $target_90pct - $current_points);
    $points_to_16 = max(0, $target_85pct - $current_points);
    $points_to_15 = max(0, $target_75pct - $current_points);
    
    // PPG needed to hit 75% target
    $ppg_needed_75 = $games_to_halfway > 0 ? $points_to_15 / $games_to_halfway : 0;
    $ppg_needed_85 = $games_to_halfway > 0 ? $points_to_16 / $games_to_halfway : 0;
    $ppg_needed_90 = $games_to_halfway > 0 ? $points_to_17 / $games_to_halfway : 0;
    $ppg_needed_95 = $games_to_halfway > 0 ? $points_to_18 / $games_to_halfway : 0;
    $ppg_needed_100 = $games_to_halfway > 0 ? $points_to_20 / $games_to_halfway : 0;
    
    // Risk assessment
    if ($games_played >= $halfway_point) {
        // Already at halfway - assess what they got
        if ($current_points >= $target_100pct) {
            $risk_level = 'SAFE';
            $risk_color = '#43b581';
            $risk_icon = '✅';
            $survival_chance = '95%+';
        } elseif ($current_points >= $target_75pct) {
            $risk_level = 'LOW RISK';
            $risk_color = '#5865F2';
            $risk_icon = '✅';
            $survival_chance = '85-90%';
        } elseif ($current_points >= $target_50pct) {
            $risk_level = 'MODERATE';
            $risk_color = '#faa61a';
            $risk_icon = '⚠️';
            $survival_chance = '50-60%';
        } else {
            $risk_level = 'HIGH RISK';
            $risk_color = '#f04747';
            $risk_icon = '🚨';
            $survival_chance = '30% or less';
        }
    } else {
        // Before halfway - project if they'll hit 75%
        if ($progress_pct >= 100) {
            $risk_level = 'SAFE';
            $risk_color = '#43b581';
            $risk_icon = '✅';
            $survival_chance = '95%+';
        } elseif ($progress_75_pct >= 100) {
            $risk_level = 'LOW RISK';
            $risk_color = '#43b581';
            $risk_icon = '✅';
            $survival_chance = '90%+';
        } elseif ($projected_halfway >= $target_75pct) {
            $risk_level = 'MODERATE';
            $risk_color = '#5865F2';
            $risk_icon = '⚠️';
            $survival_chance = '75-85%';
        } elseif ($projected_halfway >= $target_50pct) {
            $risk_level = 'HIGH RISK';
            $risk_color = '#faa61a';
            $risk_icon = '⚠️';
            $survival_chance = '50-70%';
        } else {
            $risk_level = 'CRITICAL';
            $risk_color = '#f04747';
            $risk_icon = '🚨';
            $survival_chance = '40% or less';
        }
    }
    
    // Full season projection
    $projected_final = round($ppg * 38, 1);
    
    $teams_analysis[] = [
        'team' => $team,
        'progress_pct' => $progress_pct,
        'progress_90_pct' => $progress_90_pct,
        'progress_75_pct' => $progress_75_pct,
        'ppg' => $ppg,
        'projected_halfway' => $projected_halfway,
        'projected_final' => $projected_final,
        'points_to_20' => $points_to_20,
        'points_to_18' => $points_to_18,
        'points_to_17' => $points_to_17,
        'points_to_16' => $points_to_16,
        'points_to_15' => $points_to_15,
        'ppg_needed_75' => $ppg_needed_75,
        'ppg_needed_85' => $ppg_needed_85,
        'ppg_needed_90' => $ppg_needed_90,
        'ppg_needed_95' => $ppg_needed_95,
        'ppg_needed_100' => $ppg_needed_100,
        'games_to_halfway' => $games_to_halfway,
        'risk_level' => $risk_level,
        'risk_color' => $risk_color,
        'risk_icon' => $risk_icon,
        'survival_chance' => $survival_chance,
    ];
}

// Sort by projected_halfway (ascending - most at risk first)
usort($teams_analysis, function($a, $b) {
    return $a['projected_halfway'] <=> $b['projected_halfway'];
});

// Count teams by risk level
$risk_counts = [
    'CRITICAL' => 0,
    'HIGH RISK' => 0,
    'MODERATE' => 0,
    'LOW RISK' => 0,
    'SAFE' => 0,
];

foreach ($teams_analysis as $analysis) {
    $risk_counts[$analysis['risk_level']]++;
}

// Find most at risk (won't hit 75%)
$most_at_risk = array_filter($teams_analysis, function($a) use ($target_75pct) {
    return $a['projected_halfway'] < $target_75pct;
});

// Find safe teams (will hit 75%+)
$safe_teams = array_filter($teams_analysis, function($a) use ($target_75pct) {
    return $a['projected_halfway'] >= $target_75pct;
});

// SIX-POINTER ANALYSIS
// Define upcoming six-pointers (in real app, would fetch from fixtures table)
$six_pointers = [
    [
        'date' => 'Dec 14, 2025',
        'home' => 'West Ham United',
        'home_pos' => 18,
        'away' => 'Burnley',
        'away_pos' => 19,
        'importance' => 'CRITICAL',
        'impact' => 'Winner climbs out of bottom 3, loser sinks deeper. Leeds hopes for a draw - both rivals drop 2 points!',
        'ideal_result' => 'Draw',
        'status' => 'upcoming',
    ],
    [
        'date' => 'Dec 21, 2025',
        'home' => 'Nottingham Forest',
        'home_pos' => 17,
        'away' => 'West Ham United',
        'away_pos' => 18,
        'importance' => 'HIGH',
        'impact' => '17th vs 18th battle! Draw would be perfect for Leeds - both rivals waste points fighting each other.',
        'ideal_result' => 'Draw',
        'status' => 'upcoming',
    ],
    [
        'date' => 'Dec 28, 2025',
        'home' => 'Burnley',
        'home_pos' => 19,
        'away' => 'Wolves',
        'away_pos' => 20,
        'importance' => 'MODERATE',
        'impact' => 'Bottom two clash. Leeds likely safe from both by now, but shows who\'s definitely relegated.',
        'ideal_result' => 'Draw or Wolves win',
        'status' => 'upcoming',
    ],
    [
        'date' => 'Jan 11, 2026',
        'home' => 'West Ham United',
        'home_pos' => 18,
        'away' => 'Nottingham Forest',
        'away_pos' => 17,
        'importance' => 'CRITICAL',
        'impact' => 'Return fixture! Could be decisive for who goes down. Leeds wants another draw!',
        'ideal_result' => 'Draw',
        'status' => 'upcoming',
    ],
];

// Identify teams in relegation positions (18th-20th)
$relegation_zone_teams = array_filter($teams_analysis, function($a) {
    return $a['team']['position'] >= 18;
});
?>

<!-- RELEGATION BATTLE ANALYZER -->

<!-- Hero Section -->
<div class="panel" style="border: 3px solid #f04747; background: linear-gradient(135deg, #f04747 0%, #2e3136 100%);">
    <div style="text-align: center;">
        <h1 style="color: #FFFFFF; font-size: 48px; margin: 0;">
            🚨 RELEGATION BATTLE
        </h1>
        <h2 style="color: #ff6b6b; font-size: 32px; margin: 10px 0;">
            Who Will Hit the 75% Rule?
        </h2>
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
            <div style="font-size: 14px; color: white;">✅ SAFE</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.8);">20+ pts projected</div>
        </div>
        
        <div style="text-align: center; padding: 15px; background: #5865F2; border-radius: 8px; min-width: 150px; margin: 5px;">
            <div style="font-size: 36px; font-weight: bold; color: white;"><?php echo $risk_counts['LOW RISK']; ?></div>
            <div style="font-size: 14px; color: white;">✅ LOW RISK</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.8);">15-19 pts projected</div>
        </div>
        
        <div style="text-align: center; padding: 15px; background: #5865F2; opacity: 0.8; border-radius: 8px; min-width: 150px; margin: 5px;">
            <div style="font-size: 36px; font-weight: bold; color: white;"><?php echo $risk_counts['MODERATE']; ?></div>
            <div style="font-size: 14px; color: white;">⚠️ MODERATE</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.8);">On track for 75%</div>
        </div>
        
        <div style="text-align: center; padding: 15px; background: #faa61a; border-radius: 8px; min-width: 150px; margin: 5px;">
            <div style="font-size: 36px; font-weight: bold; color: white;"><?php echo $risk_counts['HIGH RISK']; ?></div>
            <div style="font-size: 14px; color: white;">⚠️ HIGH RISK</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.8);">10-14 pts projected</div>
        </div>
        
        <div style="text-align: center; padding: 15px; background: #f04747; border-radius: 8px; min-width: 150px; margin: 5px;">
            <div style="font-size: 36px; font-weight: bold; color: white;"><?php echo $risk_counts['CRITICAL']; ?></div>
            <div style="font-size: 14px; color: white;">🚨 CRITICAL</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.8);">&lt;10 pts projected</div>
        </div>
    </div>
</div>

<!-- SIX-POINTER TRACKER SECTION -->
<div class="panel" style="background: linear-gradient(135deg, #f04747, #faa61a); border: 3px solid #FFFFFF;">
    <div style="text-align: center;">
        <h2 style="color: white; font-size: 32px; margin: 0;">
            ⚔️ UPCOMING SIX-POINTERS
        </h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin-top: 10px;">
            Relegation rivals facing each other - these affect Leeds WITHOUT playing!
        </p>
    </div>
    
    <div style="margin-top: 20px;">
        <?php foreach ($six_pointers as $match): ?>
        <?php 
        $importance_color = $match['importance'] === 'CRITICAL' ? '#f04747' : 
                           ($match['importance'] === 'HIGH' ? '#faa61a' : '#5865F2');
        ?>
        <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap;">
                <span style="color: #dcddde; font-size: 14px;">📅 <?php echo $match['date']; ?></span>
                <span style="background: <?php echo $importance_color; ?>; padding: 5px 10px; border-radius: 4px; color: white; font-weight: bold; font-size: 12px;">
                    <?php echo $match['importance']; ?>
                </span>
            </div>
            
            <div style="text-align: center; margin-bottom: 10px;">
                <div style="color: white; font-size: 18px; font-weight: bold; margin-bottom: 5px;">
                    ⚔️ <?php echo $match['home']; ?> vs <?php echo $match['away']; ?>
                </div>
                <div style="color: rgba(255,255,255,0.7); font-size: 13px;">
                    <?php echo $match['home_pos']; ?>th vs <?php echo $match['away_pos']; ?>th place
                </div>
            </div>
            
            <div style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 4px; margin-bottom: 10px;">
                <span style="color: #FFCD00; font-weight: bold; font-size: 13px;">Leeds Impact:</span>
                <p style="color: white; font-size: 13px; margin: 5px 0 0 0;">
                    <?php echo $match['impact']; ?>
                </p>
            </div>
            
            <div style="background: rgba(67, 181, 129, 0.2); padding: 8px; border-radius: 4px; border-left: 3px solid #43b581;">
                <span style="color: #43b581; font-weight: bold; font-size: 12px;">
                    ✅ Ideal Result for Leeds: <?php echo $match['ideal_result']; ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Six-Pointer Strategy Explainer -->
<div class="panel" style="background: #2e3136; border-left: 4px solid #faa61a;">
    <h3>🎯 The Six-Pointer Strategy</h3>
    <p style="color: #dcddde; line-height: 1.6;">
        <strong style="color: #FFCD00;">What's a Six-Pointer?</strong>
        When two relegation rivals play each other, the winner gains 3 points while the loser gets 0.
        This creates a <strong>6-point swing</strong> in the gap between them!
    </p>
    <p style="color: #dcddde; line-height: 1.6;">
        <strong style="color: #FFCD00;">Why Leeds Cares:</strong>
        When rivals play each other, <strong>one MUST drop points</strong>. Leeds can improve their position
        without even playing! A draw is often ideal - both rivals waste 2 points vs a win.
    </p>
    <div style="background: #40444b; padding: 15px; border-radius: 8px; margin-top: 15px;">
        <h4 style="color: #FFCD00; margin-top: 0;">Leeds' Ideal Outcomes:</h4>
        <ul style="color: #dcddde; line-height: 2; margin: 10px 0;">
            <li><strong style="color: #43b581;">✅ Draw:</strong> Both teams drop 2 points vs a win - Leeds pulls relatively clear!</li>
            <li><strong style="color: #faa61a;">⚠️ Team Above Leeds Loses:</strong> Leeds' gap to safety widens</li>
            <li><strong style="color: #5865F2;">⚠️ Team Below Leeds Wins:</strong> They get closer, but still behind</li>
            <li><strong style="color: #f04747;">🚨 Worst:</strong> Team below Leeds wins convincingly - could overtake!</li>
        </ul>
    </div>
    <div style="background: rgba(67, 181, 129, 0.1); padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 3px solid #43b581;">
        <p style="color: #43b581; font-weight: bold; margin: 0;">
            💡 Pro Tip: Six-pointers become MORE valuable as season progresses. In final 10 games, they're worth their weight in gold!
        </p>
    </div>
</div>

<!-- Most At Risk Alert -->
<?php if (count($most_at_risk) > 0): ?>
<div class="panel" style="background: linear-gradient(135deg, #f04747, #ff6b6b); border: 3px solid #FFFFFF;">
    <div style="text-align: center;">
        <h2 style="color: white; font-size: 28px; margin: 0;">
            🚨 ALERT: <?php echo count($most_at_risk); ?> Teams Won't Hit 75% Rule
        </h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin-top: 10px;">
            These teams are projected to have less than 15 points at halfway
        </p>
        <div style="margin-top: 15px; display: flex; justify-content: center; flex-wrap: wrap; gap: 10px;">
            <?php foreach ($most_at_risk as $analysis): ?>
                <span style="background: rgba(0,0,0,0.3); padding: 8px 15px; border-radius: 6px; color: white; font-weight: bold;">
                    <?php echo htmlspecialchars($analysis['team']['team_name']); ?>
                    (<?php echo round($analysis['projected_halfway']); ?> pts projected)
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Detailed Analysis Table -->
<div class="panel">
    <h2>📈 Complete Relegation Battle Analysis</h2>
    <p style="color: #888; font-size: 13px; margin-bottom: 15px;">
        Sorted by projected halfway points (most at risk first)
    </p>
    <table>
        <tr>
            <th>Pos</th>
            <th>Team</th>
            <th>P</th>
            <th>Pts</th>
            <th>PPG</th>
            <th>75% Progress</th>
            <th>Projected Halfway</th>
            <th>Projected Final</th>
            <th>Risk Level</th>
            <th>Survival %</th>
        </tr>
        <?php foreach ($teams_analysis as $analysis): ?>
        <?php 
        $team = $analysis['team'];
        $is_relegated_zone = $team['position'] >= 18;
        $is_leeds = stripos($team['team_name'], 'Leeds') !== false;
        ?>
        <tr style="<?php echo $is_relegated_zone ? 'background: rgba(244, 71, 71, 0.2); border-left: 4px solid #f04747;' : ''; ?><?php echo $is_leeds ? 'background: rgba(29, 66, 138, 0.3); border-left: 4px solid #FFCD00;' : ''; ?>">
            <td><strong><?php echo $team['position']; ?></strong></td>
            <td>
                <?php if ($is_leeds): ?>
                    <strong style="color: #FFCD00;">🤍💛💙 <?php echo htmlspecialchars($team['team_name']); ?></strong>
                <?php else: ?>
                    <strong><?php echo htmlspecialchars($team['team_name']); ?></strong>
                <?php endif; ?>
                <?php if ($is_relegated_zone && !$is_leeds): ?>
                    <span style="color: #f04747; margin-left: 5px;">🔻</span>
                <?php endif; ?>
            </td>
            <td><?php echo $team['played']; ?></td>
            <td><strong><?php echo $team['points']; ?></strong></td>
            <td><?php echo number_format($analysis['ppg'], 2); ?></td>
            <td>
                <div style="background: #40444b; border-radius: 4px; height: 20px; width: 100px; position: relative; overflow: hidden;">
                    <?php 
                    $bar_width = min(100, $analysis['progress_75_pct']);
                    if ($analysis['progress_75_pct'] >= 100) {
                        $bar_color = '#43b581';
                    } elseif ($analysis['progress_75_pct'] >= 75) {
                        $bar_color = '#5865F2';
                    } elseif ($analysis['progress_75_pct'] >= 50) {
                        $bar_color = '#faa61a';
                    } else {
                        $bar_color = '#f04747';
                    }
                    ?>
                    <div style="background: <?php echo $bar_color; ?>; width: <?php echo $bar_width; ?>%; height: 100%;"></div>
                    <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 11px; font-weight: bold; color: white;">
                        <?php echo round($analysis['progress_75_pct']); ?>%
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

<!-- Points Needed Analysis -->
<div class="panel">
    <h2>🎯 Points Needed to Hit Targets (Remaining to Halfway)</h2>
    <p style="color: #888; font-size: 13px; margin-bottom: 15px;">
        How many points each team needs in remaining games before Game 19
    </p>
    <table>
        <tr>
            <th>Team</th>
            <th>Current Pts</th>
            <th>Games to Halfway</th>
            <th>To Hit 15 (75%)</th>
            <th>To Hit 16 (85%)</th>
            <th>To Hit 17 (90%)</th>
            <th>To Hit 18 (95%)</th>
            <th>To Hit 20 (100%)</th>
            <th>PPG Needed (75%)</th>
            <th>PPG Needed (85%)</th>
            <th>PPG Needed (90%)</th>
            <th>PPG Needed (95%)</th>
            <th>PPG Needed (100%)</th>
            <th>Feasibility (75%)</th>
            <th>Feasibility (100%)</th>
        </tr>
        <?php foreach ($teams_analysis as $analysis): ?>
        <?php 
        $team = $analysis['team'];
        $is_leeds = stripos($team['team_name'], 'Leeds') !== false;
        
        // Feasibility assessment
        if ($analysis['points_to_15'] == 0) {
            $feasibility75 = '✅ DONE';
            $feasibility_color = '#43b581';
        } elseif ($analysis['games_to_halfway'] == 0) {
            $feasibility75 = $team['points'] >= 15 ? '✅ HIT IT' : '❌ MISSED';
            $feasibility_color = $team['points'] >= 15 ? '#43b581' : '#f04747';
        } elseif ($analysis['ppg_needed_75'] <= 1.0) {
            $feasibility75 = '✅ LIKELY';
            $feasibility_color75 = '#43b581';
        } elseif ($analysis['ppg_needed_75'] <= 1.5) {
            $feasibility75 = '⚠️ TOUGH';
            $feasibility_color75 = '#faa61a';
        } elseif ($analysis['ppg_needed_75'] <= 2.0) {
            $feasibility75 = '🚨 HARD';
            $feasibility_color75 = '#f04747';
        } else {
            $feasibility75 = '🚨 UNLIKELY';
            $feasibility_color75 = '#f04747';
        }

        if ($analysis['points_to_20'] == 0) {
            $feasibility100 = '✅ PERFECT';
            $feasibility_color = '#43b581'; 
        } elseif ($analysis['games_to_halfway'] == 0) {
            $feasibility100 = $team['points'] >= 20 ? '✅ ACHIEVED' : '❌ FAILED';
            $feasibility_color = $team['points'] >= 20 ? '#43b581' : '#f04747';
        } elseif ($analysis['ppg_needed_100'] <= 1.0) {
            $feasibility100 = '✅ POSSIBLE';
            $feasibility_color100 = '#43b581';
        } elseif ($analysis['ppg_needed_100'] <= 1.5) {
            $feasibility100 = '⚠️ CHALLENGING';
            $feasibility_color100 = '#faa61a';
        } elseif ($analysis['ppg_needed_100'] <= 2.0) {
            $feasibility100 = '🚨 DIFFICULT';
            $feasibility_color100 = '#f04747';
        } else {
            $feasibility100 = '🚨 UNREALISTIC';
            $feasibility_color100 = '#f04747';
        }

    
        ?>
        <tr style="<?php echo $is_leeds ? 'background: rgba(29, 66, 138, 0.2);' : ''; ?>">
            <td>
                <?php if ($is_leeds): ?>
                    <strong style="color: #FFCD00;">🤍💛💙 <?php echo htmlspecialchars($team['team_name']); ?></strong>
                <?php else: ?>
                    <strong><?php echo htmlspecialchars($team['team_name']); ?></strong>
                <?php endif; ?>
            </td>
            <td><?php echo $team['points']; ?></td>
            <td><?php echo $analysis['games_to_halfway']; ?></td>
            <td style="font-weight: bold; color: <?php echo $analysis['points_to_15'] == 0 ? '#43b581' : '#faa61a'; ?>;">
                <?php echo $analysis['points_to_15']; ?> pts
            </td>
            <td style="font-weight: bold; color: <?php echo $analysis['points_to_16'] == 0 ? '#43b581' : '#faa61a'; ?>;">
                <?php echo $analysis['points_to_16']; ?> pts
            </td>
            <td style="font-weight: bold; color: <?php echo $analysis['points_to_17'] == 0 ? '#43b581' : '#faa61a'; ?>;">
                <?php echo $analysis['points_to_17']; ?> pts
            </td>
            <td style="font-weight: bold; color: <?php echo $analysis['points_to_18'] == 0 ? '#43b581' : '#f04747'; ?>;">
                <?php echo $analysis['points_to_18']; ?> pts
            </td>
            <td style="font-weight: bold; color: <?php echo $analysis['points_to_20'] == 0 ? '#43b581' : '#f04747'; ?>;">
                <?php echo $analysis['points_to_20']; ?> pts
            </td>
            <td><?php echo number_format($analysis['ppg_needed_75'], 2); ?></td>
            <td><?php echo number_format($analysis['ppg_needed_85'], 2); ?></td>
            <td><?php echo number_format($analysis['ppg_needed_90'], 2); ?></td>
            <td><?php echo number_format($analysis['ppg_needed_95'], 2); ?></td>
            <td><?php echo number_format($analysis['ppg_needed_100'], 2); ?></td>
            <td style="color: <?php echo $feasibility_color75; ?>; font-weight: bold;">
                <?php echo $feasibility75; ?>
            </td>
            <td style="color: <?php echo $feasibility_color100; ?>; font-weight: bold;">
                <?php echo $feasibility100; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
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
                <span style="color: #888; font-size: 13px;">Current Points:</span>
                <span style="color: white; font-weight: bold;"><?php echo $team['points']; ?> / 20</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span style="color: #888; font-size: 13px;">75% Progress:</span>
                <span style="color: <?php echo $analysis['progress_75_pct'] >= 100 ? '#43b581' : '#f04747'; ?>; font-weight: bold;">
                    <?php echo round($analysis['progress_75_pct']); ?>%
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span style="color: #888; font-size: 13px;">Projected Halfway:</span>
                <span style="color: <?php echo $analysis['projected_halfway'] >= 15 ? '#43b581' : '#f04747'; ?>; font-weight: bold;">
                    <?php echo round($analysis['projected_halfway'], 1); ?> pts
                </span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #888; font-size: 13px;">PPG Needed (75%):</span>
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

<!-- Teams definatly relegated -->
<?php
$definite_relegation = array_filter($teams_analysis, function($a) {
    return $a['projected_final'] < 38;  
})
;
if (count($definite_relegation) > 0): ?>
<div class="panel" style="background: linear-gradient(135deg, #f04747, #ff6b6b); border: 3px solid #FFFFFF;">
    <div style="text-align: center;">    
        <h2 style="color: white; font-size: 28px; margin: 0;">
            🚨 ALERT: <?php echo count($definite_relegation); ?> Teams Definitly Relegated
        </h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin-top: 10px;">
            These teams are projected to have less than 38 points at the end of the season
        </p>
        <div style="margin-top: 15px; display: flex; justify-content: center; flex-wrap: wrap; gap: 10px;">
            <?php foreach ($definite_relegation as $analysis): ?>
                <span style="background: rgba(0,0,0,0.3); padding: 8px 15px; border-radius: 6px; color: white; font-weight: bold;">
                    <?php echo htmlspecialchars($analysis['team']['team_name']); ?>
                    (<?php echo round($analysis['projected_final']); ?> pts projected)
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Risk Teams (Unlikely to Hit 75%) -->    
<?php
$risk_teams = array_filter($teams_analysis, function($a) use ($target_75pct) {
    return $a['projected_halfway'] < $target_75pct;
});
if (count($risk_teams) > 0): ?>
<div class="panel" style="background: linear-gradient(135deg, #f04747, #faa61a); border: 3px solid #FFFFFF;">
    <div style="text-align: center;">    
        <h2 style="color: white; font-size: 28px; margin: 0;">
            ⚠️ <?php echo count($risk_teams); ?> Teams At Risk of Missing 75% Rule
        </h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin-top: 10px;">
            These teams are projected to have less than 15 points at halfway
        </p>
        <div style="margin-top: 15px; display: flex; justify-content: center; flex-wrap: wrap; gap: 10px;">
            <?php foreach ($risk_teams as $analysis): ?>
                <span style="background: rgba(0,0,0,0.3); padding: 8px 15px; border-radius: 6px; color: white; font-weight: bold;">
                    <?php echo htmlspecialchars($analysis['team']['team_name']); ?>
                    (<?php echo round($analysis['projected_halfway']); ?> pts projected)
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Safe Teams (Likely to Hit 75%) -->
<?php if (count($safe_teams) > 0): ?>
<div class="panel" style="background: linear-gradient(135deg, #43b581, #57f287);">
    <div style="text-align: center;">
        <h2 style="color: white; font-size: 28px; margin: 0;">
            ✅ <?php echo count($safe_teams); ?> Teams On Track for 75% Rule
        </h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin-top: 10px;">
            These teams are projected to reach 15+ points at halfway
        </p>
        <div style="margin-top: 15px; display: flex; justify-content: center; flex-wrap: wrap; gap: 10px;">
            <?php foreach ($safe_teams as $analysis): ?>
                <?php $is_leeds = stripos($analysis['team']['team_name'], 'Leeds') !== false; ?>
                <span style="background: rgba(0,0,0,0.2); padding: 8px 15px; border-radius: 6px; color: white; font-weight: bold; <?php echo $is_leeds ? 'border: 2px solid #FFCD00;' : ''; ?>">
                    <?php if ($is_leeds): ?>🤍💛💙<?php endif; ?>
                    <?php echo htmlspecialchars($analysis['team']['team_name']); ?>
                    (<?php echo round($analysis['projected_halfway']); ?> pts projected)
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Safe Teams (Likely to Hit 85%) -->
<?php
$safe_85_teams = array_filter($teams_analysis, function($a) use ($target_85pct) {
    return $a['projected_halfway'] >= $target_85pct;
});

if (count($safe_85_teams) > 0): ?>
<div class="panel" style="background: linear-gradient(135deg, #57f287, #43b581);">
    <div style="text-align: center;">
        <h2 style="color: white; font-size: 28px; margin: 0;">
            🌟 <?php echo count($safe_85_teams); ?> Teams On Track for 85% Rule
        </h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin-top: 10px;">
            These teams are projected to reach 17+ points at halfway
        </p>
        <div style="margin-top: 15px; display: flex; justify-content: center; flex-wrap: wrap; gap: 10px;">
            <?php foreach ($safe_85_teams as $analysis): ?>
                <?php $is_leeds = stripos($analysis['team']['team_name'], 'Leeds') !== false; ?>
                <span style="background: rgba(0,0,0,0.2); padding: 8px 15px; border-radius: 6px; color: white; font-weight: bold; <?php echo $is_leeds ? 'border: 2px solid #FFCD00;' : ''; ?>">
                    <?php if ($is_leeds): ?>🤍💛💙<?php endif; ?>
                    <?php echo htmlspecialchars($analysis['team']['team_name']); ?>
                    (<?php echo round($analysis['projected_halfway']); ?> pts projected)
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Safe Teams (Likely to Hit 90%) -->
<?php
$safe_90_teams = array_filter($teams_analysis, function($a) use ($target_90pct) {
    return $a['projected_halfway'] >= $target_90pct;
})
;
if (count($safe_90_teams) > 0): ?>
<div class="panel" style="background: linear-gradient(135deg, #43b581, #57f287);">
    <div style="text-align: center;">
        <h2 style="color: white; font-size: 28px; margin: 0;">
            ✨ <?php echo count($safe_90_teams); ?> Teams On Track for 90% Rule
        </h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin-top: 10px;">
            These teams are projected to reach 20+ points at halfway
        </p>
        <div style="margin-top: 15px; display: flex; justify-content: center; flex-wrap: wrap; gap: 10px;">
            <?php foreach ($safe_90_teams as $analysis): ?>
                <?php $is_leeds = stripos($analysis['team']['team_name'], 'Leeds') !== false; ?>
                <span style="background: rgba(0,0,0,0.2); padding: 8px 15px; border-radius: 6px; color: white; font-weight: bold; <?php echo $is_leeds ? 'border: 2px solid #FFCD00;' : ''; ?>">
                    <?php if ($is_leeds): ?>🤍💛💙<?php endif; ?>
                    <?php echo htmlspecialchars($analysis['team']['team_name']); ?>
                    (<?php echo round($analysis['projected_halfway']); ?> pts projected)
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Safe Teams (Likely to Hit 100%) -->
<?php
$safe_100_teams = array_filter($teams_analysis, function($a) use ($target_100pct) {
    return $a['projected_halfway'] >= $target_100pct;
}); 
if (count($safe_100_teams) > 0): ?>
<div class="panel" style="background: linear-gradient(135deg, #57f287, #43b581);">
    <div style="text-align: center;">
        <h2 style="color: white; font-size: 28px; margin: 0;">
            🌈 <?php echo count($safe_100_teams); ?> Teams On Track for 100% Rule
        </h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin-top: 10px;">
            These teams are projected to reach 22+ points at halfway
        </p>
        <div style="margin-top: 15px; display: flex; justify-content: center; flex-wrap: wrap; gap: 10px;">
            <?php foreach ($safe_100_teams as $analysis): ?>
                <?php $is_leeds = stripos($analysis['team']['team_name'], 'Leeds') !== false; ?>
                <span style="background: rgba(0,0,0,0.2); padding: 8px 15px; border-radius: 6px; color: white; font-weight: bold; <?php echo $is_leeds ? 'border: 2px solid #FFCD00;' : ''; ?>">
                    <?php if ($is_leeds): ?>🤍💛💙<?php endif; ?>
                    <?php echo htmlspecialchars($analysis['team']['team_name']); ?>
                    (<?php echo round($analysis['projected_halfway']); ?> pts projected)
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Key Insights -->
<div class="panel" style="background: #2e3136; border-left: 4px solid #5865F2;">
    <h3>💡 Key Insights</h3>
    <ul style="color: #dcddde; line-height: 2;">
        <li><strong style="color: #FFCD00;">The 75% Rule:</strong> Teams with 15+ points at halfway have an 85-90% survival rate historically.</li>
        <li><strong style="color: #FFCD00;">Critical Threshold:</strong> Below 10 points at halfway typically leads to relegation (70%+ chance).</li>
        <li><strong style="color: #FFCD00;">PPG Analysis:</strong> Teams needing 1.5+ PPG to hit 75% are in serious danger - very hard to achieve!</li>
        <li><strong style="color: #FFCD00;">Six-Pointers:</strong> When rivals face each other, one MUST drop points. Leeds can gain ground without playing!</li>
        <li><strong style="color: #FFCD00;">Form Matters:</strong> Current PPG projects future performance - consistency is key to survival.</li>
        <?php if (count($most_at_risk) > 0): ?>
        <li style="color: #f04747;"><strong>⚠️ <?php echo count($most_at_risk); ?> teams currently projected to miss 75% target</strong> - they need significant improvement!</li>
        <?php endif; ?>
        <?php if ($leeds): ?>
        <li style="color: #FFCD00;"><strong>🤍💛💙 Leeds Status:</strong> <?php echo $leeds['position']; ?>th with <?php echo $leeds['points']; ?> points - 
        <?php 
        $leeds_75 = ($leeds['points'] / 15) * 100;
        if ($leeds_75 >= 100) {
            echo "HIT 75% RULE! On track for survival! ✅";
        } elseif ($leeds_75 >= 75) {
            echo "Close to 75% rule - need consistency! ⚠️";
        } else {
            echo "Below 75% rule - improvement needed! 🚨";
        }
        ?></li>
        <?php endif; ?>
    </ul>
</div>
