<?php
require_once __DIR__ . '/table-view.php';

/** Use the same Admin league/season rules as the standings tables. */
function football_stats_simulation_config(PDO $db, string $code, string $season, string $name): array
{
    $rules = football_stats_get_competition_rules($code, $season, $db);
    $zones = [];
    $columns = [['label' => 'P(🏆 Title)', 'from' => 1, 'to' => 1, 'color' => '#FFD700']];
    $emojis = ['title' => '🏆', 'champions-league' => '🌍', 'europa-league' => '⚽', 'conference-league' => '🏅',
        'automatic-promotion' => '⬆️', 'playoffs' => '🎯', 'relegation' => '⬇️'];
    foreach ($rules['zones'] as $zone) {
        [$color, $bg] = football_stats_table_zone_colors($zone);
        $zones[] = ['key' => $zone['key'], 'name' => $zone['label'], 'emoji' => $emojis[$zone['key']] ?? '',
            'from' => $zone['from'], 'to' => $zone['to'], 'color' => $color, 'bg' => $bg];
        if ($zone['key'] === 'title') $columns[0] = ['label' => 'P(' . $zone['label'] . ')', 'from' => $zone['from'], 'to' => $zone['to'], 'color' => $color];
        else $columns[] = ['label' => 'P(' . $zone['label'] . ')', 'from' => $zone['from'], 'to' => $zone['to'], 'color' => $color];
    }
    $targetZones = $zones;
    $displayZones = [];
    for ($position = 1; $position <= $rules['team_count']; $position++) {
        $selected = clever_position_display_zone($rules['zones'], $position);
        $zone = $selected ?? ['key'=>'unassigned', 'label'=>'Unassigned', 'color'=>'#555555'];
        [$color, $bg] = football_stats_table_zone_colors($zone);
        [$edgeColor] = football_stats_table_zone_colors(clever_position_display_zone($rules['zones'], $position, 'edge'));
        $members = array_values(array_filter($targetZones, static fn($candidate) => $position >= $candidate['from'] && $position <= $candidate['to']));
        $name = $members ? implode(' + ', array_column($members, 'name')) : 'Unassigned';
        $last = count($displayZones) - 1;
        if ($last >= 0 && $displayZones[$last]['source'] === $selected && $displayZones[$last]['members'] === $members) {
            $displayZones[$last]['to'] = $position;
        } else {
            $displayZones[] = ['source'=>$selected, 'members'=>$members, 'key'=>$zone['key'], 'name'=>$name,
                'emoji'=>$emojis[$zone['key']] ?? '', 'from'=>$position, 'to'=>$position,
                'color'=>$color, 'edge_color'=>$edgeColor, 'bg'=>$selected === null ? 'transparent' : $bg];
        }
    }
    // Region probabilities retain full overlapping ranges. Only the bar uses disjoint segments.
    $regionZones = $targetZones;
    foreach ($displayZones as $zone) {
        if ($zone['key'] === 'unassigned') $regionZones[] = $zone;
    }
    $zones = $displayZones;
    usort($zones, static fn($a, $b) => $a['from'] <=> $b['from']);
    return ['comp_code' => $code, 'season_label' => $season, 'league_name' => $name,
        'total_games' => $rules['total_games'], 'halfway_games' => $rules['halfway_games'],
        'n_teams' => $rules['team_count'], 'zones' => $zones, 'target_zones' => $targetZones, 'region_zones' => $regionZones, 'prob_columns' => $columns];
}
