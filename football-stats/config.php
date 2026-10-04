<?php
$db_path = getenv('CLEVER_FOOTBALL_DB') ?: __DIR__ . '/football-stats.sqlite3';
$db = new PDO("sqlite:$db_path");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
require_once dirname(__DIR__) . '/lib/football-settings.php';
clever_migrate_football_settings($db);
$world_cup_db_path = getenv('CLEVER_WORLD_CUP_DB') ?: __DIR__ . '/world-cup-stats.sqlite3';
$world_cup_db = new PDO("sqlite:$world_cup_db_path");
$world_cup_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
