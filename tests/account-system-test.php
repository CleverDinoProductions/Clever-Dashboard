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

clever_logout();
@unlink($temp);
echo "Account system tests passed.\n";
