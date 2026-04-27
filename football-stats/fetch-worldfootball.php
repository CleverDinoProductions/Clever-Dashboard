<?php
// fetch-worldfootball.php (PHP version)
// Ported from Python script to PHP

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Fetching Premier League & World Cup data...\n";

// API Configuration
$API_KEY = '2467baa7f4d747f5b7d2d99498a70172';
$headers_api = [
    'http' => [
        'method' => 'GET',
        'header' => "X-Auth-Token: $API_KEY\r\n"
    ]
];
$context_api = stream_context_create($headers_api);

// Database connection
$db = new SQLite3('football-stats.sqlite3');

function build_season_label($season_data) {
    if (!$season_data) return date('Y') . '-' . (date('Y') + 1);
    $start_date = $season_data['startDate'] ?? null;
    $end_date = $season_data['endDate'] ?? null;
    if ($start_date && $end_date) {
        return substr($start_date, 0, 4) . '-' . substr($end_date, 0, 4);
    }
    if ($start_date) {
        $start_year = intval(substr($start_date, 0, 4));
        return $start_year . '-' . ($start_year + 1);
    }
    $current_year = date('Y');
    return $current_year . '-' . ($current_year + 1);
}

function fetch_json($url, $context_api) {
    $json = @file_get_contents($url, false, $context_api);
    if ($json === false) return null;
    return json_decode($json, true);
}

function exec_sql($db, $sql, $params = []) {
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k + 1, $v);
    }
    return $stmt->execute();
}


