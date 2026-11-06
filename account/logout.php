<?php
/** Revokes the current browser sign-in and redirects to the login form. */
require_once dirname(__DIR__) . '/lib/auth.php';
clever_logout();
header('Location: /account/login.php');
exit;
