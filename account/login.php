<?php
require_once dirname(__DIR__) . '/lib/auth.php';
clever_start_session();
if (clever_current_user()) {
    header('Location: /account/');
    exit;
}
$error = '';
$return = clever_safe_return_path((string)($_REQUEST['return'] ?? ''), '/account/');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clever_verify_csrf();
    if (clever_login((string)($_POST['identity'] ?? ''), (string)($_POST['password'] ?? ''))) {
        header('Location: ' . $return);
        exit;
    }
    $error = 'The username/email or password was incorrect, or the account is disabled.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in | CleverDino</title><link rel="stylesheet" href="/account/account.css"></head><body><main class="shell"><section class="panel auth-panel"><a class="brand" href="/">🦖 Clever<span>Dino</span></a><h1 style="margin-top:24px">Welcome back</h1><p class="muted">Sign in to your dashboard account.</p><?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><input type="hidden" name="return" value="<?= htmlspecialchars($return) ?>"><label>Username or email<input name="identity" required autofocus autocomplete="username"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button type="submit">Sign in</button></form><p class="muted">No account? <a href="/account/register.php">Create one</a>.</p></section></main></body></html>
