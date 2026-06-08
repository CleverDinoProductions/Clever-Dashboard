<?php
/**
 * fetch-worldfootball-badge-injector.php
 * REVISED SMART VERSION:
 * - Payload Optimized: Uses new trimmed v1/v2 eventsseason.php fields.
 * - Auto-Crest Discovery: Grabs badges directly from fixture events.
 * - Efficient Reconstruction: Skips stable historical data, updates current season.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

echo "⚽ Initializing Optimized Sync & Badge Injector...\n";

$API_KEY  = '876419';
$BASE_URL = "https://www.thesportsdb.com/api/v1/json/$API_KEY/";
$db       = new SQLite3('football-stats.sqlite3');

// --- 1. Database Schema (Same as before, ensures consistency) ---
$tables = [
    "league_table_PL" => "team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER",
    "league_table_ELC" => "team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER",
    "league_table_L1" => "team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER",
    "league_table_L2" => "team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER",
    "league_table_NL" => "team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER",
    "matches" => "id INTEGER PRIMARY KEY AUTOINCREMENT, competition_code TEXT, season_label TEXT, matchweek INTEGER, match_date TEXT, home_team TEXT, away_team TEXT, home_goals INTEGER, away_goals INTEGER, home_pens INTEGER, away_pens INTEGER, status TEXT, source TEXT",
    "league_table_snapshots" => "competition_code TEXT, season_label TEXT, matchweek INTEGER, team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, source_updated_at INTEGER, archived_at INTEGER, PRIMARY KEY (competition_code, season_label, matchweek, team_name)",
    "league_table_snapshots_by_date" => "competition_code TEXT, season_label TEXT, snapshot_date TEXT, team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, source_updated_at INTEGER, archived_at INTEGER, PRIMARY KEY (competition_code, season_label, snapshot_date, team_name)",
    "live_table_metadata" => "competition_code TEXT PRIMARY KEY, live_table_name TEXT NOT NULL, season_label TEXT NOT NULL, matchweek INTEGER NOT NULL, updated_at INTEGER NOT NULL",
];

foreach ($tables as $name => $schema) {
    $db->exec("CREATE TABLE IF NOT EXISTS $name ($schema)");
}

// Add missing columns to existing matches tables (no-op if already present)
foreach (['home_pens INTEGER', 'away_pens INTEGER', 'status TEXT'] as $colDef) {
    try { $db->exec("ALTER TABLE matches ADD COLUMN $colDef"); } catch (Exception $e) {}
}

// --- 2. Core League Processing ---

function sync_league($db, $BASE_URL, $code, $id) {
    echo "\n[Syncing $code (ID: $id)]\n";
    $timestamp = round(microtime(true) * 1000);
    $crest_map = [];
    
    // STEP A: Pre-populate crest map from current search (Good for modern badges)
    $teams_json = json_decode(@file_get_contents("{$BASE_URL}search_all_teams.php?l=" . urlencode($code)), true);
    if ($teams_json && isset($teams_json['teams'])) {
        foreach ($teams_json['teams'] as $t) {
            if (!empty($t['strTeamBadge'])) $crest_map[$t['strTeam']] = $t['strTeamBadge'];
        }
    }

    // STEP B: Update Live View
    $live_data = json_decode(@file_get_contents("{$BASE_URL}lookuptable.php?l=$id"), true);
    // Derive current season from date so playoff-era fetches aren't skipped if the live
    // table has already rolled over to next season (TheSportsDB does this at season end).
    $cm = (int)date('n'); $cy = (int)date('Y');
    $date_based_season = $cm >= 7 ? "$cy-" . ($cy + 1) : ($cy - 1) . "-$cy";
    $current_season = $date_based_season;
    if ($live_data && isset($live_data['table'])) {
        $current_season = $live_data['table'][0]['strSeason'] ?? $date_based_season;
        $current_mw = max(array_column($live_data['table'], 'intPlayed'));
        $table_name = "league_table_$code";

        $db->exec("DELETE FROM $table_name");
        $l_stmt = $db->prepare("INSERT INTO $table_name VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($live_data['table'] as $t) {
            $name = $t['strTeam'];
            $badge = $t['strBadge'] ?? ($crest_map[$name] ?? '');
            $crest_map[$name] = $badge; 

            $vals = [$badge, $name, $t['intRank'], $t['intPlayed'], $t['intWin'], $t['intDraw'], $t['intLoss'], $t['intGoalsFor'], $t['intGoalsAgainst'], $t['intGoalDifference'], $t['intPoints'], $timestamp];
            foreach ($vals as $i => $v) $l_stmt->bindValue($i+1, $v);
            $l_stmt->execute();
        }

        $meta = $db->prepare("INSERT INTO live_table_metadata VALUES (?, ?, ?, ?, ?) ON CONFLICT(competition_code) DO UPDATE SET season_label=excluded.season_label, matchweek=excluded.matchweek, updated_at=excluded.updated_at");
        $meta->bindValue(1, $code); $meta->bindValue(2, $table_name); $meta->bindValue(3, $current_season); $meta->bindValue(4, $current_mw); $meta->bindValue(5, $timestamp);
        $meta->execute();
    }

    // STEP C: Process Seasons
    $seasons_json = json_decode(@file_get_contents("{$BASE_URL}search_all_seasons.php?id=$id"), true);
    if (!$seasons_json || !isset($seasons_json['seasons'])) return;

    foreach (array_reverse($seasons_json['seasons']) as $s_obj) {
        $season = $s_obj['strSeason'];
        if ((int)substr($season, 0, 4) < 1987) continue;

        // Optimization: Only skip if badges exist AND it's not the current season.
        // Always re-fetch both the API-reported current season AND the date-inferred one
        // so that end-of-season playoff results are picked up even when the live table
        // has rolled over.
        $check = $db->prepare("SELECT team_crest FROM league_table_snapshots WHERE competition_code = ? AND season_label = ? LIMIT 1");
        $check->bindValue(1, $code); $check->bindValue(2, $season);
        $res = $check->execute()->fetchArray(SQLITE3_ASSOC);

        $is_current = ($season === $current_season || $season === $date_based_season);
        if (!$is_current && $res !== false && !empty($res['team_crest'])) {
            echo "  -> Season $season: Cached. Skipping.\n";
            continue;
        }

        echo "  -> " . ($res === false ? "Reconstructing" : "Updating") . " Season: $season... ";

        $fixtures = json_decode(@file_get_contents("{$BASE_URL}eventsseason.php?id=$id&s=" . urlencode($season)), true);
        if (!$fixtures || empty($fixtures['events'])) { echo "No data.\n"; continue; }

        $db->exec("BEGIN TRANSACTION");
        $db->exec("DELETE FROM matches WHERE competition_code = '$code' AND season_label = '$season'");
        $db->exec("DELETE FROM league_table_snapshots WHERE competition_code = '$code' AND season_label = '$season'");

        $m_ins = $db->prepare("INSERT INTO matches (competition_code, season_label, matchweek, match_date, home_team, away_team, home_goals, away_goals, status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $mw_buckets = []; $running_stats = [];

        // Pre-process: remap intRound=0 (TheSportsDB playoff sentinel) to MW47+ by date order.
        // MW0 is reserved for the pre-season blank table. Matches already at intRound>46 are kept as-is.
        $playoff_zero_date_map = [];
        {
            $zero_dates = [];
            foreach ($fixtures['events'] as $e) {
                if ((int)$e['intRound'] === 0 && !empty($e['dateEvent'])) {
                    $zero_dates[$e['dateEvent']] = true;
                }
            }
            if (!empty($zero_dates)) {
                ksort($zero_dates);
                $mw_counter = 47;
                foreach (array_keys($zero_dates) as $d) {
                    $playoff_zero_date_map[$d] = $mw_counter++;
                }
                echo "  [Remapped " . count($zero_dates) . " playoff date(s) from MW0 to MW47+]\n";
            }
        }

        foreach ($fixtures['events'] as $e) {
            // New Badge Injection Point: Grabbing badges directly from the event payload
            if (!empty($e['strHomeTeamBadge'])) $crest_map[$e['strHomeTeam']] = $e['strHomeTeamBadge'];
            if (!empty($e['strAwayTeamBadge'])) $crest_map[$e['strAwayTeam']] = $e['strAwayTeamBadge'];

            if (!isset($running_stats[$e['strHomeTeam']])) $running_stats[$e['strHomeTeam']] = ['p'=>0,'w'=>0,'d'=>0,'l'=>0,'gf'=>0,'ga'=>0,'pts'=>0];
            if (!isset($running_stats[$e['strAwayTeam']])) $running_stats[$e['strAwayTeam']] = ['p'=>0,'w'=>0,'d'=>0,'l'=>0,'gf'=>0,'ga'=>0,'pts'=>0];

            $mw = (int)$e['intRound'];
            // Apply playoff remap: intRound=0 → MW47+ based on date
            if ($mw === 0 && !empty($e['dateEvent']) && isset($playoff_zero_date_map[$e['dateEvent']])) {
                $mw = $playoff_zero_date_map[$e['dateEvent']];
            }
            $hg = ($e['intHomeScore'] !== null) ? (int)$e['intHomeScore'] : null;
            $ag = ($e['intAwayScore'] !== null) ? (int)$e['intAwayScore'] : null;

            // Determine match status from TheSportsDB fields
            $apiStatus = strtolower(trim($e['strStatus'] ?? ''));
            if (!empty($e['strPostponed']) && strtolower($e['strPostponed']) === 'yes') {
                $matchStatus = 'postponed';
            } elseif (str_contains($apiStatus, 'postponed')) {
                $matchStatus = 'postponed';
            } elseif (str_contains($apiStatus, 'cancel')) {
                $matchStatus = 'cancelled';
            } elseif ($hg !== null && $ag !== null) {
                $matchStatus = 'played';
            } else {
                $matchStatus = 'scheduled';
            }

            $m_ins->bindValue(1, $code); $m_ins->bindValue(2, $season); $m_ins->bindValue(3, $mw);
            $m_ins->bindValue(4, $e['dateEvent']); $m_ins->bindValue(5, $e['strHomeTeam']);
            $m_ins->bindValue(6, $e['strAwayTeam']); $m_ins->bindValue(7, $hg);
            $m_ins->bindValue(8, $ag); $m_ins->bindValue(9, $matchStatus);
            $m_ins->bindValue(10, 'tsdb_v2_optimized');
            $m_ins->execute();

            if ($hg !== null && $ag !== null) {
                $mw_buckets[$mw][] = ['h'=>$e['strHomeTeam'], 'a'=>$e['strAwayTeam'], 'hg'=>$hg, 'ag'=>$ag];
            }
        }

        ksort($mw_buckets);
        $s_ins = $db->prepare("INSERT INTO league_table_snapshots VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Always insert blank pre-season snapshot at MW0 (reserved for pre-season; playoffs remapped to MW47+)
        if (!empty($running_stats)) {
            $pre_teams = array_keys($running_stats);
            sort($pre_teams);
            $pos = 1;
            foreach ($pre_teams as $name) {
                $badge = $crest_map[$name] ?? '';
                $vals = [$code, $season, 0, $badge, $name, $pos++, 0, 0, 0, 0, 0, 0, 0, 0, $timestamp, $timestamp];
                foreach ($vals as $i => $v) $s_ins->bindValue($i+1, $v);
                $s_ins->execute();
            }
            echo "  [Pre-season MW0 table created for $season]\n";
        }

        foreach ($mw_buckets as $mw => $m_list) {
            foreach ($m_list as $m) {
                $h = &$running_stats[$m['h']]; $a = &$running_stats[$m['a']];
                $h['p']++; $a['p']++; $h['gf']+=$m['hg']; $h['ga']+=$m['ag']; $a['gf']+=$m['ag']; $a['ga']+=$m['hg'];
                if ($m['hg'] > $m['ag']) { $h['w']++; $a['l']++; $h['pts']+=3; }
                elseif ($m['hg'] < $m['ag']) { $a['w']++; $h['l']++; $a['pts']+=3; }
                else { $h['d']++; $a['d']++; $h['pts']+=1; $a['pts']+=1; }
            }

            // Sort table for the current matchweek
            $temp_table = $running_stats;
            uasort($temp_table, function($x, $y) {
                if ($x['pts'] != $y['pts']) return $y['pts'] - $x['pts'];
                return ($y['gf'] - $y['ga']) - ($x['gf'] - $x['ga']);
            });

            $pos = 1;
            foreach ($temp_table as $name => $s) {
                $badge = $crest_map[$name] ?? '';
                $vals = [$code, $season, $mw, $badge, $name, $pos++, $s['p'], $s['w'], $s['d'], $s['l'], $s['gf'], $s['ga'], $s['gf']-$s['ga'], $s['pts'], $timestamp, $timestamp];
                foreach ($vals as $i => $v) $s_ins->bindValue($i+1, $v);
                $s_ins->execute();
            }
        }

        // --- Date-ordered snapshots (accounts for postponed matches played later) ---
        $db->exec("DELETE FROM league_table_snapshots_by_date WHERE competition_code = '$code' AND season_label = '$season'");

        $date_fixtures = [];
        foreach ($fixtures['events'] as $e) {
            $hg = ($e['intHomeScore'] !== null) ? (int)$e['intHomeScore'] : null;
            $ag = ($e['intAwayScore'] !== null) ? (int)$e['intAwayScore'] : null;
            if ($hg !== null && $ag !== null && !empty($e['dateEvent'])) {
                $date_fixtures[] = ['date' => $e['dateEvent'], 'h' => $e['strHomeTeam'], 'a' => $e['strAwayTeam'], 'hg' => $hg, 'ag' => $ag];
            }
        }
        usort($date_fixtures, fn($a, $b) => strcmp($a['date'], $b['date']));

        $date_buckets = [];
        foreach ($date_fixtures as $m) {
            $date_buckets[$m['date']][] = $m;
        }

        $date_running_stats = [];
        $ds_ins = $db->prepare("INSERT INTO league_table_snapshots_by_date VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($date_buckets as $snap_date => $matches) {
            foreach ($matches as $m) {
                if (!isset($date_running_stats[$m['h']])) $date_running_stats[$m['h']] = ['p'=>0,'w'=>0,'d'=>0,'l'=>0,'gf'=>0,'ga'=>0,'pts'=>0];
                if (!isset($date_running_stats[$m['a']])) $date_running_stats[$m['a']] = ['p'=>0,'w'=>0,'d'=>0,'l'=>0,'gf'=>0,'ga'=>0,'pts'=>0];
                $h = &$date_running_stats[$m['h']]; $a = &$date_running_stats[$m['a']];
                $h['p']++; $a['p']++; $h['gf']+=$m['hg']; $h['ga']+=$m['ag']; $a['gf']+=$m['ag']; $a['ga']+=$m['hg'];
                if ($m['hg'] > $m['ag']) { $h['w']++; $a['l']++; $h['pts']+=3; }
                elseif ($m['hg'] < $m['ag']) { $a['w']++; $h['l']++; $a['pts']+=3; }
                else { $h['d']++; $a['d']++; $h['pts']+=1; $a['pts']+=1; }
            }

            $temp_date = $date_running_stats;
            uasort($temp_date, function($x, $y) {
                if ($x['pts'] != $y['pts']) return $y['pts'] - $x['pts'];
                return ($y['gf'] - $y['ga']) - ($x['gf'] - $x['ga']);
            });

            $pos = 1;
            foreach ($temp_date as $name => $s) {
                $badge = $crest_map[$name] ?? '';
                $vals = [$code, $season, $snap_date, $badge, $name, $pos++, $s['p'], $s['w'], $s['d'], $s['l'], $s['gf'], $s['ga'], $s['gf']-$s['ga'], $s['pts'], $timestamp, $timestamp];
                foreach ($vals as $i => $v) $ds_ins->bindValue($i+1, $v);
                $ds_ins->execute();
            }
        }

        $db->exec("COMMIT");
        echo "Done.\n";
    }
}

// --- 3. Execution ---
$leagues = ['PL' => '4328', 'ELC' => '4329', 'L1' => '4396', 'L2' => '4397', 'NL' => '4590'];
foreach ($leagues as $code => $id) {
    sync_league($db, $BASE_URL, $code, $id);
}

echo "\n🏁 Optimized Sync Complete.\n";