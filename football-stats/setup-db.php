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

CREATE TABLE IF NOT EXISTS points_deductions (
    competition_code TEXT NOT NULL,
    season_label TEXT NOT NULL,
    team_name TEXT NOT NULL,
    points INTEGER NOT NULL CHECK (points > 0),
    reason TEXT NOT NULL DEFAULT '',
    PRIMARY KEY (competition_code, season_label, team_name, reason)
);

CREATE INDEX IF NOT EXISTS idx_league_table_snapshots_lookup
ON league_table_snapshots (competition_code, season_label, matchweek, position);

INSERT OR IGNORE INTO points_deductions
    (competition_code, season_label, team_name, points, reason)
VALUES
    ('ELC', '2025-2026', 'Sheffield Wednesday', 18, 'Administration and EFL financial-rule breaches');
");

echo "Database created!\n";
?>
