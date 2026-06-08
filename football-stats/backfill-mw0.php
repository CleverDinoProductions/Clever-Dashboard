<?php
// Backfill MW0 (pre-season blank snapshot) for every season that is missing one.
// Teams are derived from the earliest non-zero matchweek snapshot for that season.
// Badges are taken from the team's earliest available snapshot row.
// Run: php backfill-mw0.php

$db_path = __DIR__ . '/football-stats.sqlite3';
if (!file_exists($db_path)) {
    echo "Database not found at $db_path\n";
    exit(1);
}

$db = new SQLite3($db_path);
$timestamp = (int)(microtime(true) * 1000);

// Find all (competition_code, season_label) pairs that have no MW0
$seasons = $db->query("
    SELECT DISTINCT competition_code, season_label
    FROM league_table_snapshots
    WHERE (competition_code, season_label) NOT IN (
        SELECT competition_code, season_label
        FROM league_table_snapshots
        WHERE matchweek = 0
    )
    ORDER BY competition_code, season_label
");

$ins = $db->prepare("INSERT OR IGNORE INTO league_table_snapshots
    (competition_code, season_label, matchweek, team_crest, team_name,
     position, played, won, drawn, lost, gf, ga, gd, points, source_updated_at, archived_at)
    VALUES (?, ?, 0, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0, 0, ?, ?)");

$total = 0;

while ($row = $seasons->fetchArray(SQLITE3_ASSOC)) {
    $code   = $row['competition_code'];
    $season = $row['season_label'];

    // Get all teams + their badge from the earliest matchweek snapshot for this season
    $esc_code   = $db->escapeString($code);
    $esc_season = $db->escapeString($season);
    $teams = $db->query("
        SELECT team_name, team_crest
        FROM league_table_snapshots
        WHERE competition_code = '$esc_code'
          AND season_label = '$esc_season'
          AND matchweek = (
              SELECT MIN(matchweek)
              FROM league_table_snapshots
              WHERE competition_code = '$esc_code'
                AND season_label = '$esc_season'
                AND matchweek > 0
          )
        ORDER BY team_name ASC
    ");

    $team_rows = [];
    while ($t = $teams->fetchArray(SQLITE3_ASSOC)) {
        $team_rows[] = $t;
    }

    if (empty($team_rows)) {
        echo "  -> $code / $season: No team data found, skipping.\n";
        continue;
    }

    $db->exec("BEGIN");
    $pos = 1;
    foreach ($team_rows as $t) {
        $ins->bindValue(1, $code);
        $ins->bindValue(2, $season);
        $ins->bindValue(3, $t['team_crest']);
        $ins->bindValue(4, $t['team_name']);
        $ins->bindValue(5, $pos++);
        $ins->bindValue(6, $timestamp);
        $ins->bindValue(7, $timestamp);
        $ins->execute();
    }
    $db->exec("COMMIT");

    $count = count($team_rows);
    echo "  -> $code / $season: MW0 created for $count teams.\n";
    $total += $count;
}

echo "\nDone. $total MW0 rows inserted.\n";