// --- Create tables if they don't exist ---
$db->exec("CREATE TABLE IF NOT EXISTS league_table_PL (
    team_crest TEXT,
    team_name TEXT,
    position INTEGER,
    played INTEGER,
    won INTEGER,
    drawn INTEGER,
    lost INTEGER,
    gf INTEGER,
    ga INTEGER,
    gd INTEGER,
    points INTEGER,
    updated_at INTEGER
)");
$db->exec("CREATE TABLE IF NOT EXISTS league_table_ELC (
    team_crest TEXT,
    team_name TEXT,
    position INTEGER,
    played INTEGER,
    won INTEGER,
    drawn INTEGER,
    lost INTEGER,
    gf INTEGER,
    ga INTEGER,
    gd INTEGER,
    points INTEGER,
    updated_at INTEGER
)");
$db->exec("CREATE TABLE IF NOT EXISTS league_table_snapshots (
    competition_code TEXT,
    season_label TEXT,
    matchweek INTEGER,
    team_crest TEXT,
    team_name TEXT,
    position INTEGER,
    played INTEGER,
    won INTEGER,
    drawn INTEGER,
    lost INTEGER,
    gf INTEGER,
    ga INTEGER,
    gd INTEGER,
    points INTEGER,
    source_updated_at INTEGER,
    archived_at INTEGER,
    PRIMARY KEY (competition_code, season_label, matchweek, team_name)
)");
$db->exec("CREATE TABLE IF NOT EXISTS live_table_metadata (
    competition_code TEXT PRIMARY KEY,
    live_table_name TEXT NOT NULL,
    season_label TEXT NOT NULL,
    matchweek INTEGER NOT NULL,
    updated_at INTEGER NOT NULL
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_league_table_snapshots_lookup
    ON league_table_snapshots (competition_code, season_label, matchweek, position)");
$db->exec("CREATE TABLE IF NOT EXISTS wc_groups (
    group_name TEXT,
    team_name TEXT,
    position INTEGER,
    played INTEGER,
    won INTEGER,
    drawn INTEGER,
    lost INTEGER,
    gf INTEGER,
    ga INTEGER,
    gd INTEGER,
    points INTEGER,
    updated_at INTEGER
)");
$db->exec("CREATE TABLE IF NOT EXISTS wc_knockout (
    stage TEXT,
    match_number INTEGER,
    match_date TEXT,
    home_team TEXT,
    away_team TEXT,
    home_score INTEGER,
    away_score INTEGER,
    status TEXT,
    venue TEXT,
    updated_at INTEGER
)");
$db->exec("CREATE TABLE IF NOT EXISTS wc_third_place (
    group_name TEXT,
    team_name TEXT,
    rank INTEGER,
    played INTEGER,
    won INTEGER,
    drawn INTEGER,
    lost INTEGER,
    gf INTEGER,
    ga INTEGER,
    gd INTEGER,
    points INTEGER,
    qualified INTEGER,
    updated_at INTEGER
)");
$db->exec("CREATE TABLE IF NOT EXISTS wc_standings (
    team_name TEXT,
    stage TEXT,
    rank INTEGER,
    played INTEGER,
    won INTEGER,
    drawn INTEGER,
    lost INTEGER,
    gf INTEGER,
    ga INTEGER,
    gd INTEGER,
    points INTEGER,
    updated_at INTEGER
)");

// --- FETCH PREMIER LEAGUE DATA ---
// --- Helper functions for archiving and snapshots ---
function get_live_table_metadata($db, $competition_code) {
    $stmt = $db->prepare("SELECT competition_code, live_table_name, season_label, matchweek, updated_at FROM live_table_metadata WHERE competition_code = ?");
    $stmt->bindValue(1, $competition_code);
    $res = $stmt->execute();
    return $res->fetchArray(SQLITE3_ASSOC);
}

function get_live_table_rows($db, $live_table_name) {
    $res = $db->query("SELECT * FROM $live_table_name ORDER BY position ASC");
    $rows = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}

function archive_live_table_if_needed($db, $competition_code, $live_table_name, $incoming_season_label, $incoming_matchweek, $archived_at) {
    $metadata = get_live_table_metadata($db, $competition_code);
    $live_rows = get_live_table_rows($db, $live_table_name);
    if (!$live_rows) return;
    $existing_matchweek = $metadata['matchweek'] ?? max(array_column($live_rows, 'played'));
    $existing_season_label = $metadata['season_label'] ?? $incoming_season_label;
    if ($existing_matchweek === null) return;
    if ($metadata && $metadata['season_label'] === $incoming_season_label && $metadata['matchweek'] == $incoming_matchweek) {
        echo "  · $competition_code: live table already represents matchweek $incoming_matchweek; snapshot unchanged\n";
        return;
    }
    $stmt = $db->prepare("SELECT COUNT(*) FROM league_table_snapshots WHERE competition_code = ? AND season_label = ? AND matchweek = ?");
    $stmt->bindValue(1, $competition_code);
    $stmt->bindValue(2, $existing_season_label);
    $stmt->bindValue(3, $existing_matchweek);
    $res = $stmt->execute();
    $existing_snapshot_count = $res->fetchArray()[0];
    if ($existing_snapshot_count) {
        echo "  · $competition_code: snapshot for $existing_season_label MW$existing_matchweek already stored\n";
        return;
    }
    $stmt = $db->prepare("INSERT OR REPLACE INTO league_table_snapshots (competition_code, season_label, matchweek, team_crest, team_name, position, played, won, drawn, lost, gf, ga, gd, points, source_updated_at, archived_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($live_rows as $row) {
        $params = [
            $competition_code,
            $existing_season_label,
            $existing_matchweek,
            $row['team_crest'],
            $row['team_name'],
            $row['position'],
            $row['played'],
            $row['won'],
            $row['drawn'],
            $row['lost'],
            $row['gf'],
            $row['ga'],
            $row['gd'],
            $row['points'],
            $row['updated_at'],
            $archived_at
        ];
        foreach ($params as $i => $v) {
            $stmt->bindValue($i + 1, $v);
        }
        $stmt->execute();
    }
    echo "  · $competition_code: archived ".count($live_rows)." rows for $existing_season_label matchweek $existing_matchweek\n";
}

function replace_live_table($db, $live_table_name, $teams_data, $updated_at) {
    $db->exec("DELETE FROM $live_table_name");
    $stmt = $db->prepare("INSERT INTO $live_table_name VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($teams_data as $team) {
        $params = array_merge($team, [$updated_at]);
        foreach ($params as $i => $v) {
            $stmt->bindValue($i + 1, $v);
        }
        $stmt->execute();
    }
}

function update_live_table_metadata($db, $competition_code, $live_table_name, $season_label, $matchweek, $updated_at) {
    $stmt = $db->prepare("INSERT INTO live_table_metadata (competition_code, live_table_name, season_label, matchweek, updated_at) VALUES (?, ?, ?, ?, ?) ON CONFLICT(competition_code) DO UPDATE SET live_table_name = excluded.live_table_name, season_label = excluded.season_label, matchweek = excluded.matchweek, updated_at = excluded.updated_at");
    $stmt->bindValue(1, $competition_code);
    $stmt->bindValue(2, $live_table_name);
    $stmt->bindValue(3, $season_label);
    $stmt->bindValue(4, $matchweek);
    $stmt->bindValue(5, $updated_at);
    $stmt->execute();
}

function recalculate_snapshots_from_matches($db, $competition_code, $season_label) {
    $stmt = $db->prepare("SELECT DISTINCT matchweek FROM matches WHERE competition_code = ? AND season_label = ? AND home_goals IS NOT NULL AND away_goals IS NOT NULL");
    $stmt->bindValue(1, $competition_code);
    $stmt->bindValue(2, $season_label);
    $res = $stmt->execute();
    $matchweeks = [];
    while ($row = $res->fetchArray(SQLITE3_NUM)) {
        $matchweeks[] = $row[0];
    }
    if (!$matchweeks) {
        echo "  • No matches found for $competition_code $season_label, skipping snapshot recalculation.\n";
        return;
    }
    $stmt = $db->prepare("SELECT DISTINCT matchweek FROM league_table_snapshots WHERE competition_code = ? AND season_label = ?");
    $stmt->bindValue(1, $competition_code);
    $stmt->bindValue(2, $season_label);
    $res = $stmt->execute();
    $existing_snapshots = [];
    while ($row = $res->fetchArray(SQLITE3_NUM)) {
        $existing_snapshots[] = $row[0];
    }
    if (count(array_diff($matchweeks, $existing_snapshots)) === 0) {
        echo "  • Snapshots already exist for all matchweeks in $competition_code $season_label, but will recalculate and update latest snapshot.\n";
    }
    echo "  • Recalculating league_table_snapshots for $competition_code $season_label from matches table...\n";
    $res = $db->query("SELECT matchweek, home_team, away_team, home_goals, away_goals FROM matches WHERE competition_code = '$competition_code' AND season_label = '$season_label' AND home_goals IS NOT NULL AND away_goals IS NOT NULL ORDER BY matchweek, id");
    $matches = [];
    while ($row = $res->fetchArray(SQLITE3_NUM)) {
        $matches[] = $row;
    }
    $matches_by_week = [];
    foreach ($matches as $match) {
        $matches_by_week[$match[0]][] = $match;
    }
    $teams_list = [];
    $res = $db->query("SELECT DISTINCT home_team FROM matches WHERE competition_code = '$competition_code' AND season_label = '$season_label' UNION SELECT DISTINCT away_team FROM matches WHERE competition_code = '$competition_code' AND season_label = '$season_label'");
    while ($row = $res->fetchArray(SQLITE3_NUM)) {
        $teams_list[] = $row[0];
    }
    $teams = [];
    foreach ($teams_list as $name) {
        $teams[$name] = [
            'team_crest' => '',
            'team_name' => $name,
            'position' => 0,
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'gf' => 0,
            'ga' => 0,
            'gd' => 0,
            'points' => 0,
            'updated_at' => 0
        ];
    }
    $updated_at = round(microtime(true) * 1000);
    function update_standings(&$teams, $match) {
        list($_, $home, $away, $home_goals, $away_goals) = $match;
        $teams[$home]['played'] += 1;
        $teams[$away]['played'] += 1;
        $teams[$home]['gf'] += $home_goals;
        $teams[$home]['ga'] += $away_goals;
        $teams[$away]['gf'] += $away_goals;
        $teams[$away]['ga'] += $home_goals;
        $teams[$home]['gd'] = $teams[$home]['gf'] - $teams[$home]['ga'];
        $teams[$away]['gd'] = $teams[$away]['gf'] - $teams[$away]['ga'];
        if ($home_goals > $away_goals) {
            $teams[$home]['won'] += 1;
            $teams[$away]['lost'] += 1;
            $teams[$home]['points'] += 3;
        } elseif ($home_goals < $away_goals) {
            $teams[$away]['won'] += 1;
            $teams[$home]['lost'] += 1;
            $teams[$away]['points'] += 3;
        } else {
            $teams[$home]['drawn'] += 1;
            $teams[$away]['drawn'] += 1;
            $teams[$home]['points'] += 1;
            $teams[$away]['points'] += 1;
        }
    }
    function standings_snapshot($teams) {
        uasort($teams, function($a, $b) {
            if ($a['points'] != $b['points']) return $b['points'] - $a['points'];
            if ($a['gd'] != $b['gd']) return $b['gd'] - $a['gd'];
            if ($a['gf'] != $b['gf']) return $b['gf'] - $a['gf'];
            return strcmp($a['team_name'], $b['team_name']);
        });
        $pos = 1;
        foreach ($teams as &$team) {
            $team['position'] = $pos++;
        }
        return $teams;
    }
    function insert_snapshot($db, $competition_code, $season_label, $matchweek, $teams, $updated_at, $archived_at = null) {
        $stmt = $db->prepare("INSERT OR REPLACE INTO league_table_snapshots (competition_code, season_label, matchweek, team_crest, team_name, position, played, won, drawn, lost, gf, ga, gd, points, source_updated_at, archived_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach (standings_snapshot($teams) as $team) {
            $params = [
                $competition_code,
                $season_label,
                $matchweek,
                '',
                $team['team_name'],
                $team['position'],
                $team['played'],
                $team['won'],
                $team['drawn'],
                $team['lost'],
                $team['gf'],
                $team['ga'],
                $team['gd'],
                $team['points'],
                $updated_at,
                $archived_at ?? $updated_at
            ];
            foreach ($params as $i => $v) {
                $stmt->bindValue($i + 1, $v);
            }
            $stmt->execute();
        }
    }
    $db->exec("DELETE FROM league_table_snapshots WHERE competition_code = '$competition_code' AND season_label = '$season_label'");
    insert_snapshot($db, $competition_code, $season_label, 0, $teams, $updated_at, $updated_at);
    echo "  · Pre-season snapshot inserted (matchweek 0)...\n";
    ksort($matches_by_week);
    foreach ($matches_by_week as $mw => $matches) {
        foreach ($matches as $match) {
            update_standings($teams, $match);
        }
        $stmt = $db->prepare("SELECT MIN(match_date) FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek = ?");
        $stmt->bindValue(1, $competition_code);
        $stmt->bindValue(2, $season_label);
        $stmt->bindValue(3, $mw);
        $res = $stmt->execute();
        $min_date_row = $res->fetchArray(SQLITE3_NUM);
        $archived_at_ts = $updated_at;
        if ($min_date_row && $min_date_row[0]) {
            $min_date = $min_date_row[0];
            $archived_at_ts = strtotime($min_date) * 1000;
        }
        insert_snapshot($db, $competition_code, $season_label, $mw, $teams, $updated_at, $archived_at_ts);
        echo "  · Snapshot recalculated for matchweek $mw (archived_at=$archived_at_ts)\n";
    }
    // Overwrite latest matchweek snapshot with current live table
    $stmt = $db->prepare("SELECT matchweek FROM live_table_metadata WHERE competition_code = ?");
    $stmt->bindValue(1, $competition_code);
    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_NUM);
    if ($row) {
        $current_mw = $row[0];
        $live_table_name = "league_table_" . $competition_code;
        $res2 = $db->query("SELECT * FROM $live_table_name ORDER BY position ASC");
        while ($team = $res2->fetchArray(SQLITE3_ASSOC)) {
            $stmt2 = $db->prepare("INSERT OR REPLACE INTO league_table_snapshots (competition_code, season_label, matchweek, team_crest, team_name, position, played, won, drawn, lost, gf, ga, gd, points, source_updated_at, archived_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $params = [
                $competition_code,
                $season_label,
                $current_mw,
                '',
                $team['team_name'],
                $team['position'],
                $team['played'],
                $team['won'],
                $team['drawn'],
                $team['lost'],
                $team['gf'],
                $team['ga'],
                $team['gd'],
                $team['points'],
                $team['updated_at'],
                round(microtime(true) * 1000)
            ];
            foreach ($params as $i => $v) {
                $stmt2->bindValue($i + 1, $v);
            }
            $stmt2->execute();
        }
        echo "  · Snapshot for matchweek $current_mw updated to mirror current live table.\n";
    }
    echo "  ✓ Snapshots recalculated from matches.\n";
}
echo "\n[1/2] Fetching Premier League...\n";
$url_pl = "https://api.football-data.org/v4/competitions/PL/standings";
$url_pl_matches = "https://api.football-data.org/v4/competitions/PL/matches";

