<?php

/** Derive every comparison from the same selected standings, including deductions. */
function football_stats_tracker_analysis(array $team, array $standings, array $rules, array $scoring): array
{
    $played = max(0, (int)$team['played']);
    $total = max(1, (int)$rules['total_games']);
    $remaining = max(0, $total - $played);
    $points = (int)$team['points'];
    $position = (int)$team['position'];
    $byPosition = [];
    $goals = $leagueGames = 0;
    foreach ($standings as $row) {
        $byPosition[(int)$row['position']] = $row;
        $goals += (int)$row['gf'];
        $leagueGames += (int)$row['played'];
    }
    ksort($byPosition);
    $zoneLabel = 'Outside configured zones';
    $boundaries = [];
    foreach ($rules['zones'] as $zone) {
        if ($position >= (int)$zone['from'] && $position <= (int)$zone['to']) $zoneLabel = $zone['label'];
        // For relegation compare to the other side of the line; for positive
        // zones compare to the last qualifying position (or the first chaser).
        $inside = $position >= (int)$zone['from'] && $position <= (int)$zone['to'];
        $rank = $zone['key'] === 'relegation'
            ? ($inside ? (int)$zone['from'] - 1 : (int)$zone['from'])
            : ($inside ? (int)$zone['to'] + 1 : (int)$zone['to']);
        $rival = $byPosition[$rank] ?? null;
        $boundaries[] = ['zone' => $zone, 'rival' => $rival,
            'gap' => $rival ? $points - (int)$rival['points'] : null,
            'games_in_hand' => $rival ? (int)$rival['played'] - $played : null];
    }
    $earned = (int)$team['won'] * $scoring['win'] + (int)$team['drawn'] * $scoring['draw'] + (int)$team['lost'] * $scoring['loss'];
    return ['played' => $played, 'total' => $total, 'remaining' => $remaining,
        'pace' => $played ? $points / $played : null,
        'zone' => $zoneLabel, 'boundaries' => $boundaries,
        'nearby' => array_values(array_filter($byPosition, static function ($row) use ($position) { return abs((int)$row['position'] - $position) <= 2; })),
        'bottom' => array_slice(array_values($byPosition), -6),
        'league_goal_rate' => $leagueGames ? $goals / $leagueGames : null,
        'adjustment' => $points - $earned,
        'max_result_points' => max($scoring), 'min_result_points' => min($scoring)];
}

/** Every W/D/L combination for a short horizon, with no invented fixtures. */
function football_stats_tracker_scenarios(int $games, array $scoring): array
{
    $rows = [];
    for ($wins = $games; $wins >= 0; $wins--) {
        for ($draws = $games - $wins; $draws >= 0; $draws--) {
            $losses = $games - $wins - $draws;
            $rows[] = ['wins' => $wins, 'draws' => $draws, 'losses' => $losses,
                'points' => $wins * $scoring['win'] + $draws * $scoring['draw'] + $losses * $scoring['loss']];
        }
    }
    usort($rows, static function ($a, $b) { return $b['points'] <=> $a['points']; });
    return $rows;
}

/** Conservative safety check: ties count as rivals still able to catch the team. */
function football_stats_tracker_safety(array $team, array $standings, array $rules, array $scoring): ?bool
{
    $from = null;
    foreach ($rules['zones'] as $zone) if ($zone['key'] === 'relegation') $from = (int)$zone['from'];
    if ($from === null || count($standings) !== (int)$rules['team_count']) return null;
    $names = array_unique(array_column($standings, 'team_name'));
    if (count($names) !== (int)$rules['team_count']) return null;
    $below = 0;
    $minimum = (int)$team['points'] + max(0, (int)$rules['total_games'] - (int)$team['played']) * min($scoring);
    foreach ($standings as $row) {
        if ($row['team_name'] === $team['team_name']) continue;
        $maximum = (int)$row['points'] + max(0, (int)$rules['total_games'] - (int)$row['played']) * max($scoring);
        if ($minimum > $maximum) $below++;
    }
    return $below >= (int)$rules['team_count'] - $from + 1;
}
