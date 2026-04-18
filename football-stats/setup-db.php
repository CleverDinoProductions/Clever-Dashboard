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

CREATE TABLE IF NOT EXISTS league_table_snapshots (
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
);

CREATE TABLE IF NOT EXISTS live_table_metadata (
    competition_code TEXT PRIMARY KEY,
    live_table_name TEXT NOT NULL,
    season_label TEXT NOT NULL,
    matchweek INTEGER NOT NULL,
    updated_at INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_league_table_snapshots_lookup
ON league_table_snapshots (competition_code, season_label, matchweek, position);
");

echo "Database created!\n";
?>
