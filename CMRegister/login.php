<?php
/** Legacy plain-text login endpoint backed by the shared SQLite accounts. */
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
require_once dirname(__DIR__) . '/lib/auth.php';
$username = trim((string)($_POST['Name'] ?? $_GET['Name'] ?? ''));
$password = (string)($_POST['Pass'] ?? $_GET['Pass'] ?? '');
if ($username === '' || $password === '') {
    http_response_code(400); echo 'Username and password required'; exit;
}
echo clever_login($username, $password) ? 'yes' : 'Password incorrect!';
