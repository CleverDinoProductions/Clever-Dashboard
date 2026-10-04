<?php
require_once dirname(__DIR__) . '/lib/auth.php';
$db = clever_accounts_db();
if ((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
    http_response_code(404);
    exit('Initial setup is no longer available.');
}
clever_start_session();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clever_verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $errors = clever_validate_registration($username, $email, $password);
    if ($password !== (string)($_POST['password_confirm'] ?? '')) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        $groupId = (int)$db->query("SELECT id FROM user_groups WHERE is_admin = 1 ORDER BY id LIMIT 1")->fetchColumn();
        clever_create_user($username, $email, $password, $groupId);
        clever_login($username, $password);
        header('Location: /control-panel.php');
        exit;
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Initial setup | CleverDino</title><link rel="stylesheet" href="/account/account.css"></head><body><main class="shell"><section class="panel auth-panel"><a class="brand" href="/">🦖 Clever<span>Dino</span></a><h1 style="margin-top:24px">Create the first administrator</h1><p class="muted">This one-time page is disabled as soon as the account is created.</p><?php foreach ($errors as $error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endforeach; ?><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><label>Username<input name="username" required pattern="[A-Za-z0-9_]{3,50}" autocomplete="username"></label><label>Email<input type="email" name="email" required autocomplete="email"></label><label>Password<input type="password" name="password" minlength="10" required autocomplete="new-password"></label><label>Confirm password<input type="password" name="password_confirm" minlength="10" required autocomplete="new-password"></label><button>Create administrator</button></form></section></main></body></html>
