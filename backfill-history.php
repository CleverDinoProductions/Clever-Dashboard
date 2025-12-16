<?php
$dbpath = 'E:\\thelounge\\logs\\DinoDude.sqlite3';
$db = new PDO("sqlite:$dbpath");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Starting historical backfill...\n\n";

// Create archive table if it doesn't exist
$db->exec("
    CREATE TABLE IF NOT EXISTS monthly_stats (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        month TEXT NOT NULL,
        channel TEXT NOT NULL,
        total_messages INTEGER,
        unique_users INTEGER,
        voice_grants INTEGER,
        avg_wait_time REAL,
        peak_hour INTEGER,
        peak_day INTEGER,
        top_user TEXT,
        top_user_messages INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(month, channel)
    )
");

echo "✓ Archive table created/verified\n\n";

// Find all unique months in your data
$stmt = $db->query("
    SELECT DISTINCT strftime('%Y-%m', time/1000, 'unixepoch') as month
    FROM messages
    WHERE month IS NOT NULL
    ORDER BY month ASC
");
$months = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Found " . count($months) . " months of data to process:\n";
foreach ($months as $m) {
    echo "  - $m\n";
}
echo "\n";

// Get all channels
$stmt = $db->query("
    SELECT DISTINCT channel
    FROM messages
    WHERE channel IS NOT NULL
    ORDER BY channel
");
$channels = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Found " . count($channels) . " channels:\n";
foreach ($channels as $ch) {
    echo "  - $ch\n";
}
echo "\n";

$total_processed = 0;

// Process each month × channel combination
foreach ($months as $month) {
    echo "Processing $month...\n";
    
    foreach ($channels as $channel) {
        // Basic stats
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_messages,
                COUNT(DISTINCT json_extract(msg, '$.from.nick')) as unique_users
            FROM messages
            WHERE channel = :channel
            AND strftime('%Y-%m', time/1000, 'unixepoch') = :month
        ");
        $stmt->execute(['month' => $month, 'channel' => $channel]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Skip if no messages this month/channel
        if ($stats['total_messages'] == 0) {
            continue;
        }
        
        // Voice grants and wait times (only for #an-q)
        $voice_grants = 0;
        $avg_wait = null;
        if ($channel == '#an-q') {
            // Count voice grants
            $stmt = $db->prepare("
                SELECT COUNT(*) as grants
                FROM messages
                WHERE channel = '#an-q'
                AND type = 'mode'
                AND json_extract(msg, '$.text') LIKE '%VOICE on%'
                AND strftime('%Y-%m', time/1000, 'unixepoch') = :month
            ");
            $stmt->execute(['month' => $month]);
            $voice_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $voice_grants = $voice_result['grants'];
            
            // Calculate average wait time
            $stmt = $db->prepare("
                SELECT 
                    AVG(wait_time) as avg_wait
                FROM (
                    SELECT 
                        time - LAG(time) OVER (ORDER BY time) as wait_time
                    FROM messages
                    WHERE channel = '#an-q'
                    AND type = 'mode'
                    AND json_extract(msg, '$.text') LIKE '%VOICE on%'
                    AND strftime('%Y-%m', time/1000, 'unixepoch') = :month
                )
                WHERE wait_time IS NOT NULL
            ");
            $stmt->execute(['month' => $month]);
            $wait_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $avg_wait = $wait_result['avg_wait'] ? round($wait_result['avg_wait'] / 60000, 1) : null;
        }
        
        // Peak hour
        $stmt = $db->prepare("
            SELECT strftime('%H', time/1000, 'unixepoch') as hour, COUNT(*) as count
            FROM messages
            WHERE channel = :channel
            AND strftime('%Y-%m', time/1000, 'unixepoch') = :month
            GROUP BY hour
            ORDER BY count DESC
            LIMIT 1
        ");
        $stmt->execute(['month' => $month, 'channel' => $channel]);
        $peak = $stmt->fetch(PDO::FETCH_ASSOC);
        $peak_hour = $peak['hour'] ?? null;
        
        // Peak day
        $stmt = $db->prepare("
            SELECT strftime('%w', time/1000, 'unixepoch') as day, COUNT(*) as count
            FROM messages
            WHERE channel = :channel
            AND strftime('%Y-%m', time/1000, 'unixepoch') = :month
            GROUP BY day
            ORDER BY count DESC
            LIMIT 1
        ");
        $stmt->execute(['month' => $month, 'channel' => $channel]);
        $peak_day_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $peak_day = $peak_day_result['day'] ?? null;
        
        // Top user
        $stmt = $db->prepare("
            SELECT 
                json_extract(msg, '$.from.nick') as nick,
                COUNT(*) as msg_count
            FROM messages
            WHERE channel = :channel
            AND type = 'message'
            AND strftime('%Y-%m', time/1000, 'unixepoch') = :month
            GROUP BY nick
            ORDER BY msg_count DESC
            LIMIT 1
        ");
        $stmt->execute(['month' => $month, 'channel' => $channel]);
        $top_user_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $top_user = $top_user_result['nick'] ?? null;
        $top_user_messages = $top_user_result['msg_count'] ?? null;
        
        // Insert or replace
        $stmt = $db->prepare("
            INSERT OR REPLACE INTO monthly_stats 
            (month, channel, total_messages, unique_users, voice_grants, avg_wait_time, peak_hour, peak_day, top_user, top_user_messages)
            VALUES (:month, :channel, :total_messages, :unique_users, :voice_grants, :avg_wait_time, :peak_hour, :peak_day, :top_user, :top_user_messages)
        ");
        $stmt->execute([
            'month' => $month,
            'channel' => $channel,
            'total_messages' => $stats['total_messages'],
            'unique_users' => $stats['unique_users'],
            'voice_grants' => $voice_grants,
            'avg_wait_time' => $avg_wait,
            'peak_hour' => $peak_hour,
            'peak_day' => $peak_day,
            'top_user' => $top_user,
            'top_user_messages' => $top_user_messages
        ]);
        
        echo "  ✓ $channel: " . number_format($stats['total_messages']) . " messages";
        if ($voice_grants > 0) {
            echo " | $voice_grants invites";
        }
        echo "\n";
        
        $total_processed++;
    }
    
    echo "\n";
}

echo "=====================================\n";
echo "Backfill complete!\n";
echo "Total records created: $total_processed\n";
echo "=====================================\n\n";

// Show summary
$stmt = $db->query("
    SELECT 
        month,
        SUM(total_messages) as total_msgs,
        SUM(voice_grants) as total_invites
    FROM monthly_stats
    GROUP BY month
    ORDER BY month DESC
    LIMIT 12
");

echo "Last 12 Months Summary:\n";
echo str_repeat("-", 50) . "\n";
printf("%-10s | %15s | %10s\n", "Month", "Total Messages", "Invites");
echo str_repeat("-", 50) . "\n";

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    printf("%-10s | %15s | %10s\n", 
        $row['month'], 
        number_format($row['total_msgs']), 
        number_format($row['total_invites'])
    );
}

echo "\n✓ Run 'mam-dashboard.php?tab=history' to view in your dashboard!\n";
?>
