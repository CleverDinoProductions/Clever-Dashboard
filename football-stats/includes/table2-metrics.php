<?php

/** Keep full precision for forecasts; round only the displayed result. */
function football_stats_table2_metrics(array $team, int $totalGames, array $scoring, array $targets): array
{
    $played = max(0, (int)$team['played']);
    $points = (float)$team['points'];
    $remaining = max(0, $totalGames - $played);
    $ppg = $played > 0 ? $points / $played : null;
    $targetMetrics = [];
    foreach ($targets as $target) {
        $needed = max(0, $target - $points);
        $targetMetrics[] = [
            'needed' => $needed,
            'ppg' => $remaining > 0 ? $needed / $remaining : null,
            'status' => $needed == 0 ? 'Reached' : ($needed > $remaining * max($scoring) ? 'Out of reach' : 'In reach'),
        ];
    }
    return [
        'remaining' => $remaining,
        'ppg' => $ppg,
        'buffer' => $points - $played,
        'projection' => $remaining === 0 ? $points : ($ppg === null ? null : round($points + $ppg * $remaining)),
        'wins' => $points + $remaining * $scoring['win'],
        'draws' => $points + $remaining * $scoring['draw'],
        'mixed' => round($points + $remaining * ($scoring['win'] + $scoring['draw']) / 2),
        'targets' => $targetMetrics,
    ];
}
