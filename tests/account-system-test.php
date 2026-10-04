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
account_assert((int)$db->query('SELECT COUNT(*) FROM user_groups')->fetchColumn() === 2, 'Default groups should be created.');
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
