<?php
require_once __DIR__ . '/auth.php';

function clever_result_keys(): array
{
    return ['calc_mode', 'snapshot_season', 'excluded_matches', 'excluded_results', 'outcome_overrides', 'custom_points_deductions', 'custom_win_points', 'custom_draw_points', 'custom_loss_points'];
}

function clever_result_state(array $params): array
{
    $state = [];
    foreach (clever_result_keys() as $key) {
        if (isset($params[$key]) && is_string($params[$key])) $state[$key] = $params[$key];
    }
    if (strlen(json_encode($state, JSON_THROW_ON_ERROR)) > 100000) throw new InvalidArgumentException('This result set is too large to save.');
    return $state;
}

function clever_results_db(): PDO
{
    $db = clever_accounts_db();
    $db->exec('CREATE TABLE IF NOT EXISTS user_saved_results (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        name TEXT NOT NULL, season TEXT NOT NULL, league TEXT NOT NULL,
        state TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    return $db;
}

function clever_saved_results(int $userId): array
{
    $stmt = clever_results_db()->prepare('SELECT * FROM user_saved_results WHERE user_id = ? ORDER BY id DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function clever_save_results(int $userId, string $name, string $season, string $league, array $params): int
{
    $name = trim($name);
    if ($name === '' || strlen($name) > 100) throw new InvalidArgumentException('Enter a save name of 1–100 characters.');
    if (!preg_match('/^\d{4}-\d{4}$/D', $season) || !preg_match('/^[a-z0-9-]{1,60}$/D', $league)) throw new InvalidArgumentException('Select a league season before saving.');
    $state = clever_result_state($params);
    if (empty($state['outcome_overrides'])) throw new InvalidArgumentException('Apply your simulated match outcomes before saving.');
    $state['calc_mode'] = 'custom_matches';
    $db = clever_results_db();
    $stmt = $db->prepare('INSERT INTO user_saved_results(user_id,name,season,league,state) VALUES(?,?,?,?,?)');
    $stmt->execute([$userId, $name, $season, $league, json_encode($state, JSON_THROW_ON_ERROR)]);
    return (int)$db->lastInsertId();
}

function clever_delete_results(int $userId, int $id): void
{
    $stmt = clever_results_db()->prepare('DELETE FROM user_saved_results WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
}

function clever_load_results(int $userId, int $id, string $season, string $league, array $params): array
{
    $stmt = clever_results_db()->prepare('SELECT * FROM user_saved_results WHERE id = ? AND user_id = ? AND season = ? AND league = ?');
    $stmt->execute([$id, $userId, $season, $league]);
    $save = $stmt->fetch();
    if (!$save) throw new InvalidArgumentException('That save is unavailable for this account, league and season.');
    foreach (clever_result_keys() as $key) unset($params[$key]);
    return array_merge($params, clever_result_state(json_decode($save['state'], true, 512, JSON_THROW_ON_ERROR)));
}