$data = fetch_json($url_pl, $context_api);
if ($data) {
    $standings = $data['standings'][0]['table'];
    $season_label = build_season_label($data['season'] ?? null);
    $matchweek = $data['season']['currentMatchday'] ?? max(array_column($standings, 'playedGames'));
    $teams_data = [];
    foreach ($standings as $team) {
        $teams_data[] = [
            $team['team']['crest'],
            $team['team']['name'],
            $team['position'],
            $team['playedGames'],
            $team['won'],
            $team['draw'],
            $team['lost'],
            $team['goalsFor'],
            $team['goalsAgainst'],
            $team['goalDifference'],
            $team['points']
        ];
    }
    $timestamp = round(microtime(true) * 1000);
    // Archive, replace, and update metadata (functions to be ported next)
    // archive_live_table_if_needed($db, 'PL', 'league_table_PL', $season_label, $matchweek, $timestamp);
    $db->exec("DELETE FROM league_table_PL");
    $stmt = $db->prepare("INSERT INTO league_table_PL VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($teams_data as $team) {
        $params = array_merge($team, [$timestamp]);
        foreach ($params as $i => $v) {
            $stmt->bindValue($i + 1, $v);
        }
        $stmt->execute();
    }
    $stmt = $db->prepare("INSERT INTO live_table_metadata (competition_code, live_table_name, season_label, matchweek, updated_at) VALUES (?, ?, ?, ?, ?) ON CONFLICT(competition_code) DO UPDATE SET live_table_name = excluded.live_table_name, season_label = excluded.season_label, matchweek = excluded.matchweek, updated_at = excluded.updated_at");
    $stmt->bindValue(1, 'PL');
    $stmt->bindValue(2, 'league_table_PL');
    $stmt->bindValue(3, $season_label);
    $stmt->bindValue(4, $matchweek);
    $stmt->bindValue(5, $timestamp);
    $stmt->execute();
    echo "  ✓ Successfully fetched ".count($teams_data)." Premier League teams\n";
    echo "  ✓ Season: $season_label • Matchweek: $matchweek\n";
    echo "  ✓ Updated at: ".date('Y-m-d H:i:s')."\n";
} else {
    echo "  ✗ Failed to fetch Premier League standings\n";
}

