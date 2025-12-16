<?php
// CHANNEL STATS TAB - Logic & View

// Get selected channel
$selected_channel = $_GET['ch'] ?? '#anonamouse.net';

// Channel-specific stats
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_messages,
        COUNT(DISTINCT json_extract(msg, '$.from.nick')) as unique_users,
        datetime(MIN(time/1000), 'unixepoch') as first_activity,
        datetime(MAX(time/1000), 'unixepoch') as last_activity
    FROM messages 
    WHERE channel = :channel
    AND time > strftime('%s', 'now', '-30 days') * 1000
");
$stmt->execute(['channel' => $selected_channel]);
$channel_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Top users in channel
$stmt = $db->prepare("
    SELECT 
        json_extract(msg, '$.from.nick') as nick,
        COUNT(*) as messages
    FROM messages 
    WHERE channel = :channel
    AND type = 'message'
    AND time > strftime('%s', 'now', '-7 days') * 1000
    GROUP BY nick 
    ORDER BY messages DESC 
    LIMIT 10
");
$stmt->execute(['channel' => $selected_channel]);
$channel_top_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Peak hours heatmap data
$stmt = $db->prepare("
    SELECT 
        strftime('%w', time/1000, 'unixepoch') as dayofweek,
        strftime('%H', time/1000, 'unixepoch') as hour,
        COUNT(*) as count
    FROM messages 
    WHERE channel = :channel
    AND type = 'message'
    AND time > strftime('%s', 'now', '-30 days') * 1000
    GROUP BY dayofweek, hour
");
$stmt->execute(['channel' => $selected_channel]);
$heatmap_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build heatmap grid
$heatmap = array_fill(0, 7, array_fill(0, 24, 0));
foreach ($heatmap_data as $row) {
    $heatmap[$row['dayofweek']][$row['hour']] = $row['count'];
}
$max_heat = max(array_map('max', $heatmap));
if ($max_heat == 0) $max_heat = 1;
?>

<!-- CHANNEL STATS VIEW -->

<div class="channel-selector">
    <a href="?tab=channels&ch=%23anonamouse.net" class="channel-btn <?= $selected_channel == '#anonamouse.net' ? 'active' : '' ?>">#anonamouse.net</a>
    <a href="?tab=channels&ch=%23an-members" class="channel-btn <?= $selected_channel == '#am-members' ? 'active' : '' ?>">#am-members</a>
    <a href="?tab=channels&ch=%23an-q" class="channel-btn <?= $selected_channel == '#an-q' ? 'active' : '' ?>">#an-q</a>
    <a href="?tab=channels&ch=%23help" class="channel-btn <?= $selected_channel == '#help' ? 'active' : '' ?>">#help</a>
</div>

<div class="grid">
    <div class="panel">
        <h2><?= htmlspecialchars($selected_channel) ?> Stats (30 Days)</h2>
        <div class="stat-row">
            <span>Total Messages</span>
            <strong><?= number_format($channel_stats['total_messages']) ?></strong>
        </div>
        <div class="stat-row">
            <span>Unique Users</span>
            <strong><?= $channel_stats['unique_users'] ?></strong>
        </div>
        <div class="stat-row">
            <span>First Activity</span>
            <strong><?= $channel_stats['first_activity'] ?></strong>
        </div>
        <div class="stat-row">
            <span>Last Activity</span>
            <strong><?= $channel_stats['last_activity'] ?></strong>
        </div>
    </div>

    <div class="panel">
        <h2>Top 10 Users (7 Days)</h2>
        <table>
            <tr><th>User</th><th>Messages</th></tr>
            <?php foreach ($channel_top_users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['nick']) ?></td>
                <td><?= number_format($user['messages']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<div class="panel">
    <h2>Activity Heatmap (Last 30 Days)</h2>
    <p style="color: #888; margin-bottom: 15px;">Darker = More Active</p>
    <div class="heatmap">
        <div></div>
        <?php for ($h = 0; $h < 24; $h++): ?>
            <div class="heatmap-label"><?= $h ?>h</div>
        <?php endfor; ?>
        
        <?php $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; ?>
        <?php for ($d = 0; $d < 7; $d++): ?>
            <div class="heatmap-label"><?= $days[$d] ?></div>
            <?php for ($h = 0; $h < 24; $h++): 
                $value = $heatmap[$d][$h];
                $intensity = $max_heat > 0 ? $value / $max_heat : 0;
                $color = sprintf('rgba(88, 101, 242, %.2f)', $intensity);
            ?>
                <div class="heatmap-cell" style="background: <?= $color ?>" 
                     title="<?= $days[$d] ?> <?= $h ?>:00 - <?= $value ?> messages"></div>
            <?php endfor; ?>
        <?php endfor; ?>
    </div>
</div>
