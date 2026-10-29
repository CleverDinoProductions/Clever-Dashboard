<?php
require_once __DIR__ . '/own-table.php';

/** Aggregate actual play dates across seasons, with each season's scoring rules. */
function football_stats_calendar_year(array $matches, int $year, array $rulesBySeason): array
{
    $included = []; $clubs = []; $points = [];
    foreach ($matches as $match) {
        $date = football_stats_own_table_date(substr((string)($match['match_date'] ?? ''), 0, 10));
        $season = $match['season_label'];
        $rules = $rulesBySeason[$season] ?? null;
        if (!$rules || $date === '' || substr($date, 0, 4) !== (string)$year
            || (int)$match['matchweek'] < 1 || (int)$match['matchweek'] > $rules['final_matchweek']
            || $match['home_goals'] === null || $match['away_goals'] === null) continue;
        $included[] = $match;
        foreach (['home_team','away_team'] as $side) $clubs[$match[$side]] = true;
        $home = (int)$match['home_goals']; $away = (int)$match['away_goals'];
        foreach ([[$match['home_team'],$home,$away],[$match['away_team'],$away,$home]] as [$club,$gf,$ga]) {
            $outcome = $gf > $ga ? 'win' : ($gf === $ga ? 'draw' : 'loss');
            $points[$club] = ($points[$club] ?? 0) + $rules['points'][$outcome];
        }
    }
    $data = football_stats_own_table($included, array_keys($clubs));
    foreach ($data['rows'] as &$row) {
        $row['points'] = $points[$row['club']];
        $row['ppg'] = $row['played'] ? (float)$row['points'] / $row['played'] : 0.0;
    }
    unset($row);
    usort($data['rows'], static fn($a,$b) => ($b['points'] <=> $a['points']) ?: ($b['gd'] <=> $a['gd']) ?: ($b['gf'] <=> $a['gf']) ?: strcasecmp($a['club'],$b['club']));
    return $data;
}
