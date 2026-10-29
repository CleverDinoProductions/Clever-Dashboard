<?php
/** A mini league counts only scored fixtures with both clubs selected. */
function football_stats_own_table(array $matches, array $clubs, string $from = '', string $to = '', array $points = ['win'=>3,'draw'=>1,'loss'=>0]): array
{
    $rows = [];
    foreach ($clubs as $club) $rows[$club] = ['club'=>$club,'played'=>0,'won'=>0,'drawn'=>0,'lost'=>0,'gf'=>0,'ga'=>0,'gd'=>0,'points'=>0,'ppg'=>0.0];
    $counted = [];
    foreach ($matches as $match) {
        $home = $match['home_team']; $away = $match['away_team'];
        if (!isset($rows[$home], $rows[$away]) || $home === $away || $match['home_goals'] === null || $match['away_goals'] === null) continue;
        $date = substr((string)($match['match_date'] ?? ''), 0, 10);
        if (($from !== '' && ($date === '' || $date < $from)) || ($to !== '' && ($date === '' || $date > $to))) continue;
        $counted[] = $match;
        foreach ([[$home, (int)$match['home_goals'], (int)$match['away_goals']], [$away, (int)$match['away_goals'], (int)$match['home_goals']]] as [$club,$gf,$ga]) {
            $row = &$rows[$club];
            $row['played']++; $row['gf'] += $gf; $row['ga'] += $ga; $row['gd'] += $gf - $ga;
            $outcome = $gf > $ga ? 'win' : ($gf === $ga ? 'draw' : 'loss');
            $row[['win'=>'won','draw'=>'drawn','loss'=>'lost'][$outcome]]++;
            $row['points'] += $points[$outcome];
            $row['ppg'] = (float)$row['points'] / $row['played'];
            unset($row);
        }
    }
    $rows = array_values($rows);
    usort($rows, static fn($a,$b) => ($b['points'] <=> $a['points']) ?: ($b['gd'] <=> $a['gd']) ?: ($b['gf'] <=> $a['gf']) ?: strcasecmp($a['club'],$b['club']));
    return ['rows'=>$rows, 'matches'=>$counted];
}

function football_stats_own_table_date($value): string
{
    if (!is_string($value)) return '';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : '';
}
