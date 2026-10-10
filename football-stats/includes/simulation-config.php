<?php
require_once __DIR__ . '/table-view.php';

/** Use the same Admin league/season rules as the standings tables. */
function football_stats_simulation_config(PDO $db, string $code, string $season, string $name): array
{
    $rules = football_stats_get_competition_rules($code, $season, $db);
    $zones = [];
    $columns = [['label' => 'P(🏆 Title)', 'from' => 1, 'to' => 1, 'color' => '#FFD700']];
    $emojis = ['champions-league' => '🌍', 'europa-league' => '⚽', 'conference-league' => '🏅',
        'automatic-promotion' => '⬆️', 'playoffs' => '🎯', 'relegation' => '⬇️'];
    $occupied = [];
    foreach ($rules['zones'] as $zone) {
        [$color, $bg] = football_stats_table_zone_colors($zone);
        $zones[] = ['key' => $zone['key'], 'name' => $zone['label'], 'emoji' => $emojis[$zone['key']] ?? '',
            'from' => $zone['from'], 'to' => $zone['to'], 'color' => $color, 'bg' => $bg];
        $columns[] = ['label' => 'P(' . $zone['label'] . ')', 'from' => $zone['from'], 'to' => $zone['to'], 'color' => $color];
        for ($position = $zone['from']; $position <= $zone['to']; $position++) $occupied[$position] = true;
    }
    // Include unassigned places once so distribution bars always total 100%.
    for ($position = 1; $position <= $rules['team_count']; $position++) {
        if (isset($occupied[$position])) continue;
        $from = $position;
        while ($position < $rules['team_count'] && !isset($occupied[$position + 1])) $position++;
        $zones[] = ['key' => 'unassigned', 'name' => 'Unassigned', 'emoji' => '', 'from' => $from,
            'to' => $position, 'color' => '#555555', 'bg' => 'transparent'];
    }
    usort($zones, static fn($a, $b) => $a['from'] <=> $b['from']);
    return ['comp_code' => $code, 'season_label' => $season, 'league_name' => $name,
        'total_games' => $rules['total_games'], 'halfway_games' => $rules['halfway_games'],
        'n_teams' => $rules['team_count'], 'zones' => $zones, 'prob_columns' => $columns];
}
