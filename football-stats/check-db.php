<?php
require_once 'config.php';

echo "<h2>Database Schema Check</h2>";

// Get table structure
$stmt = $db->query("PRAGMA table_info(league_table)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Columns in league_table:</h3>";
echo "<pre>";
foreach ($columns as $col) {
    echo $col['name'] . " (" . $col['type'] . ")\n";
}
echo "</pre>";

// Show sample row
echo "<h3>Sample Data:</h3>";
$stmt = $db->query("SELECT * FROM league_table LIMIT 1");
$sample = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($sample);
echo "</pre>";
?>
