<?php
/**
 * fetch-englishfootball.php
 *
 * Match data source : openfootball/eng-england (GitHub raw text files)
 *   https://github.com/openfootball/eng-england
 *   Covers all four Football League tiers back to 1888-89 with proper Matchday N headers.
 *
 * Live table source : TheSportsDB (current-season widget tables only)
 *
 * Competition code → tier file → era names:
 *   PL  (tier 1, 1888-)  First Division → Premier League (1992)
 *   ELC (tier 2, 1892-)  Second Division → Football League First Division (1992) → Championship (2004)
 *   L1  (tier 3, 1920-)  Third Division North/South → Third Division → League One (2004)
 *   L2  (tier 4, 1958-)  Fourth Division → Third Division (new) → League Two (2004)
 *   NL  – National League – TheSportsDB only (not in openfootball)
 *
 * Points rule: 2 pts per win before 1981-82; 3 pts per win from 1981-82 onwards.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');
set_time_limit(0);

echo "⚽ English Football Historical Sync (openfootball + TheSportsDB live tables)...\n";

$TSDB_KEY  = '876419';
$TSDB_BASE = "https://www.thesportsdb.com/api/v1/json/$TSDB_KEY/";
$OFB_BASE  = "https://raw.githubusercontent.com/openfootball/eng-england/master/";

$db = new SQLite3('football-stats.sqlite3');

// ─── 1. Schema ────────────────────────────────────────────────────────────────

$tables = [
    "league_table_PL"  => "team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER",
    "league_table_ELC" => "team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER",
    "league_table_L1"  => "team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER",
    "league_table_L2"  => "team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER",
    "league_table_NL"  => "team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER",
    "matches"          => "id INTEGER PRIMARY KEY AUTOINCREMENT, competition_code TEXT, competition_name TEXT, season_label TEXT, matchweek INTEGER, match_date TEXT, home_team TEXT, away_team TEXT, home_goals INTEGER, away_goals INTEGER, home_pens INTEGER, away_pens INTEGER, status TEXT, source TEXT",
    "league_table_snapshots"         => "competition_code TEXT, competition_name TEXT, season_label TEXT, matchweek INTEGER, team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, source_updated_at INTEGER, archived_at INTEGER, PRIMARY KEY (competition_code, season_label, matchweek, team_name)",
    "league_table_snapshots_by_date" => "competition_code TEXT, competition_name TEXT, season_label TEXT, snapshot_date TEXT, team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, source_updated_at INTEGER, archived_at INTEGER, PRIMARY KEY (competition_code, season_label, snapshot_date, team_name)",
    "live_table_metadata"            => "competition_code TEXT PRIMARY KEY, live_table_name TEXT NOT NULL, season_label TEXT NOT NULL, matchweek INTEGER NOT NULL, updated_at INTEGER NOT NULL",
];

foreach ($tables as $tname => $schema) {
    $db->exec("CREATE TABLE IF NOT EXISTS $tname ($schema)");
}

// Migrate existing tables (no-op if column already present)
$migrate = [
    'matches'                         => ['home_pens INTEGER', 'away_pens INTEGER', 'status TEXT', 'competition_name TEXT'],
    'league_table_snapshots'          => ['competition_name TEXT'],
    'league_table_snapshots_by_date'  => ['competition_name TEXT'],
];
foreach ($migrate as $tbl => $cols) {
    foreach ($cols as $col) {
        @$db->exec("ALTER TABLE $tbl ADD COLUMN $col");
    }
}

// ─── 2. Pure functions ────────────────────────────────────────────────────────

function season_str(int $year): string {
    return $year . '-' . sprintf('%02d', ($year + 1) % 100);
}

function era_name(string $code, int $year): string {
    return match ($code) {
        'PL'  => $year < 1992 ? 'Football League First Division'  : 'Premier League',
        'ELC' => $year < 1992 ? 'Football League Second Division' : ($year < 2004 ? 'Football League First Division'  : 'Championship'),
        'L1'  => $year < 1992 ? 'Football League Third Division'  : ($year < 2004 ? 'Football League Second Division' : 'League One'),
        'L2'  => $year < 1992 ? 'Football League Fourth Division' : ($year < 2004 ? 'Football League Third Division'  : 'League Two'),
        'NL'  => 'National League',
        default => $code,
    };
}

/** Win points: 2 pts per win before 1981-82, 3 pts from 1981-82 onwards. */
function win_pts(int $year): int {
    return $year >= 1981 ? 3 : 2;
}

