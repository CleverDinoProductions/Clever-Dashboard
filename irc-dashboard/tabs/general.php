<?php
// GENERAL STATS TAB - Logic & View

// Total messages today across all channels
$stmt = $db->query("
    SELECT COUNT(*) as total_today 
    FROM messages 
    WHERE date(time/1000, 'unixepoch') = date('now')
");
$general_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Messages this week
$stmt = $db->query("
    SELECT COUNT(*) as total_week 
    FROM messages 
    WHERE time > strftime('%s', 'now', '-7 days') * 1000
");
$week_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Channel comparison
$stmt = $db->query("
    SELECT 
        channel,
        COUNT(*) as messages,
        COUNT(DISTINCT json_extract(msg, '$.from.nick')) as users
    FROM messages 
    WHERE time > strftime('%s', 'now', '-7 days') * 1000
    AND type = 'message'
    GROUP BY channel 
    ORDER BY messages DESC
");
$channel_comparison = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top users network-wide
$stmt = $db->query("
    SELECT 
        json_extract(msg, '$.from.nick') as nick,
        COUNT(*) as messages,
        COUNT(DISTINCT channel) as channels_active
    FROM messages 
    WHERE type = 'message'
    AND time > strftime('%s', 'now', '-7 days') * 1000
    GROUP BY nick 
    ORDER BY messages DESC 
    LIMIT 15
");
$top_users_network = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent announcements from #an-a
$stmt = $db->query("
    SELECT 
        datetime(time/1000, 'unixepoch') as timestamp,
        json_extract(msg, '$.from.nick') as nick,
        json_extract(msg, '$.text') as text
    FROM messages
    WHERE channel = '#an-a'
    AND type = 'message'
    ORDER BY time DESC
    LIMIT 10
");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- GENERAL STATS VIEW -->

<?php if (!empty($announcements)): ?>
<div class="panel" style="border: 2px solid #f39c12; background: #2e3136;">
    <h2 style="color: #f39c12;">📢 Recent Announcements (#an-a)</h2>
    <?php foreach (array_slice($announcements, 0, 5) as $ann): ?>
    <div class="feed-item" style="border-left: 3px solid #f39c12; padding-left: 15px;">
        <span class="timestamp" style="min-width: 130px;"><?= htmlspecialchars($ann['timestamp']) ?></span>
        <span class="badge badge-announce"><?= htmlspecialchars($ann['nick']) ?></span>
        <span style="flex: 1; color: #fff; font-weight: 500;"><?= htmlspecialchars($ann['text']) ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="grid">
    <div class="panel">
        <h2>Messages Today</h2>
        <div class="stat-big"><?= number_format($general_stats['total_today']) ?></div>
        <div class="stat-label">Across All Channels</div>
    </div>
    
    <div class="panel">
        <h2>Messages This Week</h2>
        <div class="stat-big"><?= number_format($week_stats['total_week']) ?></div>
        <div class="stat-label">Last 7 Days</div>
    </div>
</div>

<div class="grid">
    <div class="panel">
        <h2>📈 Channel Comparison (7 Days)</h2>
        <table>
            <tr><th>Channel</th><th>Messages</th><th>Users</th></tr>
            <?php foreach ($channel_comparison as $chan): ?>
            <tr>
                <td><?= htmlspecialchars($chan['channel']) ?></td>
                <td><?= number_format($chan['messages']) ?></td>
                <td><?= $chan['users'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="panel">
        <h2>🏆 Top Users Network-Wide (7 Days)</h2>
        <table>
            <tr><th>User</th><th>Messages</th><th>Channels</th></tr>
            <?php foreach ($top_users_network as $user): ?>
            <tr>
                <td><span class="badge badge-user"><?= htmlspecialchars($user['nick']) ?></span></td>
                <td><?= number_format($user['messages']) ?></td>
                <td><?= $user['channels_active'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
