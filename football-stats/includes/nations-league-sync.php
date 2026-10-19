<?php
require_once __DIR__ . '/nations-league.php';

function football_stats_nations_team(string $name): string
{
    $name = trim($name);
    return [
        'Czechia'=>'Czech Republic', 'Türkiye'=>'Turkey', 'Turkiye'=>'Turkey',
        'Republic of Ireland'=>'Ireland', 'Rep. of Ireland'=>'Ireland',
        'N.Ireland'=>'Northern Ireland', 'N. Ireland'=>'Northern Ireland',
        'Bosnia and Herzegovina'=>'Bosnia-Herzegovina', 'Bosnia & Herzegovina'=>'Bosnia-Herzegovina',
        'Bosnia Herzegovina'=>'Bosnia-Herzegovina', 'FYR Macedonia'=>'North Macedonia', 'Macedonia'=>'North Macedonia',
    ][$name] ?? $name;
}

function football_stats_nations_groups(string $edition): array
{
    static $groups;
    $groups ??= json_decode(file_get_contents(__DIR__.'/../tabs/nations-league/groups.json'), true, 512, JSON_THROW_ON_ERROR);
    return $groups[$edition] ?? [];
}

/** Make requests without exposing the key-bearing URL in errors or logs. */
function football_stats_sportsdb_request(string $key, string $endpoint, array $params): ?array
{
    $url = 'https://www.thesportsdb.com/api/v1/json/'.rawurlencode($key).'/'.$endpoint.'?'.http_build_query($params);
    $context = stream_context_create(['http'=>['timeout'=>25, 'header'=>"Accept: application/json\r\nUser-Agent: Clever-Dashboard/1.0\r\n"]]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) return null;
    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

function football_stats_nations_score($value): ?int
{
    return is_scalar($value) && preg_match('/^\d+$/', (string)$value) ? (int)$value : null;
}

/** Classify using this edition's draw, never the current domestic league tables. */
function football_stats_nations_event(array $event, string $edition, array $groups): ?array
{
    if (isset($event['idLeague']) && (string)$event['idLeague'] !== '4490') return null;
    if (!empty($event['strSeason']) && $event['strSeason'] !== $edition) return null;
    if (empty($event['idEvent']) || empty($event['strHomeTeam']) || empty($event['strAwayTeam'])) return null;
    $home = football_stats_nations_team($event['strHomeTeam']);
    $away = football_stats_nations_team($event['strAwayTeam']);
    $teamGroups = [];
    foreach ($groups as $group=>$teams) foreach ($teams as $team) $teamGroups[$team] = $group;
    if (!isset($teamGroups[$home], $teamGroups[$away]) || $home === $away) return null;
    $homeGroup = $teamGroups[$home]; $awayGroup = $teamGroups[$away];
    $leagues = array_values(array_unique(['league-'.strtolower($homeGroup[0]), 'league-'.strtolower($awayGroup[0])]));
    $round = (string)($event['intRound'] ?? '');
    $stageText = strtolower(implode(' ', [$event['strRound'] ?? '', $event['strStage'] ?? '']));
    $knockout = (is_numeric($round) && (int)$round >= 100) || preg_match('/final|play.?off|play.?out|relegation|quarter|semi/', $stageText);
    if (count($leagues) > 1) {
        $stage = 'playoffs';
    } elseif ($homeGroup === $awayGroup && !$knockout) {
        $stage = 'groups';
    } else {
        $stage = $leagues[0] === 'league-a' ? 'finals' : 'playoffs';
    }
    $roundNames = ['125'=>'Final', '150'=>'Semi-final', '160'=>'Quarter-final', '200'=>'Third-place match'];
    $roundName = $stage === 'groups' ? 'Group '.$homeGroup.' · Matchday '.$round : ($roundNames[$round] ?? ($event['strRound'] ?? ($stage === 'playoffs' ? 'Promotion/relegation play-off' : 'Finals')));
    if ($stage === 'playoffs') $roundName = 'Promotion/relegation play-off';
    $status = strtolower(trim($event['strStatus'] ?? ''));
    if (strtolower($event['strPostponed'] ?? '') === 'yes' || str_contains($status, 'postpon')) {
        $status = 'Postponed';
    } elseif (preg_match('/cancel|abandon|suspend/', $status)) {
        $status = 'Cancelled / abandoned';
    } elseif (in_array($status, ['match finished', 'finished', 'ft', 'aet', 'ap', 'after extra time', 'after penalties', 'awarded'], true)) {
        $status = 'Finished';
    } elseif ($status === '' || in_array($status, ['not started', 'ns', 'scheduled'], true)) {
        // Blank status is common in historical payloads. Require both scores.
        $status = $status === '' && football_stats_nations_score($event['intHomeScore'] ?? null) !== null && football_stats_nations_score($event['intAwayScore'] ?? null) !== null ? 'Finished' : 'Scheduled';
    } else {
        $status = 'Live';
    }
    $date = $event['strTimestamp'] ?? null;
    if (!$date && !empty($event['dateEvent'])) $date = $event['dateEvent'].'T'.(($event['strTime'] ?? '') ?: '00:00:00').'Z';
    if ($date) {
        try { $date = (new DateTimeImmutable($date, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'); }
        catch (Exception $exception) { $date = null; }
    }
    return [
        'id'=>'tsdb:'.$edition.':'.$event['idEvent'], 'tournament'=>$edition, 'league'=>$leagues[0],
        'stage'=>$stage, 'round_name'=>$roundName, 'match_date'=>$date,
        'home_team'=>$home, 'away_team'=>$away,
        'home_score'=>football_stats_nations_score($event['intHomeScore'] ?? null),
        'away_score'=>football_stats_nations_score($event['intAwayScore'] ?? null),
        'status'=>$status,
        'home_pens'=>football_stats_nations_score($event['intHomeScorePen'] ?? null),
        'away_pens'=>football_stats_nations_score($event['intAwayScorePen'] ?? null),
        'leagues'=>$leagues,
    ];
}

function football_stats_nations_totals(array $teams, array $matches): array
{
    $stats = [];
    foreach ($teams as $team) $stats[$team] = array_fill_keys(['played','won','drawn','lost','gf','ga','gd','points','away_gf','away_won'], 0);
    foreach ($matches as $match) {
        $home = $match['home_team']; $away = $match['away_team'];
        if (!isset($stats[$home], $stats[$away]) || $match['status'] !== 'Finished' || $match['home_score'] === null || $match['away_score'] === null) continue;
        $hg = (int)$match['home_score']; $ag = (int)$match['away_score'];
        $stats[$home]['played']++; $stats[$away]['played']++;
        $stats[$home]['gf'] += $hg; $stats[$home]['ga'] += $ag;
        $stats[$away]['gf'] += $ag; $stats[$away]['ga'] += $hg; $stats[$away]['away_gf'] += $ag;
        if ($hg === $ag) {
            foreach ([$home,$away] as $team) { $stats[$team]['drawn']++; $stats[$team]['points']++; }
        } else {
            $winner = $hg > $ag ? $home : $away; $loser = $hg > $ag ? $away : $home;
            $stats[$winner]['won']++; $stats[$winner]['points'] += 3; $stats[$loser]['lost']++;
            if ($winner === $away) $stats[$away]['away_won']++;
        }
    }
    foreach ($stats as &$row) $row['gd'] = $row['gf'] - $row['ga'];
    return $stats;
}

/** Reapply head-to-head criteria to tied subsets before overall goal criteria. */
function football_stats_nations_order(array $teams, array $matches, array $totals, string $edition): array
{
    if (count($teams) < 2) return $teams;
    $head = football_stats_nations_totals($teams, $matches);
    $fields = $edition <= '2020-2021' ? ['points','gd','gf','away_gf'] : ['points','gd','gf'];
    $buckets = [];
    foreach ($teams as $team) $buckets[implode(':', array_map(static fn($field) => $head[$team][$field], $fields))][] = $team;
    if (count($buckets) > 1) {
        $buckets = array_values($buckets);
        usort($buckets, static function($a,$b) use ($head,$fields) {
            foreach ($fields as $field) { $result = $head[$b[0]][$field] <=> $head[$a[0]][$field]; if ($result) return $result; }
            return 0;
        });
        $ordered = [];
        foreach ($buckets as $bucket) array_push($ordered, ...football_stats_nations_order($bucket, $matches, $totals, $edition));
        return $ordered;
    }
    usort($teams, static function($a,$b) use ($totals) {
        foreach (['gd','gf','away_gf','won','away_won'] as $field) { $result = $totals[$b][$field] <=> $totals[$a][$field]; if ($result) return $result; }
        return strcasecmp($a,$b); // Disciplinary points and access rankings are not supplied.
    });
    return $teams;
}

function football_stats_nations_import(PDO $db, string $edition, array $events): array
{
    $groups = football_stats_nations_groups($edition);
    if (!$groups) throw new InvalidArgumentException('Unsupported Nations League edition.');
    $normalized = []; $skipped = 0;
    foreach ($events as $event) {
        $row = is_array($event) ? football_stats_nations_event($event, $edition, $groups) : null;
        if ($row === null) { $skipped++; continue; }
        $normalized[$row['id']] = $row;
    }
    if (!$normalized) return ['matches'=>0, 'skipped'=>$skipped];
    $db->beginTransaction();
    try {
        $db->exec(file_get_contents(__DIR__.'/../tabs/nations-league/schema.sql'));
        $columns = array_column($db->query('PRAGMA table_info(nl_matches)')->fetchAll(PDO::FETCH_ASSOC), 'name');
        foreach (['home_pens','away_pens'] as $column) if (!in_array($column, $columns, true)) $db->exec("ALTER TABLE nl_matches ADD COLUMN $column INTEGER");
        $insert = $db->prepare('INSERT INTO nl_matches (id,tournament,league,stage,round_name,match_date,home_team,away_team,home_score,away_score,status,home_pens,away_pens) VALUES (:id,:tournament,:league,:stage,:round_name,:match_date,:home_team,:away_team,:home_score,:away_score,:status,:home_pens,:away_pens) ON CONFLICT(id) DO UPDATE SET league=excluded.league,stage=excluded.stage,round_name=excluded.round_name,match_date=excluded.match_date,home_team=excluded.home_team,away_team=excluded.away_team,home_score=excluded.home_score,away_score=excluded.away_score,status=excluded.status,home_pens=excluded.home_pens,away_pens=excluded.away_pens');
        $clearLinks = $db->prepare('DELETE FROM nl_match_leagues WHERE match_id = ?');
        $link = $db->prepare('INSERT INTO nl_match_leagues (match_id,league) VALUES (?,?)');
        foreach ($normalized as $row) {
            $leagues = $row['leagues']; unset($row['leagues']);
            $insert->execute($row); $clearLinks->execute([$row['id']]);
            foreach ($leagues as $league) $link->execute([$row['id'],$league]);
        }
        // Merge returned events with the cache: truncated API responses must not delete fixtures.
        $get = $db->prepare("SELECT * FROM nl_matches WHERE tournament = ? AND stage = 'groups'");
        $get->execute([$edition]); $matches = $get->fetchAll(PDO::FETCH_ASSOC);
        $delete = $db->prepare('DELETE FROM nl_standings WHERE tournament = ?'); $delete->execute([$edition]);
        $standing = $db->prepare('INSERT INTO nl_standings (tournament,league,group_name,position,team_name,played,won,drawn,lost,gf,ga,gd,points) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($groups as $group=>$teams) {
            $groupMatches = array_values(array_filter($matches, static fn($match) => in_array($match['home_team'],$teams,true) && in_array($match['away_team'],$teams,true)));
            if (!$groupMatches) continue;
            $stats = football_stats_nations_totals($teams,$groupMatches); $pointBuckets = [];
            foreach ($teams as $team) $pointBuckets[$stats[$team]['points']][] = $team;
            krsort($pointBuckets, SORT_NUMERIC); $position = 1;
            foreach ($pointBuckets as $tied) foreach (football_stats_nations_order($tied,$groupMatches,$stats,$edition) as $team) {
                $s = $stats[$team];
                $standing->execute([$edition,'league-'.strtolower($group[0]),$group,$position++,$team,$s['played'],$s['won'],$s['drawn'],$s['lost'],$s['gf'],$s['ga'],$s['gd'],$s['points']]);
            }
        }
        $meta = $db->prepare('INSERT INTO nl_sync_status (tournament,synced_at,match_count,skipped_count) VALUES (?,?,?,?) ON CONFLICT(tournament) DO UPDATE SET synced_at=excluded.synced_at,match_count=excluded.match_count,skipped_count=excluded.skipped_count');
        $meta->execute([$edition,gmdate('Y-m-d H:i:s'),count($normalized),$skipped]);
        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack(); throw $exception;
    }
    return ['matches'=>count($normalized), 'skipped'=>$skipped];
}

function football_stats_sync_nations_league(PDO $db, string $key, array $editions, ?callable $request = null): array
{
    $request ??= 'football_stats_sportsdb_request';
    $results = [];
    foreach ($editions as $edition) {
        if (!isset(football_stats_nations_league_editions()[$edition])) throw new InvalidArgumentException('Unsupported Nations League edition.');
        $data = $request($key, 'eventsseason.php', ['id'=>'4490','s'=>$edition]);
        if (!is_array($data) || !is_array($data['events'] ?? null) || !$data['events']) {
            $results[$edition] = ['matches'=>0,'skipped'=>0,'error'=>'No events returned; cached data preserved.'];
            continue;
        }
        try {
            $results[$edition] = football_stats_nations_import($db,$edition,$data['events']);
            if (!$results[$edition]['matches']) $results[$edition]['error'] = 'No recognised events; cached data preserved.';
        } catch (Throwable $exception) {
            $results[$edition] = ['matches'=>0,'skipped'=>0,'error'=>'Import failed; cached data preserved.'];
        }
    }
    return $results;
}
