<?php

declare(strict_types=1);

$temp = sys_get_temp_dir() . '/clever-accounts-' . bin2hex(random_bytes(5)) . '.sqlite3';
putenv('CLEVER_ACCOUNTS_DB=' . $temp);
require_once dirname(__DIR__) . '/lib/auth.php';

function account_assert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$db = clever_accounts_db();
account_assert((int)$db->query('SELECT COUNT(*) FROM user_groups')->fetchColumn() === 5, 'Default role groups should be created.');
account_assert((int)$db->query("SELECT can_manage_football FROM user_groups WHERE name='Football Editors'")->fetchColumn() === 1, 'Football editors should receive configuration access.');
account_assert((int)$db->query("SELECT can_update_data FROM user_groups WHERE name='Data Operators'")->fetchColumn() === 1, 'Data operators should receive update access.');
account_assert(clever_validate_registration('ab', 'invalid', 'short') !== [], 'Invalid registration details should be rejected.');
account_assert(!clever_login('', ''), 'Blank credentials should be rejected.');

$invalidRejected = false;
try {
    clever_create_user('x', 'invalid', 'short');
} catch (InvalidArgumentException $exception) {
    $invalidRejected = true;
}
account_assert($invalidRejected, 'The account API should reject invalid users even when called outside the registration form.');

$userId = clever_create_user('test_user', 'test@example.com', 'a-secure-password');
account_assert($userId > 0, 'A user should be created.');
$preferences = clever_save_user_preferences($userId, [
    'default_season' => '2026-2027', 'default_league' => 'championship',
    'default_view' => 'matches', 'favourite_team' => 'Test United',
    'accent_color' => '#12AB34', 'compact_navigation' => '1',
]);
account_assert($preferences === clever_user_preferences($userId), 'Saved dashboard preferences should round-trip for the user.');
account_assert(clever_login('TEST_USER', 'a-secure-password'), 'Username login should be case insensitive.');
account_assert(session_get_cookie_params()['path'] === '/', 'The account cookie should be valid across the whole site.');
account_assert(!clever_is_admin(), 'Members should not be administrators.');

$adminGroup = (int)$db->query("SELECT id FROM user_groups WHERE is_admin=1")->fetchColumn();
$db->prepare('INSERT INTO user_group_memberships (user_id, group_id) VALUES (?, ?)')->execute([$userId, $adminGroup]);
account_assert(clever_is_admin(), 'Administrator group membership should grant admin access.');
account_assert(!clever_login('test_user', 'wrong-password'), 'An invalid password should be rejected.');

$token = $_COOKIE[CLEVER_LOGIN_COOKIE];
account_assert(strlen($token) === 64, 'Sign-in should issue a random persistent token.');
account_assert($db->query('SELECT token_hash FROM login_tokens')->fetchColumn() === hash('sha256', $token), 'Only a hash of the cookie token should be stored.');
account_assert(session_get_cookie_params()['httponly'] && session_get_cookie_params()['samesite'] === 'Lax', 'Cookies should retain HttpOnly and SameSite protection.');
$_SESSION = [];
account_assert(clever_current_user()['id'] === $userId, 'The cookie should restore login after PHP session data is lost.');
clever_logout();
account_assert((int)$db->query('SELECT COUNT(*) FROM login_tokens')->fetchColumn() === 0, 'Logout should revoke the persistent token.');
$_COOKIE[CLEVER_LOGIN_COOKIE] = $token;
account_assert(clever_current_user() === null, 'A signed-out cookie should not restore login.');

clever_login('test_user', 'a-secure-password');
$db->exec('UPDATE login_tokens SET expires_at = 0');
$_SESSION = [];
account_assert(clever_current_user() === null, 'Expired tokens should not restore login.');
clever_login('test_user', 'a-secure-password');
$db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash('another-secure-password', PASSWORD_DEFAULT), $userId]);
$_SESSION = [];
account_assert(clever_current_user() === null, 'A password change should invalidate persistent login.');
clever_login('test_user', 'another-secure-password');
$db->prepare("UPDATE users SET status = 'disabled' WHERE id = ?")->execute([$userId]);
$_SESSION = [];
account_assert(clever_current_user() === null, 'Disabled accounts should not restore login.');
$db->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$userId]);
clever_login('test_user', 'another-secure-password');
$firstBrowserToken = $_COOKIE[CLEVER_LOGIN_COOKIE];
unset($_COOKIE[CLEVER_LOGIN_COOKIE]);
$_SESSION = [];
clever_login('test_user', 'another-secure-password');
clever_logout();
$_COOKIE[CLEVER_LOGIN_COOKIE] = $firstBrowserToken;
account_assert(clever_current_user()['id'] === $userId, 'Signing out in one browser should preserve another browser login.');
$db->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
$_SESSION = [];
account_assert(clever_current_user() === null, 'Deleted accounts should not restore login.');
$_COOKIE[CLEVER_LOGIN_COOKIE] = ['invalid'];
account_assert(clever_current_user() === null, 'Malformed cookies should be rejected.');

clever_logout();
@unlink($temp);
echo "Account system tests passed.\n";
