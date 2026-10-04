<?php

declare(strict_types=1);

$suffix = bin2hex(random_bytes(5));
$accountsPath = sys_get_temp_dir() . '/clever-admin-accounts-' . $suffix . '.sqlite3';
putenv('CLEVER_ACCOUNTS_DB=' . $accountsPath);
putenv('CLEVER_FOOTBALL_DB=' . sys_get_temp_dir() . '/missing-' . $suffix . '/football.sqlite3');

require_once dirname(__DIR__) . '/lib/auth.php';
$accounts = clever_accounts_db();
$adminGroup = (int)$accounts->query("SELECT id FROM user_groups WHERE is_admin = 1")->fetchColumn();
$adminId = clever_create_user('admin_test', 'admin@example.com', 'a-secure-password', $adminGroup);
clever_start_session();
$_SESSION[CLEVER_SESSION_USER_ID] = $adminId;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/admin/admin.php';

ob_start();
require dirname(__DIR__) . '/admin/admin.php';
$html = ob_get_clean();

if (!str_contains($html, 'Administration') || !str_contains($html, 'Football configuration is unavailable')) {
    throw new RuntimeException('The admin page should remain available when the football database cannot be opened.');
}

clever_logout();
@unlink($accountsPath);
putenv('CLEVER_ACCOUNTS_DB');
putenv('CLEVER_FOOTBALL_DB');
echo "Admin page tests passed.\n";
