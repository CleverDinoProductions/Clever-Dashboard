<?php

declare(strict_types=1);

const CLEVER_SESSION_USER_ID = 'clever_user_id';

function clever_start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $forwardedProtocol = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProtocol === 'https';
        session_set_cookie_params([
            'path' => '/',
            'httponly' => true,
            'secure' => $isSecure,
            'samesite' => 'Lax',
        ]);
        if (!session_start()) {
            throw new RuntimeException('Unable to start the account session.');
        }
    }
}

function clever_accounts_db_path(): string
{
    return getenv('CLEVER_ACCOUNTS_DB') ?: dirname(__DIR__) . '/data/accounts.sqlite3';
}

function clever_accounts_db(): PDO
{
    static $db;
    if ($db instanceof PDO) {
        return $db;
    }

    $path = clever_accounts_db_path();
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the account database directory.');
    }

    $db = new PDO('sqlite:' . $path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA busy_timeout = 5000');
    $db->exec('PRAGMA foreign_keys = ON');
    clever_migrate_accounts($db);
    return $db;
}

function clever_migrate_accounts(PDO $db): void
{
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS user_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE COLLATE NOCASE,
    description TEXT NOT NULL DEFAULT '',
    is_admin INTEGER NOT NULL DEFAULT 0 CHECK (is_admin IN (0, 1)),
    can_manage_football INTEGER NOT NULL DEFAULT 0 CHECK (can_manage_football IN (0, 1)),
    can_update_data INTEGER NOT NULL DEFAULT 0 CHECK (can_update_data IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE COLLATE NOCASE,
    email TEXT NOT NULL UNIQUE COLLATE NOCASE,
    password_hash TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'disabled')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at TEXT
);
CREATE TABLE IF NOT EXISTS user_group_memberships (
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    group_id INTEGER NOT NULL REFERENCES user_groups(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, group_id)
);
CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    default_season TEXT NOT NULL DEFAULT '2025-2026',
    default_league TEXT NOT NULL DEFAULT 'premier-league',
    default_view TEXT NOT NULL DEFAULT 'table',
    favourite_team TEXT NOT NULL DEFAULT '',
    accent_color TEXT NOT NULL DEFAULT '#FFD700',
    compact_navigation INTEGER NOT NULL DEFAULT 0 CHECK (compact_navigation IN (0, 1)),
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_memberships_group ON user_group_memberships(group_id);
SQL);

    $groupColumns = array_column($db->query('PRAGMA table_info(user_groups)')->fetchAll(), 'name');
    if (!in_array('can_manage_football', $groupColumns, true)) $db->exec('ALTER TABLE user_groups ADD COLUMN can_manage_football INTEGER NOT NULL DEFAULT 0');
    if (!in_array('can_update_data', $groupColumns, true)) $db->exec('ALTER TABLE user_groups ADD COLUMN can_update_data INTEGER NOT NULL DEFAULT 0');

    $db->exec("INSERT OR IGNORE INTO user_groups (name, description, is_admin) VALUES ('Members', 'Standard dashboard accounts', 0)");
    $db->exec("INSERT OR IGNORE INTO user_groups (name, description, is_admin) VALUES ('Administrators', 'Full access to account and dashboard configuration', 1)");
    $db->exec("INSERT OR IGNORE INTO user_groups (name, description, is_admin, can_manage_football, can_update_data) VALUES ('Football Editors', 'Maintainers of football teams, rules and dashboard content', 0, 1, 1)");
    $db->exec("INSERT OR IGNORE INTO user_groups (name, description, is_admin) VALUES ('Analysts', 'Users who focus on tables, comparisons and simulations', 0)");
    $db->exec("INSERT OR IGNORE INTO user_groups (name, description, is_admin, can_update_data) VALUES ('Data Operators', 'Users responsible for keeping dashboard data current', 0, 1)");
}

function clever_user_preferences(int $userId): array
{
    $defaults = [
        'default_season' => '2025-2026', 'default_league' => 'premier-league',
        'default_view' => 'table', 'favourite_team' => '', 'accent_color' => '#FFD700',
        'compact_navigation' => 0,
    ];
    $stmt = clever_accounts_db()->prepare('SELECT default_season, default_league, default_view, favourite_team, accent_color, compact_navigation FROM user_preferences WHERE user_id = ?');
    $stmt->execute([$userId]);
    $stored = $stmt->fetch();
    return $stored ? array_merge($defaults, $stored) : $defaults;
}

