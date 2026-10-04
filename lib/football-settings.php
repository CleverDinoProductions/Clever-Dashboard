<?php

declare(strict_types=1);

function clever_football_db(): PDO
{
    static $db;
    if ($db instanceof PDO) return $db;
    $path = getenv('CLEVER_FOOTBALL_DB') ?: dirname(__DIR__) . '/football-stats/football-stats.sqlite3';
    $db = new PDO('sqlite:' . $path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    clever_migrate_football_settings($db);
    return $db;
}

function clever_migrate_football_settings(PDO $db): void
{
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS team_metadata (
    competition_code TEXT NOT NULL,
    team_name TEXT NOT NULL,
    display_name TEXT NOT NULL,
    common_name TEXT NOT NULL,
    nickname TEXT NOT NULL DEFAULT '',
    short_code TEXT NOT NULL,
    color TEXT NOT NULL DEFAULT '#888888',
    PRIMARY KEY (competition_code, team_name)
);
CREATE TABLE IF NOT EXISTS points_deductions (
    competition_code TEXT NOT NULL,
    season_label TEXT NOT NULL,
    team_name TEXT NOT NULL,
    points INTEGER NOT NULL CHECK (points > 0),
    reason TEXT NOT NULL DEFAULT '',
    PRIMARY KEY (competition_code, season_label, team_name, reason)
);
SQL);

    // A missing/empty database should render an empty dashboard rather than a
    // partially generated page followed by a fatal "no such table" error.
    $standingColumns = 'team_crest TEXT, team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER, lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER, updated_at INTEGER';
    foreach (['D1', 'PL', 'ELC', 'L1', 'L2', 'NL'] as $code) {
        $db->exec("CREATE TABLE IF NOT EXISTS league_table_$code ($standingColumns)");
    }
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS matches (
    id INTEGER PRIMARY KEY AUTOINCREMENT, competition_code TEXT, competition_name TEXT,
    season_label TEXT, matchweek INTEGER, match_date TEXT, match_timestamp TEXT,
    home_team TEXT, away_team TEXT, home_goals INTEGER, away_goals INTEGER,
    home_pens INTEGER, away_pens INTEGER, status TEXT, source TEXT
);
CREATE TABLE IF NOT EXISTS league_table_snapshots (
    competition_code TEXT, season_label TEXT, matchweek INTEGER, team_crest TEXT,
    team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER,
    lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER,
    source_updated_at INTEGER, archived_at INTEGER, competition_name TEXT,
    PRIMARY KEY (competition_code, season_label, matchweek, team_name)
);
CREATE TABLE IF NOT EXISTS league_table_snapshots_by_date (
    competition_code TEXT, season_label TEXT, snapshot_date TEXT, team_crest TEXT,
    team_name TEXT, position INTEGER, played INTEGER, won INTEGER, drawn INTEGER,
    lost INTEGER, gf INTEGER, ga INTEGER, gd INTEGER, points INTEGER,
    source_updated_at INTEGER, archived_at INTEGER, competition_name TEXT,
    PRIMARY KEY (competition_code, season_label, snapshot_date, team_name)
);
CREATE TABLE IF NOT EXISTS live_table_metadata (
    competition_code TEXT PRIMARY KEY, live_table_name TEXT NOT NULL,
    season_label TEXT NOT NULL, matchweek INTEGER NOT NULL, updated_at INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_matches_competition_season
ON matches (competition_code, season_label, match_date);
CREATE INDEX IF NOT EXISTS idx_snapshots_lookup
ON league_table_snapshots (competition_code, season_label, matchweek, position);
SQL);
}

function clever_seed_team_metadata(PDO $db, array $sets): void
{
    $stmt = $db->prepare('INSERT OR IGNORE INTO team_metadata (competition_code, team_name, display_name, common_name, nickname, short_code, color) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($sets as $code => $teams) {
        foreach ($teams as $key => $info) {
            $stmt->execute([$code, $key, $info['name'], $info['common_name'], $info['nickname'], $info['short'], $info['color']]);
        }
    }
}

function clever_team_metadata(PDO $db, string $competitionCode): array
{
    $stmt = $db->prepare('SELECT team_name, display_name AS name, common_name, nickname, short_code AS short, color FROM team_metadata WHERE competition_code = ? ORDER BY team_name');
    $stmt->execute([$competitionCode]);
    $teams = [];
    foreach ($stmt as $row) {
        $key = $row['team_name'];
        unset($row['team_name']);
        $teams[$key] = $row;
    }
    return $teams;
}
