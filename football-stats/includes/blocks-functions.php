<?php
/**
 * Blocks of 4 Analysis - Shared Functions
 * Real-time insights and dynamic data processing
 */

/**
 * Get teams for a specific block with all statistics
 */
function getBlockTeams($pdo, $startPos, $endPos) {
    try {
        $stmt = $pdo->prepare("
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
                ROUND(CAST(points AS FLOAT) / NULLIF(played, 0), 2) as ppg,
                ROUND(CAST(won AS FLOAT) / NULLIF(played, 0) * 100, 1) as win_rate,
                ROUND(CAST(gf AS FLOAT) / NULLIF(played, 0), 2) as goals_per_game,
                ROUND(CAST(ga AS FLOAT) / NULLIF(played, 0), 2) as conceded_per_game
            FROM league_table 
            WHERE position BETWEEN ? AND ?
            ORDER BY position ASC
        ");
        $stmt->execute([$startPos, $endPos]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get block statistics and insights
 */
function getBlockStats($teams) {
    if (empty($teams)) {
        return [
            'avg_points' => 0,
            'avg_ppg' => 0,
            'avg_goals' => 0,
            'total_wins' => 0,
            'best_team' => 'N/A',
            'worst_team' => 'N/A'
        ];
    }
    
    $totalPoints = array_sum(array_column($teams, 'points'));
    $totalGoals = array_sum(array_column($teams, 'gf'));
    $totalWins = array_sum(array_column($teams, 'won'));
    $avgPPG = array_sum(array_column($teams, 'ppg')) / count($teams);
    
    return [
        'avg_points' => round($totalPoints / count($teams), 1),
        'avg_ppg' => round($avgPPG, 2),
        'avg_goals' => round($totalGoals / count($teams), 1),
        'total_wins' => $totalWins,
        'best_team' => $teams[0]['team_name'] ?? 'N/A',
        'worst_team' => end($teams)['team_name'] ?? 'N/A'
    ];
}

/**
 * Check if block goals are being met
 */
function checkBlockGoals($blockNumber, $teams, $matchday) {
    if (empty($teams)) return [];
    
    $insights = [];
    
    switch($blockNumber) {
        case 1: // Title Contenders
            $titlePace = ($teams[0]['points'] ?? 0) / max($matchday, 1) * 38;
            $ucl_pace = ($teams[3]['points'] ?? 0) / max($matchday, 1) * 38;
            
            $insights[] = [
                'goal' => 'Win Premier League',
                'target' => '85-95 points',
                'current_pace' => round($titlePace, 1) . ' points',
                'status' => $titlePace >= 85 ? 'on_track' : 'below_pace',
                'leader' => $teams[0]['team_name'] ?? 'N/A'
            ];
            
            $insights[] = [
                'goal' => 'Secure Champions League (4th place)',
                'target' => '70-75 points minimum',
                'current_pace' => round($ucl_pace, 1) . ' points',
                'status' => $ucl_pace >= 70 ? 'on_track' : 'below_pace',
                'team' => $teams[3]['team_name'] ?? 'N/A'
            ];
            break;
            
        case 2: // European Zone
            $europa_pace = ($teams[0]['points'] ?? 0) / max($matchday, 1) * 38;
            $avgPPG = array_sum(array_column($teams, 'ppg')) / count($teams);
            
            $insights[] = [
                'goal' => 'Secure Europa League qualification',
                'target' => '60-65 points',
                'current_pace' => round($europa_pace, 1) . ' points',
                'status' => $europa_pace >= 60 ? 'on_track' : 'below_pace',
                'leader' => $teams[0]['team_name'] ?? 'N/A'
            ];
            
            $insights[] = [
                'goal' => 'Maintain 1.5+ PPG for European spots',
                'target' => '1.50 PPG',
                'current_pace' => round($avgPPG, 2) . ' PPG',
                'status' => $avgPPG >= 1.5 ? 'on_track' : 'below_pace',
                'context' => 'Block average'
            ];
            break;
            
        case 3: // Safe Mid-Table
            $safetyBuffer = ($teams[0]['points'] ?? 0) - 40; // Typical relegation is ~35-40 pts
            $avgPPG = array_sum(array_column($teams, 'ppg')) / count($teams);
            $projectedPoints = $avgPPG * 38;
            
            $insights[] = [
                'goal' => 'Achieve Premier League safety',
                'target' => '40+ points (safety threshold)',
                'current_pace' => round($projectedPoints, 1) . ' points projected',
                'status' => $projectedPoints >= 40 ? 'safe' : 'risk',
                'buffer' => $safetyBuffer > 0 ? "+$safetyBuffer pts" : "Need " . abs($safetyBuffer) . " more"
            ];
            
            $insights[] = [
                'goal' => 'Maintain 1.0+ PPG for comfortable safety',
                'target' => '1.00 PPG minimum',
                'current_pace' => round($avgPPG, 2) . ' PPG',
                'status' => $avgPPG >= 1.0 ? 'safe' : 'risk',
                'context' => 'Block 3 security zone'
            ];
            break;
            
        case 4: // Danger Zone
            $escapePoints = ($teams[0]['points'] ?? 0);
            $avgPPG = array_sum(array_column($teams, 'ppg')) / count($teams);
            $projectedPoints = $avgPPG * 38;
            
            $insights[] = [
                'goal' => 'Escape relegation zone',
                'target' => 'Reach 40 points',
                'current_pace' => round($projectedPoints, 1) . ' points projected',
                'status' => $projectedPoints >= 40 ? 'escaping' : 'danger',
                'urgency' => $projectedPoints < 35 ? 'CRITICAL' : 'URGENT'
            ];
            
            $insights[] = [
                'goal' => 'Improve to 1.05+ PPG for survival',
                'target' => '1.05 PPG survival rate',
                'current_pace' => round($avgPPG, 2) . ' PPG',
                'status' => $avgPPG >= 1.05 ? 'improving' : 'danger',
                'context' => 'Historical survival minimum'
            ];
            break;
            
        case 5: // Relegation Battle
            $bottomPoints = end($teams)['points'] ?? 0;
            $avgPPG = array_sum(array_column($teams, 'ppg')) / count($teams);
            $projectedPoints = $avgPPG * 38;
            $pointsNeeded = max(0, 35 - ($teams[0]['points'] ?? 0));
            $gamesLeft = 38 - ($teams[0]['played'] ?? 0);
            $requiredPPG = $gamesLeft > 0 ? $pointsNeeded / $gamesLeft : 0;
            
            $insights[] = [
                'goal' => 'Avoid automatic relegation',
                'target' => '35-40 points minimum',
                'current_pace' => round($projectedPoints, 1) . ' points projected',
                'status' => $projectedPoints >= 35 ? 'fighting' : 'critical',
                'urgency' => 'RELEGATION THREAT'
            ];
            
            $insights[] = [
                'goal' => 'Achieve required points per game',
                'target' => round($requiredPPG, 2) . ' PPG needed',
                'current_pace' => round($avgPPG, 2) . ' PPG current',
                'status' => $avgPPG >= $requiredPPG ? 'possible' : 'critical',
                'context' => "$pointsNeeded points needed from $gamesLeft games"
            ];
            break;
    }
    
    return $insights;
}

/**
 * Get movement analysis for teams
 */
function getBlockMovement($pdo, $blockNumber) {
    // This would track historical position changes
    // For now, return structure for future implementation
    return [
        'teams_entering' => [],
        'teams_leaving' => [],
        'stable_teams' => []
    ];
}

/**
 * Get current matchday
 */
function getCurrentMatchday($pdo) {
    try {
        $stmt = $pdo->query("SELECT MAX(played) as matchday FROM league_table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['matchday'] ?? 1;
    } catch (Exception $e) {
        return 1;
    }
}

/**
 * Get block color theme
 */
function getBlockColor($blockNumber) {
    $colors = [
        1 => ['primary' => '#FFD700', 'rgba' => 'rgba(255, 215, 0, 0.1)', 'name' => 'Gold'],
        2 => ['primary' => '#4CAF50', 'rgba' => 'rgba(76, 175, 80, 0.1)', 'name' => 'Green'],
        3 => ['primary' => '#2196F3', 'rgba' => 'rgba(33, 150, 243, 0.1)', 'name' => 'Blue'],
        4 => ['primary' => '#FF9800', 'rgba' => 'rgba(255, 152, 0, 0.1)', 'name' => 'Orange'],
        5 => ['primary' => '#F44336', 'rgba' => 'rgba(244, 67, 54, 0.1)', 'name' => 'Red']
    ];
    return $colors[$blockNumber] ?? $colors[3];
}

/**
 * Get block title and emoji
 */
function getBlockInfo($blockNumber) {
    $info = [
        1 => ['title' => 'Title Contenders', 'emoji' => '🏆', 'positions' => '1-4'],
        2 => ['title' => 'European Zone', 'emoji' => '🌟', 'positions' => '5-8'],
        3 => ['title' => 'Safe Mid-Table', 'emoji' => '✅', 'positions' => '9-12'],
        4 => ['title' => 'Danger Zone', 'emoji' => '⚠️', 'positions' => '13-16'],
        5 => ['title' => 'Relegation Battle', 'emoji' => '🔻', 'positions' => '17-20']
    ];
    return $info[$blockNumber] ?? $info[3];
}

/**
 * Format status badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'on_track' => '<span class="status-badge success">✓ On Track</span>',
        'safe' => '<span class="status-badge success">✓ Safe</span>',
        'below_pace' => '<span class="status-badge warning">⚠ Below Pace</span>',
        'risk' => '<span class="status-badge warning">⚠ At Risk</span>',
        'danger' => '<span class="status-badge danger">✗ Danger</span>',
        'critical' => '<span class="status-badge critical">🚨 Critical</span>',
        'escaping' => '<span class="status-badge warning">↗ Escaping</span>',
        'improving' => '<span class="status-badge success">↗ Improving</span>',
        'fighting' => '<span class="status-badge warning">⚔️ Fighting</span>',
        'possible' => '<span class="status-badge warning">? Possible</span>'
    ];
    return $badges[$status] ?? '<span class="status-badge neutral">—</span>';
}

/**
 * Get last updated timestamp
 */
function getLastUpdated($pdo) {
    try {
        $stmt = $pdo->query("SELECT MAX(updated_at) as last_update FROM league_table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && $result['last_update']) {
            return date('Y-m-d H:i:s', $result['last_update']);
        }
    } catch (Exception $e) {
        // Ignore
    }
    return date('Y-m-d H:i:s');
}
?>