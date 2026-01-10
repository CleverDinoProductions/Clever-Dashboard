<?php
// create-indexes.php - Run once to optimize database performance

require_once 'config.php'; // Your existing config that sets up $db

echo "Creating database indexes for performance optimization...\n\n";

try {
    // Index 1: Channel + Time (speeds up queue/voice queries)
    echo "Creating idx_messages_channel_time... ";
    $db->exec("
        CREATE INDEX IF NOT EXISTS idx_messages_channel_time 
        ON messages(channel, time)
    ");
    echo "✓ Done\n";
    
    // Index 2: Type + Channel + Time (speeds up mode event queries)
    echo "Creating idx_messages_type_channel_time... ";
    $db->exec("
        CREATE INDEX IF NOT EXISTS idx_messages_type_channel_time 
        ON messages(type, channel, time)
    ");
    echo "✓ Done\n";
    
    // Verify indexes were created
    echo "\nVerifying indexes...\n";
    $stmt = $db->query("
        SELECT name, sql 
        FROM sqlite_master 
        WHERE type = 'index' 
        AND name LIKE 'idx_messages_%'
    ");
    
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($indexes) >= 2) {
        echo "✓ Successfully created " . count($indexes) . " indexes:\n";
        foreach ($indexes as $index) {
            echo "  - {$index['name']}\n";
        }
    } else {
        echo "⚠ Warning: Expected at least 2 indexes, found " . count($indexes) . "\n";
    }
    
    // Show database stats
    echo "\nDatabase statistics:\n";
    $stats = $db->query("SELECT COUNT(*) as total FROM messages")->fetch();
    echo "  Total messages: " . number_format($stats['total']) . "\n";
    
    echo "\n✅ Index creation complete!\n";
    echo "Your dashboard queries should now be significantly faster.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
