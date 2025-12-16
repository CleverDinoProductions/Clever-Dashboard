<?php
$db = new PDO('sqlite:football-stats.sqlite3');

// Create tables
$db->exec("
CREATE TABLE IF NOT EXISTS league_table (
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
);

CREATE TABLE IF NOT EXISTS xg_table (
    team_name TEXT,
    xg_for REAL,
    xg_against REAL,
    xg_diff REAL,
    actual_gf INTEGER,
    actual_ga INTEGER,
    xg_overperformance REAL,
    updated_at INTEGER
);
");

echo "Database created!\n";
?>
