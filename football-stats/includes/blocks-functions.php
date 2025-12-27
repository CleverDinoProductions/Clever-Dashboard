<?php
/**
 * Blocks of 4 Analysis Functions
 * Dynamic data processing and insights generation
 */

/**
 * Get teams in a specific block
 */
function getBlockTeams($db, $blockNumber) {
    $startPos = (($blockNumber - 1) * 4) + 1;
    $endPos = $blockNumber * 4;
    
    $stmt = $db->prepare("
        SELECT 
            team_name,
            position,
            played,
            won,
            drawn,
            lost,
            gf,
            ga,
            gd,
            points,
            ROUND(CAST(points AS REAL) / played, 2) as ppg,
            ROUND(CAST(gf AS REAL) / played, 2) as gpg,
            ROUND(CAST(ga AS REAL) / played, 2) as gapg,
            ROUND(CAST(won AS REAL) / played * 100, 1) as win_rate
        FROM league_table
        WHERE position BETWEEN :start AND :end
        ORDER BY position
    ");
    $stmt->execute(['start' => $startPos, 'end' => $endPos]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get block statistics
 */
function getBlockStats($db, $blockNumber) {
    $startPos = (($blockNumber - 1) * 4) + 1;
    $endPos = $blockNumber * 4;
    
    $stmt = $db->prepare("
        SELECT 
            AVG(points) as avg_points,
            AVG(CAST(points AS REAL) / played) as avg_ppg,
            SUM(gf) as total_goals,
            SUM(ga) as total_conceded,
            AVG(CAST(won AS REAL) / played * 100) as avg_win_rate,
            MAX(points) as highest_points,
            MIN(points) as lowest_points,
            (MAX(points) - MIN(points)) as point_spread
        FROM league_table
        WHERE position BETWEEN :start AND :end
    ");
    $stmt->execute(['start' => $startPos, 'end' => $endPos]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get xG data for teams in a block
 */
function getBlockXG($db, $blockNumber) {
    $startPos = (($blockNumber - 1) * 4) + 1;
    $endPos = $blockNumber * 4;
    
    $stmt = $db->prepare("
        SELECT 
            x.team_name,
            x.xg_for,
            x.xg_against,
            x.xg_diff,
            x.actual_gf,
            x.actual_ga,
            x.xg_overperformance,
            l.position,
            (x.actual_gf - x.xg_for) as finishing_diff,
            (x.xg_against - x.actual_ga) as defensive_diff
        FROM xg_table x
        JOIN league_table l ON x.team_name = l.team_name
        WHERE l.position BETWEEN :start AND :end
        ORDER BY l.position
    ");
    $stmt->execute(['start' => $startPos, 'end' => $endPos]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Calculate block goals and achievement status
 */
function getBlockGoalsStatus($db, $blockNumber, $currentMatchday = null) {
    $teams = getBlockTeams($db, $blockNumber);
    if (empty($teams)) return null;
    
    $avgPlayed = array_sum(array_column($teams, 'played')) / count($teams);
    $avgPoints = array_sum(array_column($teams, 'points')) / count($teams);
    $avgPPG = $avgPoints / $avgPlayed;
    
    // Define block-specific goals
    $blockGoals = [
        1 => [
            'title' => 'Title Contenders',
            'target_ppg' => 2.0,
            'target_points_38' => 76,
            'target_win_rate' => 65,
            'target_gd' => 30,
            'description' => 'Champions League qualification minimum'
        ],
        2 => [
            'title' => 'European Zone',
            'target_ppg' => 1.6,
            'target_points_38' => 61,
            'target_win_rate' => 50,
            'target_gd' => 15,
            'description' => 'Europa/Conference League qualification'
        ],
        3 => [
            'title' => 'Safe Mid-Table',
            'target_ppg' => 1.3,
            'target_points_38' => 49,
            'target_win_rate' => 40,
            'target_gd' => 0,
            'description' => 'Comfortable Premier League safety'
        ],
        4 => [
            'title' => 'Danger Zone',
            'target_ppg' => 1.0,
            'target_points_38' => 38,
            'target_win_rate' => 30,
            'target_gd' => -10,
            'description' => 'Relegation battle threshold'
        ],
        5 => [
            'title' => 'Relegation Battle',
            'target_ppg' => 0.9,
            'target_points_38' => 34,
            'target_win_rate' => 25,
            'target_gd' => -20,
            'description' => 'Desperate fight for survival'
        ]
    ];
    
    $goals = $blockGoals[$blockNumber];
    $avgWinRate = array_sum(array_column($teams, 'win_rate')) / count($teams);
    $avgGD = array_sum(array_column($teams, 'gd')) / count($teams);
    
    // Projected points at end of season
    $projectedPoints = round($avgPPG * 38, 0);
    
    // Calculate achievement status
    $achievements = [
        'ppg_met' => $avgPPG >= $goals['target_ppg'],
        'win_rate_met' => $avgWinRate >= $goals['target_win_rate'],
        'gd_met' => $avgGD >= $goals['target_gd'],
        'projection_met' => $projectedPoints >= $goals['target_points_38']
    ];
    
    $metCount = count(array_filter($achievements));
    $totalGoals = count($achievements);
    $overallStatus = ($metCount / $totalGoals) * 100;
    
    return [
        'goals' => $goals,
        'current' => [
            'avg_ppg' => round($avgPPG, 2),
            'avg_win_rate' => round($avgWinRate, 1),
            'avg_gd' => round($avgGD, 0),
            'projected_points' => $projectedPoints,
            'matches_played' => round($avgPlayed, 0)
        ],
        'achievements' => $achievements,
        'overall_status' => round($overallStatus, 0),
        'status_text' => getStatusText($overallStatus)
    ];
}

/**
 * Get status text based on achievement percentage
 */
function getStatusText($percentage) {
    if ($percentage >= 100) return 'All Goals Met ✅';
    if ($percentage >= 75) return 'On Track ✅';
    if ($percentage >= 50) return 'Partial Progress ⚠️';
    if ($percentage >= 25) return 'Struggling ⚠️';
    return 'Crisis Mode 🚨';
}

/**
 * Get real-time insights for a block
 */
function getBlockInsights($db, $blockNumber) {
    $teams = getBlockTeams($db, $blockNumber);
    $stats = getBlockStats($db, $blockNumber);
    $goalsStatus = getBlockGoalsStatus($db, $blockNumber);
    
    if (empty($teams)) return [];
    
    $insights = [];
    
    // Insight 1: Point spread analysis
    if ($stats['point_spread'] >= 5) {
        $insights[] = [
            'type' => 'warning',
            'icon' => '📊',
            'title' => 'Wide Point Spread',
            'message' => "Large {$stats['point_spread']}-point gap within block suggests movement between blocks likely"
        ];
    } elseif ($stats['point_spread'] <= 2) {
        $insights[] = [
            'type' => 'info',
            'icon' => '🔒',
            'title' => 'Tight Competition',
            'message' => "Only {$stats['point_spread']} points separate block - intense competition for positions"
        ];
    }
    
    // Insight 2: Form analysis
    $topTeam = $teams[0];
    $bottomTeam = end($teams);
    
    if ($topTeam['ppg'] >= 2.0 && $blockNumber > 1) {
        $insights[] = [
            'type' => 'success',
            'icon' => '⬆️',
            'title' => 'Promotion Candidate',
            'message' => "{$topTeam['team_name']} ({$topTeam['ppg']} PPG) showing form to climb to Block " . ($blockNumber - 1)
        ];
    }
    
    if ($bottomTeam['ppg'] < 1.0 && $blockNumber < 5) {
        $insights[] = [
            'type' => 'danger',
            'icon' => '⬇️',
            'title' => 'Relegation Risk',
            'message' => "{$bottomTeam['team_name']} ({$bottomTeam['ppg']} PPG) at risk of dropping to Block " . ($blockNumber + 1)
        ];
    }
    
    // Insight 3: Goals achievement
    if ($goalsStatus['overall_status'] >= 75) {
        $insights[] = [
            'type' => 'success',
            'icon' => '🎯',
            'title' => 'Goals On Track',
            'message' => "Block achieving {$goalsStatus['overall_status']}% of typical targets - {$goalsStatus['status_text']}"
        ];
    } elseif ($goalsStatus['overall_status'] < 50) {
        $insights[] = [
            'type' => 'warning',
            'icon' => '⚠️',
            'title' => 'Underperforming',
            'message' => "Only {$goalsStatus['overall_status']}% of typical targets met - {$goalsStatus['status_text']}"
        ];
    }
    
    // Insight 4: Historical context
    $avgMatchday = round(array_sum(array_column($teams, 'played')) / count($teams), 0);
    if ($avgMatchday >= 10) {
        $projectedGap = abs($goalsStatus['current']['projected_points'] - $goalsStatus['goals']['target_points_38']);
        if ($projectedGap <= 5) {
            $insights[] = [
                'type' => 'info',
                'icon' => '📈',
                'title' => 'Projection Accurate',
                'message' => "Current pace projects to {$goalsStatus['current']['projected_points']} points - within expected range"
            ];
        }
    }
    
    return $insights;
}

/**
 * Get block comparison data
 */
function getBlockComparison($db) {
    $comparison = [];
    for ($i = 1; $i <= 5; $i++) {
        $stats = getBlockStats($db, $i);
        $goalsStatus = getBlockGoalsStatus($db, $i);
        $comparison[$i] = [
            'block_number' => $i,
            'avg_ppg' => round($stats['avg_ppg'], 2),
            'avg_points' => round($stats['avg_points'], 1),
            'point_spread' => $stats['point_spread'],
            'goals_met' => $goalsStatus['overall_status'],
            'status' => $goalsStatus['status_text']
        ];
    }
    return $comparison;
}

/**
 * Get movement predictions
 */
function getMovementPredictions($db) {
    $allTeams = $db->query("
        SELECT 
            team_name,
            position,
            points,
            played,
            ROUND(CAST(points AS REAL) / played, 2) as ppg
        FROM league_table
        ORDER BY position
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $predictions = [];
    foreach ($allTeams as $team) {
        $currentBlock = ceil($team['position'] / 4);
        $projectedPoints = $team['ppg'] * 38;
        
        // Determine likely end block based on projection
        if ($projectedPoints >= 76) $projectedBlock = 1;
        elseif ($projectedPoints >= 61) $projectedBlock = 2;
        elseif ($projectedPoints >= 49) $projectedBlock = 3;
        elseif ($projectedPoints >= 38) $projectedBlock = 4;
        else $projectedBlock = 5;
        
        if ($currentBlock != $projectedBlock) {
            $predictions[] = [
                'team' => $team['team_name'],
                'current_block' => $currentBlock,
                'current_position' => $team['position'],
                'projected_block' => $projectedBlock,
                'direction' => $projectedBlock < $currentBlock ? 'up' : 'down',
                'ppg' => $team['ppg'],
                'projected_points' => round($projectedPoints, 0)
            ];
        }
    }
    
    return $predictions;
}

/**
 * Get block timeline - when blocks typically stabilize
 */
function getBlockTimeline($db) {
    $stmt = $db->query("SELECT AVG(played) as avg_matchday FROM league_table");
    $currentMatchday = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_matchday'], 0);
    
    $milestones = [
        ['matchday' => 10, 'description' => 'Early patterns emerge', 'status' => $currentMatchday >= 10],
        ['matchday' => 15, 'description' => 'Block positions begin stabilizing', 'status' => $currentMatchday >= 15],
        ['matchday' => 20, 'description' => 'Bottom blocks typically locked in', 'status' => $currentMatchday >= 20],
        ['matchday' => 25, 'description' => 'Mathematical realities clear', 'status' => $currentMatchday >= 25],
        ['matchday' => 30, 'description' => 'Final block positions set', 'status' => $currentMatchday >= 30],
    ];
    
    return [
        'current_matchday' => $currentMatchday,
        'milestones' => $milestones,
        'stabilization_level' => getStabilizationLevel($currentMatchday)
    ];
}

function getStabilizationLevel($matchday) {
    if ($matchday < 10) return ['level' => 'early', 'text' => 'Early Season - High Volatility', 'color' => '#72767d'];
    if ($matchday < 20) return ['level' => 'forming', 'text' => 'Patterns Forming', 'color' => '#faa61a'];
    if ($matchday < 30) return ['level' => 'stable', 'text' => 'Blocks Stabilizing', 'color' => '#5865F2'];
    return ['level' => 'locked', 'text' => 'Blocks Locked In', 'color' => '#43b581'];
}
?>