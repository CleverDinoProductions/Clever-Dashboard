<?php
$db_path = __DIR__ . '/football-stats.sqlite3';
$db = new PDO("sqlite:$db_path");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$world_cup_db_path = __DIR__ . '/world-cup-stats.sqlite3';
$world_cup_db = new PDO("sqlite:$world_cup_db_path");
$world_cup_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>