function fetch_url(string $url): ?string {
    static $cache = [];
    if (array_key_exists($url, $cache)) return $cache[$url];
    $ctx  = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) return $cache[$url] = null;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('/^HTTP\/\S+\s+4\d\d/', $h)) return $cache[$url] = null;
    }
    return $cache[$url] = ($body ?: null);
}

const MONTH_MAP = ['Jan'=>1,'Feb'=>2,'Mar'=>3,'Apr'=>4,'May'=>5,'Jun'=>6,
                   'Jul'=>7,'Aug'=>8,'Sep'=>9,'Oct'=>10,'Nov'=>11,'Dec'=>12];

function parse_ofb_date(string $raw, int $season_year): ?string {
    $s = preg_replace('/^[A-Z][a-z]{1,2}\s+/', '', trim($raw)); // strip weekday
    $mon = null; $day = null;

    if (preg_match('/([A-Z][a-z]+)[\/\s](\d{1,2})/', $s, $m)) {
        $mon = MONTH_MAP[$m[1]] ?? null; $day = (int)$m[2];
    } elseif (preg_match('/(\d{1,2})[\/\s]([A-Z][a-z]+)/', $s, $m)) {
        $day = (int)$m[1]; $mon = MONTH_MAP[$m[2]] ?? null;
    } elseif (preg_match('/(\d{1,2})\/(\d{1,2})/', $s, $m)) {
        // Could be d/m or m/d — assume d/m for British sources
        $day = (int)$m[1]; $mon = (int)$m[2];
    }

    if (!$mon || !$day) return null;
    $year = ($mon >= 7) ? $season_year : $season_year + 1;
    return sprintf('%04d-%02d-%02d', $year, $mon, $day);
}

/**
 * Parse an openfootball text file into [ matchweek => [ match, ... ] ]
 *
 * Supported line patterns:
 *   Matchday N          – sets current matchweek
 *   [Mon Aug/17]        – standalone date
 *   [Mon Aug/17]  Home  N-N  Away   – inline date + match
 *     Home  N-N  Away             – indented match (after standalone date)
 *     Home  ?-?  Away             – scheduled match
 */
function parse_ofb_text(string $text, int $season_year): array {
    $rounds       = [];
    $current_mw   = null;
    $current_date = null;

    foreach (explode("\n", $text) as $raw) {
        $line = rtrim($raw);
        if (str_starts_with(ltrim($line), '#') || trim($line) === '') continue;

        // ── Matchday / Round header ──────────────────────────────────────────
        if (preg_match('/^(?:Matchday|Round)\s+(\d+)/i', $line, $m)) {
            $current_mw = (int)$m[1];
            continue;
        }

        // ── Standalone date line: [Mon Aug/17] ──────────────────────────────
        if (preg_match('/^\[([^\]]+)\]\s*$/', $line, $m)) {
            $current_date = parse_ofb_date($m[1], $season_year);
            continue;
        }

        if ($current_mw === null) continue;

        // ── Inline date + played match: [Mon Aug/17]  Home  N-N  Away ───────
        if (preg_match('/^\[([^\]]+)\]\s+(.+?)\s{2,}(\d{1,3})-(\d{1,3})(?:\s*(?:aet|pen\.?))?\s{2,}(.+?)\s*$/', $line, $m)) {
            $current_date = parse_ofb_date($m[1], $season_year);
            $rounds[$current_mw][] = [
                'home' => trim($m[2]), 'away' => trim($m[5]),
                'hg' => (int)$m[3], 'ag' => (int)$m[4],
                'date' => $current_date, 'status' => 'played',
            ];
            continue;
        }
        // ── Inline date + scheduled match: [Mon Aug/17]  Home  ?-?  Away ────
        if (preg_match('/^\[([^\]]+)\]\s+(.+?)\s{2,}\?-\?\s{2,}(.+?)\s*$/', $line, $m)) {
            $current_date = parse_ofb_date($m[1], $season_year);
            $rounds[$current_mw][] = [
                'home' => trim($m[2]), 'away' => trim($m[3]),
                'hg' => null, 'ag' => null,
                'date' => $current_date, 'status' => 'scheduled',
            ];
            continue;
        }

        // ── Indented played match ────────────────────────────────────────────
        if (preg_match('/^\s{2,}(.+?)\s{2,}(\d{1,3})-(\d{1,3})(?:\s*(?:aet|pen\.?))?\s{2,}(.+?)\s*$/', $line, $m)) {
            $rounds[$current_mw][] = [
                'home' => trim($m[1]), 'away' => trim($m[4]),
                'hg' => (int)$m[2], 'ag' => (int)$m[3],
                'date' => $current_date, 'status' => 'played',
            ];
            continue;
        }
        // ── Indented scheduled match ─────────────────────────────────────────
        if (preg_match('/^\s{2,}(.+?)\s{2,}\?-\?\s{2,}(.+?)\s*$/', $line, $m)) {
            $rounds[$current_mw][] = [
                'home' => trim($m[1]), 'away' => trim($m[2]),
                'hg' => null, 'ag' => null,
                'date' => $current_date, 'status' => 'scheduled',
            ];
            continue;
        }
    }

    return $rounds;
}

