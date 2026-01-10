<?php
// Database Configuration - CleverLounge DB (READ-ONLY)
$dbPath = '/home/paul/.thelounge/logs/DinoDude.sqlite3'; // Adjust to your path

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Timezone
date_default_timezone_set('UTC'); // Force GMT

// Global functions
function formatTimestamp($timestampMs) {
    return date('Y-m-d H:i:s', $timestampMs / 1000);
}

function timeAgo($timestampMs) {
    $seconds = (time() * 1000 - $timestampMs) / 1000;
    if ($seconds < 60) return round($seconds) . 's ago';
    if ($seconds < 3600) return round($seconds / 60) . 'm ago';
    if ($seconds < 86400) return round($seconds / 3600) . 'h ago';
    return round($seconds / 86400) . 'd ago';
}

// Alias with underscore for consistency
function time_ago($timestampMs) {
    return timeAgo($timestampMs);
}
?>