function clever_save_user_preferences(int $userId, array $values): array
{
    $preferences = [
        'default_season' => trim((string)($values['default_season'] ?? '2025-2026')),
        'default_league' => trim((string)($values['default_league'] ?? 'premier-league')),
        'default_view' => trim((string)($values['default_view'] ?? 'table')),
        'favourite_team' => trim((string)($values['favourite_team'] ?? '')),
        'accent_color' => strtoupper(trim((string)($values['accent_color'] ?? '#FFD700'))),
        'compact_navigation' => !empty($values['compact_navigation']) ? 1 : 0,
    ];
    if (!preg_match('/^[a-z0-9-]{1,40}$/', $preferences['default_season'])
        || !preg_match('/^[a-z0-9-]{1,40}$/', $preferences['default_league'])
        || !preg_match('/^[a-z0-9-]{1,40}$/', $preferences['default_view'])) {
        throw new InvalidArgumentException('The selected dashboard destination is invalid.');
    }
    if (!preg_match('/^#[0-9A-F]{6}$/', $preferences['accent_color'])) {
        throw new InvalidArgumentException('Accent colour must be a six-digit hex colour.');
    }
    if (strlen($preferences['favourite_team']) > 100) {
        throw new InvalidArgumentException('Favourite team must be 100 characters or fewer.');
    }
    $stmt = clever_accounts_db()->prepare(<<<'SQL'
INSERT INTO user_preferences (user_id, default_season, default_league, default_view, favourite_team, accent_color, compact_navigation, updated_at)
VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
ON CONFLICT(user_id) DO UPDATE SET default_season=excluded.default_season, default_league=excluded.default_league,
default_view=excluded.default_view, favourite_team=excluded.favourite_team, accent_color=excluded.accent_color,
compact_navigation=excluded.compact_navigation, updated_at=CURRENT_TIMESTAMP
SQL);
    $stmt->execute([$userId, $preferences['default_season'], $preferences['default_league'], $preferences['default_view'], $preferences['favourite_team'], $preferences['accent_color'], $preferences['compact_navigation']]);
    return $preferences;
}

function clever_current_user(): ?array
{
    clever_start_session();
    $id = filter_var($_SESSION[CLEVER_SESSION_USER_ID] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        return null;
    }

    $stmt = clever_accounts_db()->prepare(<<<'SQL'
SELECT u.id, u.username, u.email, u.status, u.created_at, u.last_login_at,
       COALESCE(MAX(g.is_admin), 0) AS is_admin,
       COALESCE(MAX(g.can_manage_football), 0) AS can_manage_football,
       COALESCE(MAX(g.can_update_data), 0) AS can_update_data,
       GROUP_CONCAT(g.name, ', ') AS groups
FROM users u
LEFT JOIN user_group_memberships ugm ON ugm.user_id = u.id
LEFT JOIN user_groups g ON g.id = ugm.group_id
WHERE u.id = ?
GROUP BY u.id
SQL);
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] !== 'active') {
        unset($_SESSION[CLEVER_SESSION_USER_ID]);
        return null;
    }
    $user['is_admin'] = (bool)$user['is_admin'];
    $user['can_manage_football'] = $user['is_admin'] || (bool)$user['can_manage_football'];
    $user['can_update_data'] = $user['is_admin'] || (bool)$user['can_update_data'];
    return $user;
}

function clever_is_admin(): bool
{
    $user = clever_current_user();
    return $user !== null && $user['is_admin'];
}

function clever_login(string $identity, string $password): bool
{
    $identity = trim($identity);
    if ($identity === '' || $password === '') {
        return false;
    }
    $stmt = clever_accounts_db()->prepare('SELECT id, password_hash, status FROM users WHERE username = ? COLLATE NOCASE OR email = ? COLLATE NOCASE LIMIT 1');
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    clever_start_session();
    session_regenerate_id(true);
    $_SESSION[CLEVER_SESSION_USER_ID] = (int)$user['id'];
    $stmt = clever_accounts_db()->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([$user['id']]);
    return true;
}

function clever_logout(): void
{
    clever_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function clever_require_login(string $loginPath = '/account/login.php'): array
{
    $user = clever_current_user();
    if (!$user) {
        $return = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . $loginPath . '?return=' . rawurlencode($return));
        exit;
    }
    return $user;
}

function clever_require_admin(): array
{
    $user = clever_require_login();
    if (!$user['is_admin']) {
        http_response_code(403);
        exit('Administrator access is required.');
    }
    return $user;
}

function clever_csrf_token(): string
{
    clever_start_session();
    if (empty($_SESSION['clever_csrf'])) {
        $_SESSION['clever_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['clever_csrf'];
}

function clever_verify_csrf(): void
{
    clever_start_session();
    $provided = (string)($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals((string)($_SESSION['clever_csrf'] ?? ''), $provided)) {
        http_response_code(419);
        exit('Your session expired. Please go back and try again.');
    }
}

function clever_safe_return_path(string $path, string $fallback = '/'): string
{
    return str_starts_with($path, '/') && !str_starts_with($path, '//') ? $path : $fallback;
}

function clever_validate_registration(string $username, string $email, string $password): array
{
    $errors = [];
    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $errors[] = 'Username must be 3–50 characters and contain only letters, numbers, and underscores.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 10) {
        $errors[] = 'Password must be at least 10 characters.';
    }
    return $errors;
}

function clever_create_user(string $username, string $email, string $password, ?int $groupId = null): int
{
    $db = clever_accounts_db();
    $username = trim($username);
    $email = trim($email);
    $errors = clever_validate_registration($username, $email, $password);
    if ($errors !== []) {
        throw new InvalidArgumentException($errors[0]);
    }
    if ($groupId === null) {
        $groupId = (int)$db->query("SELECT id FROM user_groups WHERE name = 'Members'")->fetchColumn();
    }
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
        $userId = (int)$db->lastInsertId();
        $stmt = $db->prepare('INSERT INTO user_group_memberships (user_id, group_id) VALUES (?, ?)');
        $stmt->execute([$userId, $groupId]);
        $db->commit();
        return $userId;
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }
}