// ─── 3. Table reconstruction helpers ─────────────────────────────────────────

function sort_table(array $stats): array {
    uasort($stats, function ($x, $y) {
        if ($x['pts'] !== $y['pts']) return $y['pts'] - $x['pts'];
        $xgd = $x['gf'] - $x['ga'];
        $ygd = $y['gf'] - $y['ga'];
        if ($xgd !== $ygd) return $ygd - $xgd;
        return $y['gf'] - $x['gf'];
    });
    return $stats;
}

function apply_result(array &$stats, string $home, string $away, int $hg, int $ag, int $win_pts): void {
    foreach ([$home, $away] as $t) {
        if (!isset($stats[$t])) $stats[$t] = ['p'=>0,'w'=>0,'d'=>0,'l'=>0,'gf'=>0,'ga'=>0,'pts'=>0];
    }
    $h = &$stats[$home]; $a = &$stats[$away];
    $h['p']++; $a['p']++;
    $h['gf'] += $hg; $h['ga'] += $ag;
    $a['gf'] += $ag; $a['ga'] += $hg;
    if ($hg > $ag)      { $h['w']++; $a['l']++; $h['pts'] += $win_pts; }
    elseif ($hg < $ag)  { $a['w']++; $h['l']++; $a['pts'] += $win_pts; }
    else                { $h['d']++; $a['d']++; $h['pts']++; $a['pts']++; }
}

// ─── 4. Main sync function (openfootball → DB) ────────────────────────────────

