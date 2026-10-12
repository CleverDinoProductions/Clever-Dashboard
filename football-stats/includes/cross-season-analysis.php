<?php
require_once __DIR__ . '/matches-team-filters.php';

/** Reconstruct pre-fixture standings in play-date order, never using the target result. */
function football_stats_cross_season_analysis(array $matches, string $team, array $options, array $groups): array
{
    $normalize = 'football_stats_normalize_group_team';
    $stats = [];
    foreach ($matches as $m) foreach (['home_team', 'away_team'] as $side) {
        $name = $normalize($m[$side]);
        $stats[$name] ??= ['points'=>0, 'gd'=>0, 'gf'=>0, 'played'=>0];
    }
    $dateKey = static function ($m) {
        $raw = $m['match_timestamp'] ?: $m['match_date'];
        try { return $raw ? (new DateTimeImmutable($raw, new DateTimeZone('UTC')))->getTimestamp() : null; }
        catch (Exception $e) { return null; }
    };
    // If any fixture lacks a kickoff time on a day, use standings before that day for all fixtures that day.
    $impreciseDays = [];
    foreach ($matches as $m) if (empty($m['match_timestamp']) && !empty($m['match_date'])) $impreciseDays[substr($m['match_date'], 0, 10)] = true;
    $time = static function ($m) use ($dateKey, $impreciseDays) {
        $key = $dateKey($m);
        if ($key !== null && isset($impreciseDays[gmdate('Y-m-d', $key)])) return strtotime(gmdate('Y-m-d', $key) . ' UTC');
        return $key;
    };
    usort($matches, static fn($a,$b) => ($time($a) ?? PHP_INT_MAX) <=> ($time($b) ?? PHP_INT_MAX));
    $batches = [];
    foreach ($matches as $m) $batches[$time($m) ?? 'unknown'][] = $m;
    $rows = []; $summary = []; $pending = [];
    foreach ($batches as $key => $batch) {
        foreach ($pending as $index => $finished) {
            if ($key === 'unknown' || $finished['available'] > (int)$key) continue;
            $m = $finished['match'];
            $home=$normalize($m['home_team']); $away=$normalize($m['away_team']); $gf=(int)$m['home_goals']; $ga=(int)$m['away_goals'];
            foreach ([[$home,$gf,$ga],[$away,$ga,$gf]] as [$name,$for,$against]) {
                $stats[$name]['played']++; $stats[$name]['gf']+=$for; $stats[$name]['gd']+=$for-$against; $stats[$name]['points']+=$for>$against?3:($for===$against?1:0);
            }
            unset($pending[$index]);
        }
        $ranked = $stats;
        uksort($ranked, static function ($a,$b) use ($stats) {
            return ($stats[$b]['points'] <=> $stats[$a]['points']) ?: ($stats[$b]['gd'] <=> $stats[$a]['gd']) ?: ($stats[$b]['gf'] <=> $stats[$a]['gf']) ?: strcmp($a,$b);
        });
        $names = array_keys($ranked); $half = (int)floor(count($names)/2);
        $hasResults = array_sum(array_column($stats,'played')) > 0;
        foreach ($batch as $m) {
            if ($m['home_goals'] === null || $m['away_goals'] === null) continue;
            $home = $normalize($m['home_team']); $away = $normalize($m['away_team']);
            $isHome = $home === $normalize($team);
            if (!$isHome && $away !== $normalize($team)) continue;
            $opponent = $isHome ? $away : $home;
            $position = array_search($opponent,$names,true);
            $bucket = !$hasResults || $key === 'unknown' ? 'unclassified' : ($position < $half ? 'top-half' : 'bottom-half');
            // A tie spanning the halfway boundary has no objective half assignment.
            if ($bucket !== 'unclassified' && $half > 0 && $half < count($names)) {
                $left=$stats[$names[$half-1]]; $right=$stats[$names[$half]];
                $opp=$stats[$opponent];
                if ($left['points']===$right['points'] && $left['gd']===$right['gd'] && $left['gf']===$right['gf'] && $opp['points']===$left['points'] && $opp['gd']===$left['gd'] && $opp['gf']===$left['gf']) $bucket='unclassified';
            }
            $gf=(int)($isHome ? $m['home_goals'] : $m['away_goals']); $ga=(int)($isHome ? $m['away_goals'] : $m['home_goals']);
            $actualResult=$gf>$ga?'win':($gf===$ga?'draw':'loss');
            $result=$options['outcomes'][$m['id']] ?? $actualResult;
            if ($result!==$actualResult) { if($result==='draw') $gf=$ga=0; elseif($result==='win') { $gf=1;$ga=0; } else { $gf=0;$ga=1; } }
            $excluded=in_array((int)$m['id'],$options['excluded']??[],true);
            $venue=$isHome?'home':'away';
            if (($options['venue'] ?? 'all') !== 'all' && $options['venue'] !== $venue) continue;
            if (($options['result'] ?? 'all') !== 'all' && $options['result'] !== $result) continue;
            if ((int)$m['matchweek'] < $options['start'] || (int)$m['matchweek'] > $options['end']) continue;
            $group=$options['opponent_group'];
            if ($group !== 'all') {
                if ($group === 'current-top-half' || $group === 'current-bottom-half') {
                    if ($bucket !== ($group==='current-top-half'?'top-half':'bottom-half')) continue;
                } else {
                    $outside=str_starts_with($group,'outside-'); $groupKey=str_replace('-','_',$outside?substr($group,8):$group);
                    $member=in_array($opponent,array_map($normalize,$groups[$groupKey] ?? []),true);
                    if ($outside ? $member : !$member) continue;
                }
            }
            $points=$options['points'][$result];
            if (!$excluded) {
            $summary[$bucket] ??= ['played'=>0,'win'=>0,'draw'=>0,'loss'=>0,'gf'=>0,'ga'=>0,'points'=>0];
            $summary[$bucket]['played']++; $summary[$bucket][$result]++; $summary[$bucket]['gf']+=$gf; $summary[$bucket]['ga']+=$ga; $summary[$bucket]['points']+=$points;
            }
            $rows[]=array_merge($m,['opponent'=>$isHome?$m['away_team']:$m['home_team'],'half'=>$bucket,'position'=>$bucket==='unclassified'?null:$position+1,'venue'=>$venue,'result'=>$result,'points'=>$points,'excluded'=>$excluded]);
        }
        if ($key === 'unknown') continue;
        foreach ($batch as $m) {
            if ($m['home_goals']===null || $m['away_goals']===null) continue;
            $available = (int)$key + (isset($impreciseDays[gmdate('Y-m-d', (int)$key)]) ? 86400 : 7200);
            $pending[] = ['available'=>$available, 'match'=>$m];
        }
    }
    return ['summary'=>$summary,'matches'=>$rows];
}
