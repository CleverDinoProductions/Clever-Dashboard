<?php
// Check YouTube Dashboard Database
require_once 'config.php';

echo "<pre>";
echo "YouTube Dashboard Database Check\n";
echo "=================================\n\n";

try {
    $db = new SQLite3(DB_PATH);
    
    // Check tables
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    echo "Tables:\n";
    while ($table = $tables->fetchArray(SQLITE3_NUM)) {
        echo "  - " . $table[0] . "\n";
    }
    
    // Count records
    echo "\nRecords:\n";
    $videos = $db->querySingle("SELECT COUNT(*) FROM videos");
    $channels = $db->querySingle("SELECT COUNT(*) FROM channels");
    $gaps = $db->querySingle("SELECT COUNT(*) FROM upload_gaps");
    
    echo "  Videos: $videos\n";
    echo "  Channels: $channels\n";
    echo "  Upload Gaps: $gaps\n";
    
    // Recent videos
    echo "\nRecent Videos:\n";
    $recent = $db->query("SELECT channel_name, title, published_date FROM videos ORDER BY published_date DESC LIMIT 5");
    while ($video = $recent->fetchArray(SQLITE3_ASSOC)) {
        echo "  - [{$video['channel_name']}] {$video['title']}\n";
        echo "    " . date('M j, Y g:i A', strtotime($video['published_date'])) . "\n";
    }
    
    echo "\n✅ Database check complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
?>