function sync_ofb_league(
    SQLite3 $db,
    string $OFB_BASE,
    string $code,
    int    $tier,
    int    $start_year,
    string $current_season
): void {
    $cm = (int)date('n'); $cy = (int)date('Y');
    $end_year = ($cm >= 7) ? $cy : $cy - 1;

    for ($year = $start_year; $year <= $end_year; $year++) {
        $season    = season_str($year);
        $comp_name = era_name($code, $year);
        $is_current = ($season === $current_season);

        // Skip if already cached (historical seasons only)
        if (!$is_current) {
            $n = $db->querySingle("SELECT COUNT(*) FROM league_table_snapshots WHERE competition_code='$code' AND season_label='$season'");
            if ($n > 0) {
                echo "    $season [$comp_name]: cached.\n";
                continue;
            }
        }

        // Try primary file; for split-era 3rd division (pre-1958) fall back to 3n (North)
        $filename = "$tier-eng.txt";
        $url  = "{$OFB_BASE}{$season}/{$filename}";
        $text = fetch_url($url);

        if ($text === null && $tier === 3 && $year < 1958) {
            $text = fetch_url("{$OFB_BASE}{$season}/3n-eng.txt");
            if ($text !== null) $comp_name = 'Football League Third Division North';
        }

        if ($text === null) {
            echo "    $season [$comp_name]: no file.\n";
            continue;
        }

        $rounds = parse_ofb_text($text, $year);
        if (empty($rounds)) {
            echo "    $season [$comp_name]: parsed 0 matchdays — skipping.\n";
            continue;
        }

        echo "    $season [$comp_name]: " . count($rounds) . " matchdays... ";

        $timestamp = round(microtime(true) * 1000);
        $wp        = win_pts($year);

        $db->exec("BEGIN TRANSACTION");
        $db->exec("DELETE FROM matches WHERE competition_code='$code' AND season_label='$season'");
        $db->exec("DELETE FROM league_table_snapshots WHERE competition_code='$code' AND season_label='$season'");
        $db->exec("DELETE FROM league_table_snapshots_by_date WHERE competition_code='$code' AND season_label='$season'");

        $m_ins = $db->prepare(
            "INSERT INTO matches (competition_code, competition_name, season_label, matchweek, match_date, home_team, away_team, home_goals, away_goals, status, source)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $running_stats = [];
        $mw_buckets    = [];

        ksort($rounds);

        foreach ($rounds as $mw => $matches) {
            foreach ($matches as $mx) {
                $m_ins->bindValue(1, $code);      $m_ins->bindValue(2, $comp_name);
                $m_ins->bindValue(3, $season);    $m_ins->bindValue(4, $mw);
                $m_ins->bindValue(5, $mx['date']); $m_ins->bindValue(6, $mx['home']);
                $m_ins->bindValue(7, $mx['away']); $m_ins->bindValue(8, $mx['hg']);
                $m_ins->bindValue(9, $mx['ag']);   $m_ins->bindValue(10, $mx['status']);
                $m_ins->bindValue(11, 'openfootball');
                $m_ins->execute();

                // Ensure teams appear in running_stats even before they play
                foreach ([$mx['home'], $mx['away']] as $t) {
                    if (!isset($running_stats[$t])) $running_stats[$t] = ['p'=>0,'w'=>0,'d'=>0,'l'=>0,'gf'=>0,'ga'=>0,'pts'=>0];
                }

                if ($mx['hg'] !== null && $mx['ag'] !== null) {
                    $mw_buckets[$mw][] = $mx;
                }
            }
        }

        // Snapshot insertion
        $s_ins = $db->prepare(
            "INSERT INTO league_table_snapshots
             (competition_code, competition_name, season_label, matchweek, team_crest, team_name, position, played, won, drawn, lost, gf, ga, gd, points, source_updated_at, archived_at)
             VALUES (?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $snap = function (int $mw, array $stats) use ($s_ins, $code, $comp_name, $season, $timestamp): void {
            $sorted = sort_table($stats);
            $pos = 1;
            foreach ($sorted as $name => $s) {
                $s_ins->bindValue(1, $code);   $s_ins->bindValue(2, $comp_name);
                $s_ins->bindValue(3, $season); $s_ins->bindValue(4, $mw);
                $s_ins->bindValue(5, $name);   $s_ins->bindValue(6, $pos++);
                $s_ins->bindValue(7,  $s['p']); $s_ins->bindValue(8,  $s['w']);
                $s_ins->bindValue(9,  $s['d']); $s_ins->bindValue(10, $s['l']);
                $s_ins->bindValue(11, $s['gf']); $s_ins->bindValue(12, $s['ga']);
                $s_ins->bindValue(13, $s['gf'] - $s['ga']); $s_ins->bindValue(14, $s['pts']);
                $s_ins->bindValue(15, $timestamp); $s_ins->bindValue(16, $timestamp);
                $s_ins->execute();
            }
        };

        // MW 0: blank pre-season table
        $snap(0, $running_stats);

        foreach ($mw_buckets as $mw => $m_list) {
            foreach ($m_list as $mx) {
                apply_result($running_stats, $mx['home'], $mx['away'], $mx['hg'], $mx['ag'], $wp);
            }
            $snap($mw, $running_stats);
        }

        // Date-ordered snapshots
        $date_fixtures = [];
        foreach ($rounds as $mw => $m_list) {
            foreach ($m_list as $mx) {
                if ($mx['hg'] !== null && !empty($mx['date'])) {
                    $date_fixtures[] = $mx;
                }
            }
        }
        usort($date_fixtures, fn($a, $b) => strcmp($a['date'], $b['date']));
        $date_buckets = [];
        foreach ($date_fixtures as $mx) $date_buckets[$mx['date']][] = $mx;

        $ds_ins = $db->prepare(
            "INSERT INTO league_table_snapshots_by_date
             (competition_code, competition_name, season_label, snapshot_date, team_crest, team_name, position, played, won, drawn, lost, gf, ga, gd, points, source_updated_at, archived_at)
             VALUES (?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $date_stats = [];
        foreach ($date_buckets as $snap_date => $matches) {
            foreach ($matches as $mx) {
                apply_result($date_stats, $mx['home'], $mx['away'], $mx['hg'], $mx['ag'], $wp);
            }
            $sorted = sort_table($date_stats);
            $pos = 1;
            foreach ($sorted as $name => $s) {
                $ds_ins->bindValue(1, $code);       $ds_ins->bindValue(2, $comp_name);
                $ds_ins->bindValue(3, $season);     $ds_ins->bindValue(4, $snap_date);
                $ds_ins->bindValue(5, $name);       $ds_ins->bindValue(6, $pos++);
                $ds_ins->bindValue(7,  $s['p']);    $ds_ins->bindValue(8,  $s['w']);
                $ds_ins->bindValue(9,  $s['d']);    $ds_ins->bindValue(10, $s['l']);
                $ds_ins->bindValue(11, $s['gf']);   $ds_ins->bindValue(12, $s['ga']);
                $ds_ins->bindValue(13, $s['gf'] - $s['ga']); $ds_ins->bindValue(14, $s['pts']);
                $ds_ins->bindValue(15, $timestamp); $ds_ins->bindValue(16, $timestamp);
                $ds_ins->execute();
            }
        }

        $db->exec("COMMIT");
        echo "done.\n";
    }
}

// ─── 5. Live table sync (TheSportsDB, current season only) ───────────────────

function sync_live_table(SQLite3 $db, string $TSDB_BASE, string $code, string $tsdb_id): ?string {
    $timestamp  = round(microtime(true) * 1000);
    $crest_map  = [];

    $teams_json = json_decode(@file_get_contents("{$TSDB_BASE}search_all_teams.php?l=" . urlencode($code)), true);
    if ($teams_json && isset($teams_json['teams'])) {
        foreach ($teams_json['teams'] as $t) {
            if (!empty($t['strTeamBadge'])) $crest_map[$t['strTeam']] = $t['strTeamBadge'];
        }
    }

    $live = json_decode(@file_get_contents("{$TSDB_BASE}lookuptable.php?l=$tsdb_id"), true);
    if (!$live || empty($live['table'])) { echo "    (live table unavailable)\n"; return null; }

    $table_name  = "league_table_$code";
    $current_mw  = max(array_column($live['table'], 'intPlayed'));
    $cur_season  = $live['table'][0]['strSeason'];

    $db->exec("DELETE FROM $table_name");
    $stmt = $db->prepare("INSERT INTO $table_name VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($live['table'] as $t) {
        $name  = $t['strTeam'];
        $badge = $t['strBadge'] ?? ($crest_map[$name] ?? '');
        $crest_map[$name] = $badge;
        $v = [$badge, $name, $t['intRank'], $t['intPlayed'], $t['intWin'], $t['intDraw'],
              $t['intLoss'], $t['intGoalsFor'], $t['intGoalsAgainst'], $t['intGoalDifference'],
              $t['intPoints'], $timestamp];
        foreach ($v as $i => $val) $stmt->bindValue($i + 1, $val);
        $stmt->execute();
    }

    $meta = $db->prepare(
        "INSERT INTO live_table_metadata VALUES (?, ?, ?, ?, ?)
         ON CONFLICT(competition_code) DO UPDATE SET
           season_label=excluded.season_label, matchweek=excluded.matchweek, updated_at=excluded.updated_at"
    );
    $meta->bindValue(1, $code); $meta->bindValue(2, $table_name);
    $meta->bindValue(3, $cur_season); $meta->bindValue(4, $current_mw); $meta->bindValue(5, $timestamp);
    $meta->execute();

    echo "    Live table: $cur_season, MW $current_mw.\n";
    return $cur_season;
}

// ─── 6. Execute ───────────────────────────────────────────────────────────────

// Date-derived fallback current season
$cm = (int)date('n'); $cy = (int)date('Y');
$fallback_season = ($cm >= 7) ? season_str($cy) : season_str($cy - 1);

$league_config = [
    // code => [ tsdb_id, tier_file_num, start_year ]
    'PL'  => ['4328', 1, 1888],
    'ELC' => ['4329', 2, 1892],
    'L1'  => ['4396', 3, 1920],
    'L2'  => ['4397', 4, 1958],
];

foreach ($league_config as $code => [$tsdb_id, $tier, $start]) {
    echo "\n[League: $code]\n";

    echo "  -> Live table (TheSportsDB)...\n";
    $cur = sync_live_table($db, $TSDB_BASE, $code, $tsdb_id);
    $current_season = $cur ?? $fallback_season;

    echo "  -> Historical matches (openfootball, {$start}-current)...\n";
    sync_ofb_league($db, $OFB_BASE, $code, $tier, $start, $current_season);
}

// National League: TheSportsDB only (not in openfootball)
echo "\n[League: NL]\n";
echo "  -> Live table (TheSportsDB)...\n";
sync_live_table($db, $TSDB_BASE, 'NL', '4590');

echo "\n🏁 Sync complete.\n";
