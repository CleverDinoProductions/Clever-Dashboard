<?php
// Database Configuration
// Auto-detect environment and set correct path
if (PHP_OS_FAMILY === 'Windows') {
    // Running on XAMPP Windows
    $dbpath = 'E:\\thelounge\\logs\\DinoDude.sqlite3';
} else {
    // Running on WSL/Linux
    $dbpath = '/mnt/e/thelounge/logs/DinoDude.sqlite3';
}

try {
    // Open database in READ-ONLY mode to prevent locking
    $db = new PDO("sqlite:$dbpath", null, null, [
        PDO::SQLITE_ATTR_OPEN_FLAGS => PDO::SQLITE_OPEN_READONLY
    ]);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Global functions
function format_timestamp($timestamp_ms) {
    return date('Y-m-d H:i:s', $timestamp_ms / 1000);
}

function time_ago($timestamp_ms) {
    $seconds = (time() * 1000 - $timestamp_ms) / 1000;
    if ($seconds < 60) return round($seconds) . "s ago";
    if ($seconds < 3600) return round($seconds / 60) . "m ago";
    if ($seconds < 86400) return round($seconds / 3600) . "h ago";
    return round($seconds / 86400) . "d ago";
}
?>

