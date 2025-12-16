<?php
// Database connection
require_once __DIR__ . '/../config.php';

try {
    $db = new SQLite3(DB_PATH);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA journal_mode=WAL');
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Helper function for safe queries
function query($sql, $params = []) {
    global $db;
    $stmt = $db->prepare($sql);
    
    if ($params) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    
    return $stmt->execute();
}

// Get single value
function getValue($sql, $params = []) {
    $result = query($sql, $params);
    $row = $result->fetchArray(SQLITE3_NUM);
    return $row ? $row[0] : null;
}
?>