// Fetch matches (for matches tab)
$db->exec("CREATE TABLE IF NOT EXISTS matches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    competition_code TEXT,
    season_label TEXT,
    matchweek INTEGER,
    match_date TEXT,
    home_team TEXT,
    away_team TEXT,
    home_goals INTEGER,
    away_goals INTEGER,
    ht_home_goals INTEGER,
    ht_away_goals INTEGER,
    source TEXT
)");
$matches_data = fetch_json($url_pl_matches, $context_api);
if ($matches_data) {
    $stmt = $db->prepare("DELETE FROM matches WHERE competition_code = ? AND season_label = ? AND source = 'api'");
    $stmt->bindValue(1, 'PL');
    $stmt->bindValue(2, $season_label);
    $stmt->execute();
    $insert = $db->prepare("INSERT INTO matches (competition_code, season_label, matchweek, match_date, home_team, away_team, home_goals, away_goals, ht_home_goals, ht_away_goals, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $count = 0;
    foreach (($matches_data['matches'] ?? []) as $m) {
        if (!isset($m['homeTeam']['name']) || !isset($m['awayTeam']['name'])) continue;
        $home = $m['homeTeam']['name'];
        $away = $m['awayTeam']['name'];
        $matchweek = $m['matchday'] ?? ($m['matchWeek'] ?? null);
        $match_date = substr($m['utcDate'] ?? '', 0, 10);
        $ft_home = $m['score']['fullTime']['home'] ?? null;
        $ft_away = $m['score']['fullTime']['away'] ?? null;
        $ht_home = $m['score']['halfTime']['home'] ?? null;
        $ht_away = $m['score']['halfTime']['away'] ?? null;
        $insert->bindValue(1, 'PL');
        $insert->bindValue(2, $season_label);
        $insert->bindValue(3, $matchweek);
        $insert->bindValue(4, $match_date);
        $insert->bindValue(5, $home);
        $insert->bindValue(6, $away);
        $insert->bindValue(7, $ft_home);
        $insert->bindValue(8, $ft_away);
        $insert->bindValue(9, $ht_home);
        $insert->bindValue(10, $ht_away);
        $insert->bindValue(11, 'api');
        $insert->execute();
        $count++;
    }
    echo "  ✓ Inserted $count Premier League matches from API.\n";
} else {
    echo "  ✗ Failed to fetch Premier League matches\n";
}

// --- FETCH CHAMPIONSHIP DATA ---
echo "\n[1.5/2] Fetching Championship...\n";
$url_champ = "https://api.football-data.org/v4/competitions/ELC/standings";
$url_champ_matches = "https://api.football-data.org/v4/competitions/ELC/matches";
$data_champ = fetch_json($url_champ, $context_api);
if ($data_champ) {
    $standings_champ = $data_champ['standings'][0]['table'];
    $season_label_champ = build_season_label($data_champ['season'] ?? null);
    $matchweek_champ = $data_champ['season']['currentMatchday'] ?? max(array_column($standings_champ, 'playedGames'));
    $teams_data_champ = [];
    foreach ($standings_champ as $team) {
        $teams_data_champ[] = [
            $team['team']['crest'],
            $team['team']['name'],
            $team['position'],
            $team['playedGames'],
            $team['won'],
            $team['draw'],
            $team['lost'],
            $team['goalsFor'],
            $team['goalsAgainst'],
            $team['goalDifference'],
            $team['points']
        ];
    }
    $timestamp = round(microtime(true) * 1000);
    archive_live_table_if_needed($db, 'ELC', 'league_table_ELC', $season_label_champ, $matchweek_champ, $timestamp);
    replace_live_table($db, 'league_table_ELC', $teams_data_champ, $timestamp);
    update_live_table_metadata($db, 'ELC', 'league_table_ELC', $season_label_champ, $matchweek_champ, $timestamp);
    echo "  ✓ Successfully fetched ".count($teams_data_champ)." Championship teams\n";
    echo "  ✓ Season: $season_label_champ • Matchweek: $matchweek_champ\n";
    echo "  ✓ Updated at: ".date('Y-m-d H:i:s')."\n";
    // Fetch matches for Championship
    $matches_data_champ = fetch_json($url_champ_matches, $context_api);
    if ($matches_data_champ) {
        $stmt = $db->prepare("DELETE FROM matches WHERE competition_code = ? AND season_label = ? AND source = 'api'");
        $stmt->bindValue(1, 'ELC');
        $stmt->bindValue(2, $season_label_champ);
        $stmt->execute();
        $insert = $db->prepare("INSERT INTO matches (competition_code, season_label, matchweek, match_date, home_team, away_team, home_goals, away_goals, ht_home_goals, ht_away_goals, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $count = 0;
        foreach (($matches_data_champ['matches'] ?? []) as $m) {
            if (!isset($m['homeTeam']['name']) || !isset($m['awayTeam']['name'])) continue;
            $home = $m['homeTeam']['name'];
            $away = $m['awayTeam']['name'];
            $matchweek = $m['matchday'] ?? ($m['matchWeek'] ?? null);
            $match_date = substr($m['utcDate'] ?? '', 0, 10);
            $ft_home = $m['score']['fullTime']['home'] ?? null;
            $ft_away = $m['score']['fullTime']['away'] ?? null;
            $ht_home = $m['score']['halfTime']['home'] ?? null;
            $ht_away = $m['score']['halfTime']['away'] ?? null;
            $insert->bindValue(1, 'ELC');
            $insert->bindValue(2, $season_label_champ);
            $insert->bindValue(3, $matchweek);
            $insert->bindValue(4, $match_date);
            $insert->bindValue(5, $home);
            $insert->bindValue(6, $away);
            $insert->bindValue(7, $ft_home);
            $insert->bindValue(8, $ft_away);
            $insert->bindValue(9, $ht_home);
            $insert->bindValue(10, $ht_away);
            $insert->bindValue(11, 'api');
            $insert->execute();
            $count++;
        }
        echo "  ✓ Inserted $count Championship matches from API.\n";
        // Recalculate snapshots for Championship
        recalculate_snapshots_from_matches($db, 'ELC', $season_label_champ);
    } else {
        echo "  ✗ Failed to fetch Championship matches\n";
    }
}

// --- FETCH WORLD CUP DATA ---
echo "\n[2/2] Fetching World Cup...\n";
$url_wc = "https://api.football-data.org/v4/competitions/WC/standings";
$data_wc = fetch_json($url_wc, $context_api);
if ($data_wc) {
    $db->exec("DELETE FROM wc_groups");
    $db->exec("DELETE FROM wc_third_place");
    $db->exec("DELETE FROM wc_knockout");
    $db->exec("DELETE FROM wc_standings");
    $timestamp = round(microtime(true) * 1000);
    $matchday = $data_wc['season']['currentMatchday'] ?? null;
    $season_label = build_season_label($data_wc['season'] ?? null);
    $archived_at = $timestamp;
    foreach ($data_wc['standings'] as $standing) {
        if ($standing['type'] === 'TOTAL') {
            $group_name = $standing['group'] ?? 'Overall';
            foreach ($standing['table'] as $team) {
                $db->exec("INSERT INTO wc_groups VALUES (" .
                    "'" . SQLite3::escapeString($group_name) . "', " .
                    "'" . SQLite3::escapeString($team['team']['name']) . "', " .
                    intval($team['position']) . ", " .
                    intval($team['playedGames']) . ", " .
                    intval($team['won']) . ", " .
                    intval($team['draw']) . ", " .
                    intval($team['lost']) . ", " .
                    intval($team['goalsFor']) . ", " .
                    intval($team['goalsAgainst']) . ", " .
                    intval($team['goalDifference']) . ", " .
                    intval($team['points']) . ", " .
                    $timestamp .
                ")");
                if ($team['position'] == 3) {
                    $db->exec("INSERT INTO wc_third_place VALUES (" .
                        "'" . SQLite3::escapeString($group_name) . "', " .
                        "'" . SQLite3::escapeString($team['team']['name']) . "', " .
                        "0, " .
                        intval($team['playedGames']) . ", " .
                        intval($team['won']) . ", " .
                        intval($team['draw']) . ", " .
                        intval($team['lost']) . ", " .
                        intval($team['goalsFor']) . ", " .
                        intval($team['goalsAgainst']) . ", " .
                        intval($team['goalDifference']) . ", " .
                        intval($team['points']) . ", " .
                        "0, " .
                        $timestamp .
                    ")");
                }
                $db->exec("INSERT INTO wc_standings VALUES (" .
                    "'" . SQLite3::escapeString($team['team']['name']) . "', " .
                    "'" . SQLite3::escapeString($group_name) . "', " .
                    intval($team['position']) . ", " .
                    intval($team['playedGames']) . ", " .
                    intval($team['won']) . ", " .
                    intval($team['draw']) . ", " .
                    intval($team['lost']) . ", " .
                    intval($team['goalsFor']) . ", " .
                    intval($team['goalsAgainst']) . ", " .
                    intval($team['goalDifference']) . ", " .
                    intval($team['points']) . ", " .
                    $timestamp .
                ")");
            }
        }
    }
    // Fetch knockout matches
    $url_wc_matches = "https://api.football-data.org/v4/competitions/WC/matches";
    $matches_data = fetch_json($url_wc_matches, $context_api);
    if ($matches_data) {
        foreach (($matches_data['matches'] ?? []) as $match) {
            $stage = $match['stage'] ?? '';
            $stage_order = ['ROUND_OF_32', 'ROUND_OF_16', 'QUARTER_FINALS', 'SEMI_FINALS', 'THIRD_PLACE', 'FINAL'];
            if (in_array($stage, $stage_order)) {
                $db->exec("INSERT INTO wc_knockout VALUES (" .
                    "'" . SQLite3::escapeString($stage) . "', " .
                    intval($match['matchNumber'] ?? 0) . ", " .
                    "'" . SQLite3::escapeString(substr($match['utcDate'] ?? '', 0, 10)) . "', " .
                    "'" . SQLite3::escapeString($match['homeTeam']['name'] ?? 'TBD') . "', " .
                    "'" . SQLite3::escapeString($match['awayTeam']['name'] ?? 'TBD') . "', " .
                    ($match['score']['fullTime']['home'] ?? 'NULL') . ", " .
                    ($match['score']['fullTime']['away'] ?? 'NULL') . ", " .
                    "'" . SQLite3::escapeString($match['status'] ?? 'SCHEDULED') . "', " .
                    "'" . SQLite3::escapeString($match['venue'] ?? 'TBD') . "', " .
                    $timestamp .
                ")");
            }
        }
        echo "  ✓ Successfully fetched World Cup knockout matches (including Round of 32)\n";
    }
    echo "  ✓ Updated at: ".date('Y-m-d H:i:s')."\n";
    // --- Calculate Best 3rd Place Teams ---
    echo "\n[3/2] Calculating Best 3rd Place Teams...\n";
    $teams = [];
    $res = $db->query("SELECT team_name, points, gd, gf FROM wc_third_place");
    while ($row = $res->fetchArray(SQLITE3_NUM)) {
        $teams[] = $row;
    }
    // Sort: Points -> GD -> GF
    usort($teams, function($a, $b) {
        if ($a[1] != $b[1]) return $b[1] - $a[1];
        if ($a[2] != $b[2]) return $b[2] - $a[2];
        return $b[3] - $a[3];
    });
    // Top 8 qualify
    foreach ($teams as $rank => $team) {
        $team_name = $team[0];
        $qualified = ($rank < 8) ? 1 : 0;
        $stmt = $db->prepare("UPDATE wc_third_place SET rank = ?, qualified = ? WHERE team_name = ?");
        $stmt->bindValue(1, $rank + 1);
        $stmt->bindValue(2, $qualified);
        $stmt->bindValue(3, $team_name);
        $stmt->execute();
        $status = $qualified ? "✅ Q" : "❌";
        echo "   ".($rank + 1).". $team_name ($status)\n";
    }
} else {
    echo "  ✗ Failed to fetch World Cup data\n";
}
