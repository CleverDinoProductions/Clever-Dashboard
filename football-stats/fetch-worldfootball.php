<?php
/**
 * fetch-worldfootball.php
 * UPDATED: Full 2026 World Cup logic including wc_standings and Best 3rd Place[cite: 2]
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

echo "⚽ Initializing Global Football Sync (2026 World Cup Ready)...\n";

// --- Configuration ---
$API_KEY  = '2467baa7f4d747f5b7d2d99498a70172'; //[cite: 2]
$BASE_URL = "https://api.football-data.org/v4/";

try {
    $db = new PDO('sqlite:world-cup-stats.sqlite3');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// --- 1. Database Schema (World Cup specific)[cite: 2] ---

// Group Standings
$db->exec("CREATE TABLE IF NOT EXISTS wc_groups (
    group_name TEXT, team_name TEXT, position INTEGER, played INTEGER, 
    won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, 
    gd INTEGER, points INTEGER, team_crest TEXT
)");

// Specialized 3rd Place Table for 2026 Round of 32 Tracking[cite: 2]
$db->exec("CREATE TABLE IF NOT EXISTS wc_third_place (
    group_name TEXT, team_name TEXT, rank INTEGER, played INTEGER, 
    won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, 
    gd INTEGER, points INTEGER, qualified INTEGER, updated_at INTEGER
)");

// NEW: Overall Standings Table[cite: 2]
$db->exec("CREATE TABLE IF NOT EXISTS wc_standings (
    team_name TEXT, stage TEXT, rank INTEGER, played INTEGER, 
    won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, 
    gd INTEGER, points INTEGER, updated_at INTEGER
)");

// Knockout Bracket
$db->exec("CREATE TABLE IF NOT EXISTS wc_knockout (
    stage TEXT, match_number INTEGER, match_date TEXT, home_team TEXT, 
    away_team TEXT, home_score INTEGER, away_score INTEGER, status TEXT, 
    venue TEXT, updated_at INTEGER
)");

// --- 2. Helper Function ---

function get_api_data($url, $key) {
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "X-Auth-Token: $key\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    return $response ? json_decode($response, true) : null;
}

// --- 3. Execution: World Cup Standings & Groups[cite: 2] ---

echo "\n[1/2] Fetching World Cup Standings...\n";
$url_wc = "{$BASE_URL}competitions/WC/standings";
$data_wc = get_api_data($url_wc, $API_KEY);

if ($data_wc && isset($data_wc['standings'])) {
    $db->exec("DELETE FROM wc_groups");
    $db->exec("DELETE FROM wc_third_place");
    $db->exec("DELETE FROM wc_standings");
    $timestamp = time() * 1000;

    $stmt_groups    = $db->prepare("INSERT INTO wc_groups VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_third     = $db->prepare("INSERT INTO wc_third_place VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_standings = $db->prepare("INSERT INTO wc_standings VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($data_wc['standings'] as $standing) {
        if ($standing['type'] === 'TOTAL') {
            $group_name = str_replace('_', ' ', $standing['group']);
            
            foreach ($standing['table'] as $team) {
                // 1. Sync to wc_groups
                $stmt_groups->execute([
                    $group_name, $team['team']['name'], (int)$team['position'],
                    (int)$team['playedGames'], (int)$team['won'], (int)$team['draw'],
                    (int)$team['lost'], (int)$team['goalsFor'], (int)$team['goalsAgainst'],
                    (int)$team['goalDifference'], (int)$team['points'], $team['team']['crest']
                ]);

                // 2. Sync to wc_standings (Overall Tracking)[cite: 2]
                $stmt_standings->execute([
                    $team['team']['name'], $group_name, (int)$team['position'],
                    (int)$team['playedGames'], (int)$team['won'], (int)$team['draw'],
                    (int)$team['lost'], (int)$team['goalsFor'], (int)$team['goalsAgainst'],
                    (int)$team['goalDifference'], (int)$team['points'], $timestamp
                ]);

                // 3. Track 3rd Place for Round of 32 Qualification[cite: 2]
                if ((int)$team['position'] === 3) {
                    $stmt_third->execute([
                        $group_name, $team['team']['name'], 0,
                        (int)$team['playedGames'], (int)$team['won'], (int)$team['draw'],
                        (int)$team['lost'], (int)$team['goalsFor'], (int)$team['goalsAgainst'],
                        (int)$team['goalDifference'], (int)$team['points'], 0, $timestamp
                    ]);
                }
            }
        }
    }
    echo "  ✓ All group and overall standings tables synced.\n";

    // --- 4. Calculate Best 3rd Place Rankings[cite: 2] ---
    echo " -> Ranking Best 3rd Place Teams...\n";
    $candidates = $db->query("SELECT * FROM wc_third_place ORDER BY points DESC, gd DESC, gf DESC")->fetchAll(PDO::FETCH_ASSOC);

    $rank = 1;
    $update_stmt = $db->prepare("UPDATE wc_third_place SET rank = ?, qualified = ? WHERE team_name = ?");
    foreach ($candidates as $c) {
        $qualified = ($rank <= 8) ? 1 : 0; // Top 8 in 2026 48-team format[cite: 2]
        $update_stmt->execute([$rank, $qualified, $c['team_name']]);
        echo "     $rank. {$c['team_name']} " . ($qualified ? "✅ Q" : "❌") . "\n";
        $rank++;
    }
}

// --- 5. Sync Knockout Bracket[cite: 2] ---

echo "\n[2/2] Syncing Knockout Matches...\n";
$url_matches = "{$BASE_URL}competitions/WC/matches";
$matches_data = get_api_data($url_matches, $API_KEY);

if ($matches_data && isset($matches_data['matches'])) {
    $db->exec("DELETE FROM wc_knockout");
    $db->beginTransaction();

    $knockout_stages = ['ROUND_OF_32', 'ROUND_OF_16', 'QUARTER_FINALS', 'SEMI_FINALS', 'THIRD_PLACE', 'FINAL'];
    $stmt_k = $db->prepare("INSERT INTO wc_knockout VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($matches_data['matches'] as $m) {
        if (in_array($m['stage'], $knockout_stages)) {
            $stmt_k->execute([
                $m['stage'], $m['matchNumber'] ?? 0, substr($m['utcDate'], 0, 10),
                $m['homeTeam']['name'] ?? 'TBD', $m['awayTeam']['name'] ?? 'TBD',
                $m['score']['fullTime']['home'], $m['score']['fullTime']['away'],
                $m['status'], $m['venue'] ?? 'TBD', time()
            ]);
        }
    }
    $db->commit();
    echo "  ✓ Knockout bracket updated.\n";
}

echo "\n🏁 Data sync complete! Your World Cup database is up to date.\n";