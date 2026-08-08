<?php
/**
 * fetch-worldfootball.php
 * UPDATED: Full 2026 World Cup logic including wc_standings, Best 3rd Place,
 *          wc_teams and wc_bracket_sides lookup tables.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

echo "⚽ Initializing Global Football Sync (2026 World Cup Ready)...\n";

// --- Configuration ---
$API_KEY  = '2467baa7f4d747f5b7d2d99498a70172';
$BASE_URL = "https://api.football-data.org/v4/";

try {
    $db = new PDO('sqlite:world-cup-stats.sqlite3');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ============================================================
// 1. DATABASE SCHEMA
// ============================================================

$db->exec("CREATE TABLE IF NOT EXISTS wc_groups (
    group_name TEXT, team_name TEXT, position INTEGER, played INTEGER,
    won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER,
    gd INTEGER, points INTEGER, team_crest TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS wc_third_place (
    group_name TEXT, team_name TEXT, rank INTEGER, played INTEGER,
    won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER,
    gd INTEGER, points INTEGER, qualified INTEGER, updated_at INTEGER
)");

$db->exec("CREATE TABLE IF NOT EXISTS wc_standings (
    team_name TEXT, stage TEXT, rank INTEGER, played INTEGER,
    won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER,
    gd INTEGER, points INTEGER, updated_at INTEGER
)");

$db->exec("CREATE TABLE IF NOT EXISTS wc_knockout (
    stage      TEXT,
    match_id   INTEGER PRIMARY KEY,
    match_date TEXT,
    home_team  TEXT,
    away_team  TEXT,
    home_score INTEGER,
    away_score INTEGER,
    status     TEXT,
    venue      TEXT,
    updated_at INTEGER
)");

$db->exec("CREATE TABLE IF NOT EXISTS wc_teams (
    team_name  TEXT PRIMARY KEY,
    pot        INTEGER NOT NULL DEFAULT 2,
    is_host    INTEGER NOT NULL DEFAULT 0,
    flag_emoji TEXT    NOT NULL DEFAULT '🏴',
    group_name TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS wc_bracket_sides (
    group_name   TEXT PRIMARY KEY,
    bracket_side TEXT NOT NULL
)");

// Also store the official R32 slot definitions so knockout.php can resolve
// team names without hardcoding them there too.
// home_pos/away_pos: 1=winner, 2=runner-up, 3=best 3rd (ranked)
$db->exec("CREATE TABLE IF NOT EXISTS wc_r32_slots (
    match_id   INTEGER PRIMARY KEY,
    home_group TEXT NOT NULL,   -- group name, or 'THIRD' for best-3rd slots
    home_pos   INTEGER NOT NULL,
    away_group TEXT NOT NULL,
    away_pos   INTEGER NOT NULL
)");

// ============================================================
// 2. SEED STATIC LOOKUP TABLES
// ============================================================

echo "\n[0/2] Seeding static lookup tables...\n";

$db->exec("INSERT OR IGNORE INTO wc_teams
    (team_name, pot, is_host, flag_emoji, group_name) VALUES
    ('Spain',        1, 0, '🇪🇸', 'Group H'),
    ('France',       1, 0, '🇫🇷', 'Group I'),
    ('England',      1, 0, '🏴󠁧󠁢󠁥󠁮󠁧󠁿', 'Group L'),
    ('Argentina',    1, 0, '🇦🇷', 'Group J'),
    ('Brazil',       1, 0, '🇧🇷', 'Group C'),
    ('Portugal',     1, 0, '🇵🇹', 'Group K'),
    ('Netherlands',  1, 0, '🇳🇱', 'Group F'),
    ('Belgium',      1, 0, '🇧🇪', 'Group G'),
    ('Germany',      1, 0, '🇩🇪', 'Group E'),
    ('United States',          2, 1, '🇺🇸', 'Group D'),
    ('Mexico',       2, 1, '🇲🇽', 'Group A'),
    ('Canada',       2, 1, '🇨🇦', 'Group B')
");

$db->exec("INSERT OR IGNORE INTO wc_bracket_sides
    (group_name, bracket_side) VALUES
    ('Group A', 'golden'),
    ('Group B', 'golden'),
    ('Group C', 'golden'),
    ('Group D', 'death'),
    ('Group E', 'death'),
    ('Group F', 'death'),
    ('Group G', 'death'),
    ('Group H', 'death'),
    ('Group I', 'death'),
    ('Group J', 'golden'),
    ('Group K', 'golden'),
    ('Group L', 'golden')
");

// Official 2026 R32 fixture slot definitions.
// THIRD + rank means "the Nth best 3rd-place team" per FIFA seeding rules.
$db->exec("INSERT OR IGNORE INTO wc_r32_slots
    (match_id, home_group, home_pos, away_group, away_pos) VALUES
    (73,  'Group A', 2, 'Group B',  2),
    (74,  'Group E', 1, 'THIRD',    1),
    (75,  'Group F', 1, 'Group C',  2),
    (76,  'Group C', 1, 'Group F',  2),
    (77,  'Group I', 1, 'THIRD',    2),
    (78,  'Group E', 2, 'Group I',  2),
    (79,  'Group A', 1, 'THIRD',    3),
    (80,  'Group L', 1, 'THIRD',    4),
    (81,  'Group D', 1, 'THIRD',    5),
    (82,  'Group G', 1, 'THIRD',    6),
    (83,  'Group K', 2, 'Group L',  2),
    (84,  'Group H', 1, 'Group J',  2),
    (85,  'Group B', 1, 'THIRD',    7),
    (86,  'Group J', 1, 'Group H',  2),
    (87,  'Group K', 1, 'THIRD',    8),
    (88,  'Group D', 2, 'Group G',  2)
");

echo "  ✓ Lookup tables ready.\n";

// ============================================================
// 3. HELPER FUNCTION
// ============================================================

function get_api_data(string $url, string $key): ?array
{
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "X-Auth-Token: $key\r\n"
        ]
    ];
    $context  = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    return $response ? json_decode($response, true) : null;
}

// ============================================================
// 4. WORLD CUP STANDINGS & GROUPS
// ============================================================

echo "\n[1/2] Fetching World Cup Standings...\n";

$url_wc  = "{$BASE_URL}competitions/WC/standings";
$data_wc = get_api_data($url_wc, $API_KEY);

if (!$data_wc || !isset($data_wc['standings'])) {
    echo "  ✗ Failed to fetch standings. Skipping.\n";
} else {
    $db->exec("DELETE FROM wc_groups");
    $db->exec("DELETE FROM wc_third_place");
    $db->exec("DELETE FROM wc_standings");
    $timestamp = time() * 1000;

    $stmt_groups    = $db->prepare("INSERT INTO wc_groups VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_third     = $db->prepare("INSERT INTO wc_third_place VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_standings = $db->prepare("INSERT INTO wc_standings VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($data_wc['standings'] as $standing) {
        if ($standing['type'] !== 'TOTAL') {
            continue;
        }

        // API returns 'GROUP_A' — normalise to 'Group A'
        $group_name = 'Group ' . strtoupper(substr(str_replace('GROUP_', '', $standing['group']), -1));

        foreach ($standing['table'] as $team) {
            $stmt_groups->execute([
                $group_name,
                $team['team']['name'],
                (int)$team['position'],
                (int)$team['playedGames'],
                (int)$team['won'],
                (int)$team['draw'],
                (int)$team['lost'],
                (int)$team['goalsFor'],
                (int)$team['goalsAgainst'],
                (int)$team['goalDifference'],
                (int)$team['points'],
                $team['team']['crest'] ?? '',
            ]);

            $stmt_standings->execute([
                $team['team']['name'],
                $group_name,
                (int)$team['position'],
                (int)$team['playedGames'],
                (int)$team['won'],
                (int)$team['draw'],
                (int)$team['lost'],
                (int)$team['goalsFor'],
                (int)$team['goalsAgainst'],
                (int)$team['goalDifference'],
                (int)$team['points'],
                $timestamp,
            ]);

            if ((int)$team['position'] === 3) {
                $stmt_third->execute([
                    $group_name,
                    $team['team']['name'],
                    0,
                    (int)$team['playedGames'],
                    (int)$team['won'],
                    (int)$team['draw'],
                    (int)$team['lost'],
                    (int)$team['goalsFor'],
                    (int)$team['goalsAgainst'],
                    (int)$team['goalDifference'],
                    (int)$team['points'],
                    0,
                    $timestamp,
                ]);
            }
        }
    }

    echo "  ✓ All group and overall standings tables synced.\n";

    echo "  -> Ranking best 3rd-place teams...\n";

    $candidates  = $db->query(
        "SELECT * FROM wc_third_place ORDER BY points DESC, gd DESC, gf DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $update_stmt = $db->prepare(
        "UPDATE wc_third_place SET rank = ?, qualified = ? WHERE team_name = ?"
    );

    $rank = 1;
    foreach ($candidates as $c) {
        $qualified = ($rank <= 8) ? 1 : 0;
        $update_stmt->execute([$rank, $qualified, $c['team_name']]);
        echo "     $rank. {$c['team_name']} " . ($qualified ? "✅ Q" : "❌") . "\n";
        $rank++;
    }
}

// ============================================================
// 5. KNOCKOUT BRACKET
// ============================================================

echo "\n[2/2] Syncing Knockout Matches...\n";

$knockout_stages = [
    'LAST_32',
    'LAST_16',
    'QUARTER_FINALS',
    'SEMI_FINALS',
    'THIRD_PLACE',
    'FINAL',
];

$url_matches  = "{$BASE_URL}competitions/WC/matches";
$matches_data = get_api_data($url_matches, $API_KEY);

if (!$matches_data || !isset($matches_data['matches'])) {
    echo "  ✗ Failed to fetch matches. Skipping.\n";
} else {
    $db->beginTransaction();

    try {
        // Use INSERT OR REPLACE so re-runs update existing rows rather than
        // deleting everything first — preserves any resolved team names while
        // keeping scores and statuses current.
        $stmt_k = $db->prepare(
            "INSERT OR REPLACE INTO wc_knockout
                (stage, match_id, match_date, home_team, away_team,
                 home_score, away_score, status, venue, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $count = 0;
        foreach ($matches_data['matches'] as $m) {
            if (!in_array($m['stage'], $knockout_stages, true)) {
                continue;
            }

            $stmt_k->execute([
                $m['stage'],
                $m['id'],
                substr($m['utcDate'], 0, 10),
                $m['homeTeam']['name'] ?? 'TBD',
                $m['awayTeam']['name'] ?? 'TBD',
                $m['score']['fullTime']['home'] ?? null,
                $m['score']['fullTime']['away'] ?? null,
                $m['status'],
                (is_array($m['venue'] ?? null)
                    ? ($m['venue']['name'] ?? 'TBD')
                    : ($m['venue'] ?? 'TBD')),
                time(),
            ]);

            $count++;
        }

        $db->commit();
        echo "  ✓ Knockout bracket updated ($count matches).\n";

    } catch (PDOException $e) {
        $db->rollBack();
        echo "  ✗ Knockout insert failed, rolled back: " . $e->getMessage() . "\n";
    }
}

echo "\n🏁 Data sync complete! Your World Cup database is up to date.\n";