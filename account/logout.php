<?php
require_once dirname(__DIR__) . '/lib/auth.php';
clever_logout();
header('Location: /account/login.php');
exit;
