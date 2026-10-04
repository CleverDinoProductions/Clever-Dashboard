<?php
/** Legacy plain-text registration endpoint backed by the shared SQLite accounts. */
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
require_once dirname(__DIR__) . '/lib/auth.php';
$username = trim((string)($_POST['login'] ?? $_GET['login'] ?? ''));
$password = (string)($_POST['pass'] ?? $_GET['pass'] ?? '');
$email = trim((string)($_POST['email'] ?? $_GET['email'] ?? ''));
$errors = clever_validate_registration($username, $email, $password);
if ($errors) { http_response_code(400); echo $errors[0]; exit; }
try {
    clever_create_user($username, $email, $password);
    echo 'Account made!';
} catch (PDOException $exception) {
    http_response_code(409);
    echo str_contains($exception->getMessage(), 'users.email') ? 'Email already registered' : 'Username already exists';
}
