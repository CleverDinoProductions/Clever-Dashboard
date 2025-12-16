<?php
// Setup YouTube Dashboard Database
require_once 'config.php';

echo "Setting up YouTube Dashboard database...\n";

try {
    $db = new SQLite3(DB_PATH);
    
    // Videos table
    $db->exec("
        CREATE TABLE IF NOT EXISTS videos (
            video_id TEXT PRIMARY KEY,
            channel_name TEXT,
            channel_id TEXT,
            title TEXT,
            published_date TEXT,
            url TEXT,
            detected_location TEXT,
            first_seen TEXT
        )
    ");
    
    // Channels table
    $db->exec("
        CREATE TABLE IF NOT EXISTS channels (
            channel_id TEXT PRIMARY KEY,
            channel_name TEXT,
            last_checked TEXT,
            video_count INTEGER DEFAULT 0
        )
    ");
    
    // Upload gaps table
    $db->exec("
        CREATE TABLE IF NOT EXISTS upload_gaps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            channel_id TEXT,
            video_id TEXT,
            gap_days INTEGER,
            previous_video_date TEXT,
            current_video_date TEXT,
            FOREIGN KEY (video_id) REFERENCES videos(video_id)
        )
    ");
    
    // Create indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_published_date ON videos(published_date)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_channel_id ON videos(channel_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_gap_days ON upload_gaps(gap_days)");
    
    echo "✅ Database created successfully!\n";
    echo "📁 Location: " . DB_PATH . "\n";
    
    $db->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
