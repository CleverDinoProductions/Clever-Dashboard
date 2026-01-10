<?php
$db_path = 'E:/thelounge/logs/DinoDude.sqlite3';
$db = new PDO("sqlite:$db_path");

// Get table list
echo "<h2>Tables in database:</h2>";
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

echo "<h2>Schema for 'messages' table:</h2>";
$schema = $db->query("PRAGMA table_info(messages)")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($schema);
echo "</pre>";

echo "<h2>Sample row:</h2>";
$sample = $db->query("SELECT * FROM messages LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($sample);
echo "</pre>";
?>
