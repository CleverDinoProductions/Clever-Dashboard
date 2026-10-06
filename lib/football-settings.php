<?php

declare(strict_types=1);

function clever_football_db(): PDO
{
    static $db;
    if ($db instanceof PDO) return $db;
    $configuredPath = getenv('CLEVER_FOOTBALL_DB');
    $canonicalPath = dirname(__DIR__) . '/football-stats/football-stats.sqlite3';
    $legacyPath = dirname(__DIR__) . '/football-stats/football-stats.db';
    $path = is_string($configuredPath) && trim($configuredPath) !== ''
        ? $configuredPath
        : (is_file($canonicalPath) ? $canonicalPath : $legacyPath);
    $db = new PDO('sqlite:' . $path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA busy_timeout = 5000');
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
CREATE TABLE IF NOT EXISTS dashboard_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT NOT NULL
);
SQL);
    $defaults = [
        'dashboard_title' => 'Football Stats Dashboard',
        'dashboard_subtitle' => 'FA Premier League, EFL, National Leagues & World Cup Standings, Analytics and Poisson Model based Simulations',
        'english_data_provider' => 'SportsDB API',
        'world_cup_data_provider' => 'Football-Data API',
        'show_update_controls' => '1',
    ];
    $stmt = $db->prepare('INSERT OR IGNORE INTO dashboard_settings (setting_key, setting_value) VALUES (?, ?)');
    foreach ($defaults as $key => $value) $stmt->execute([$key, $value]);
}

function clever_dashboard_settings(PDO $db): array
{
    clever_migrate_football_settings($db);
    return $db->query('SELECT setting_key, setting_value FROM dashboard_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
}

function clever_seed_team_metadata(PDO $db, array $sets): void
{
    $seeded = $db->query("SELECT setting_value FROM dashboard_settings WHERE setting_key = 'team_metadata_seeded'")->fetchColumn();
    if ($seeded === '1') return;
    $stmt = $db->prepare('INSERT OR IGNORE INTO team_metadata (competition_code, team_name, display_name, common_name, nickname, short_code, color) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($sets as $code => $teams) {
        foreach ($teams as $key => $info) {
            $stmt->execute([$code, $key, $info['name'], $info['common_name'], $info['nickname'], $info['short'], $info['color']]);
        }
    }
    $db->exec("INSERT INTO dashboard_settings (setting_key, setting_value) VALUES ('team_metadata_seeded', '1') ON CONFLICT(setting_key) DO UPDATE SET setting_value='1'");
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

function clever_team_group_defaults(): array
{
    $six = ['Arsenal', 'Chelsea', 'Liverpool', 'Manchester City', 'Manchester United', 'Tottenham Hotspur'];
    $eight = array_merge($six, ['Leeds United', 'Aston Villa']);
    return [
        'big_six' => $six,
        'big_eight' => $eight,
        'big_twelve' => array_merge($eight, ['Newcastle United', 'Everton', 'Fulham', 'Crystal Palace']),
    ];
}

function clever_parse_team_group(string $value): array
{
    $teams = [];
    foreach (preg_split('/[\r\n,]+/', $value) as $name) {
        $name = trim($name);
        if ($name === '') continue;
        if (strlen($name) > 100 || preg_match('/[\x00-\x1f\x7f]/', $name)) {
            throw new InvalidArgumentException('Team names must be at most 100 characters and contain no control characters.');
        }
        $teams[strtolower($name)] = $name;
    }
    if (count($teams) > 100) throw new InvalidArgumentException('Each group can contain at most 100 teams.');
    return array_values($teams);
}

function clever_team_groups(array $settings): array
{
    $groups = clever_team_group_defaults();
    foreach ($groups as $key => $default) {
        if (array_key_exists('team_group_' . $key, $settings)) {
            $groups[$key] = clever_parse_team_group((string)$settings['team_group_' . $key]);
        }
    }
    return $groups;
}

function clever_save_team_groups(PDO $db, array $values): void
{
    $groups = [];
    foreach (clever_team_group_defaults() as $key => $default) {
        if (!isset($values[$key]) || !is_string($values[$key])) {
            throw new InvalidArgumentException('Supply a team list for every group.');
        }
        $groups[$key] = clever_parse_team_group($values[$key]);
    }
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('INSERT INTO dashboard_settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value');
        foreach ($groups as $key => $teams) $stmt->execute(['team_group_' . $key, implode("\n", $teams)]);
        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack();
        throw $exception;
    }
}
