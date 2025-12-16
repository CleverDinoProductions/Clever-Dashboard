<?php
$db_path = __DIR__ . '/football-stats.sqlite3';
$db = new PDO("sqlite:$db_path");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>