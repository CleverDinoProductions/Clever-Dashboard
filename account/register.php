<?php
require_once dirname(__DIR__) . '/lib/auth.php';
clever_start_session();
$errors = [];
$username = trim((string)($_POST['username'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clever_verify_csrf();
    $password = (string)($_POST['password'] ?? '');
    $errors = clever_validate_registration($username, $email, $password);
    if ($password !== (string)($_POST['password_confirm'] ?? '')) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        try {
            clever_create_user($username, $email, $password);
            clever_login($username, $password);
            header('Location: /account/?created=1');
            exit;
        } catch (PDOException $exception) {
            $errors[] = str_contains($exception->getMessage(), 'users.email') ? 'That email is already registered.' : 'That username is already in use.';
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Create account | CleverDino</title><link rel="stylesheet" href="/account/account.css"></head><body><main class="shell"><section class="panel auth-panel"><a class="brand" href="/">🦖 Clever<span>Dino</span></a><h1 style="margin-top:24px">Create an account</h1><p class="muted">New accounts join the Members group. An administrator can change access later.</p><?php foreach ($errors as $error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endforeach; ?><form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>"><label>Username<input name="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9_]+" value="<?= htmlspecialchars($username) ?>" required autocomplete="username"></label><label>Email<input type="email" name="email" maxlength="254" value="<?= htmlspecialchars($email) ?>" required autocomplete="email"></label><label>Password<input type="password" name="password" minlength="10" required autocomplete="new-password"></label><label>Confirm password<input type="password" name="password_confirm" minlength="10" required autocomplete="new-password"></label><button type="submit">Create account</button></form><p class="muted">Already registered? <a href="/account/login.php">Sign in</a>.</p></section></main></body></html>
