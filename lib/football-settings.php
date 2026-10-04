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
