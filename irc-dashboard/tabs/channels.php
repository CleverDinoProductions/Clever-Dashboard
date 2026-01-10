<?php
// IMPROVED CHANNEL STATS TAB - Logic & View with Mode Badges

// Get selected channel and time range
$selected_channel = $_GET['ch'];
$days = (int)($_GET['days'] ?? 30);
$days = max(1, min(365, $days)); // Limit between 1-365 days

// Get all available channels dynamically - NO time filter to show all channels
$stmt = $db->query("
    SELECT DISTINCT channel, COUNT(*) as msg_count
    FROM messages 
    GROUP BY channel
    ORDER BY msg_count DESC
");
$all_channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Channel-specific stats with growth comparison
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_messages,
        COUNT(DISTINCT json_extract(msg, '$.from.nick')) as unique_users,
        COUNT(CASE WHEN type = 'message' THEN 1 END) as chat_messages,
        COUNT(CASE WHEN type = 'join' THEN 1 END) as joins,
        COUNT(CASE WHEN type = 'part' THEN 1 END) as parts,
        COUNT(CASE WHEN type = 'quit' THEN 1 END) as quits,
        COUNT(CASE WHEN type = 'kick' THEN 1 END) as kicks,
        COUNT(CASE WHEN type = 'mode' THEN 1 END) as mode_changes,
        datetime(MIN(time/1000), 'unixepoch') as first_activity,
        datetime(MAX(time/1000), 'unixepoch') as last_activity,
        ROUND(COUNT(*) * 1.0 / :days, 1) as avg_per_day
    FROM messages 
    WHERE channel = :channel
    AND time > strftime('%s', 'now', '-' || :days || ' days') * 1000
");
$stmt->execute(['channel' => $selected_channel, 'days' => $days]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Previous period comparison
$stmt = $db->prepare("
    SELECT COUNT(*) as prev_messages
    FROM messages 
    WHERE channel = :channel
    AND time BETWEEN strftime('%s', 'now', '-' || (:days * 2) || ' days') * 1000 
                 AND strftime('%s', 'now', '-' || :days || ' days') * 1000
");
$stmt->execute(['channel' => $selected_channel, 'days' => $days]);
$prev_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$growth = $prev_stats['prev_messages'] > 0 
    ? (($stats['total_messages'] - $prev_stats['prev_messages']) / $prev_stats['prev_messages'] * 100) 
    : 0;

// Helper function to get mode symbol and color
function get_mode_badge($mode) {
    if (empty($mode)) return null;
    
    $badges = [
        '~' => ['symbol' => '~', 'title' => 'Owner', 'color' => '#f1c40f'],
        '&' => ['symbol' => '&', 'title' => 'Admin', 'color' => '#e91e63'],
        '@' => ['symbol' => '@', 'title' => 'Op', 'color' => '#e74c3c'],
        '%' => ['symbol' => '%', 'title' => 'Half-Op', 'color' => '#ff9800'],
        '+' => ['symbol' => '+', 'title' => 'Voice', 'color' => '#3498db'],
    ];
    
    return $badges[$mode] ?? null;
}

// Top users with modes and percentage
$stmt = $db->prepare("
    SELECT 
        json_extract(msg, '$.from.nick') as nick,
        json_extract(msg, '$.from.mode') as mode,
        COUNT(*) as messages,
        ROUND(COUNT(*) * 100.0 / :total, 1) as percentage,
        datetime(MAX(time/1000), 'unixepoch') as last_seen
    FROM messages 
    WHERE channel = :channel
    AND type = 'message'
    AND time > strftime('%s', 'now', '-30 days') * 1000
    GROUP BY nick 
    ORDER BY messages DESC 
    LIMIT 15
");
$stmt->execute([
    'channel' => $selected_channel, 
    'total' => max(1, $stats['chat_messages'])
]);
$top_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Most active hours (top 5)
$stmt = $db->prepare("
    SELECT 
        strftime('%H', time/1000, 'unixepoch') as hour,
        COUNT(*) as count
    FROM messages 
    WHERE channel = :channel
    AND type = 'message'
    AND time > strftime('%s', 'now', '-' || :days || ' days') * 1000
    GROUP BY hour
    ORDER BY count DESC
    LIMIT 5
");
$stmt->execute(['channel' => $selected_channel, 'days' => $days]);
$peak_hours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Activity heatmap
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

// Build heatmap
$heatmap = array_fill(0, 0, array_fill(0, 24, 0));
foreach ($heatmap_data as $row) {
    $heatmap[$row['dayofweek']][$row['hour']] = $row['count'];
}
$max_heat = max(array_map('max', $heatmap));
if ($max_heat == 0) $max_heat = 1;
?>

<!-- CHANNEL STATS VIEW -->

<!-- Channel Selector -->
<div class="channel-selector">
    <?php foreach ($all_channels as $ch): ?>
        <a href="?tab=channels&ch=<?= urlencode($ch['channel']) ?>&days=<?= $days ?>" 
           class="channel-btn <?= $selected_channel == $ch['channel'] ? 'active' : '' ?>">
            <?= htmlspecialchars($ch['channel']) ?>
            <span style="opacity: 0.6; font-size: 0.9em;">(<?= number_format($ch['msg_count']) ?>)</span>
        </a>
    <?php endforeach; ?>
</div>

<!-- Time Range Selector -->
<div class="time-selector" style="margin: 15px 0; text-align: center;">
    <a href="?tab=channels&ch=<?= urlencode($selected_channel) ?>&days=1" 
       class="time-btn <?= $days == 1 ? 'active' : '' ?>">1 Day</a>
    <a href="?tab=channels&ch=<?= urlencode($selected_channel) ?>&days=7" 
       class="time-btn <?= $days == 7 ? 'active' : '' ?>">7 Days</a>
    <a href="?tab=channels&ch=<?= urlencode($selected_channel) ?>&days=30" 
       class="time-btn <?= $days == 30 ? 'active' : '' ?>">30 Days</a>
    <a href="?tab=channels&ch=<?= urlencode($selected_channel) ?>&days=90" 
       class="time-btn <?= $days == 90 ? 'active' : '' ?>">90 Days</a>
    <a href="?tab=channels&ch=<?= urlencode($selected_channel) ?>&days=365" 
       class="time-btn <?= $days == 365 ? 'active' : '' ?>">1 Year</a>
</div>

<div class="grid">
    <!-- Overview Stats -->
    <div class="panel">
        <h2><?= htmlspecialchars($selected_channel) ?> Overview</h2>
        <div class="stat-row">
            <span>Total Messages</span>
            <strong>
                <?= number_format($stats['total_messages']) ?>
                <?php if ($growth != 0): ?>
                    <span style="font-size: 0.85em; color: <?= $growth > 0 ? '#57f287' : '#ed4245' ?>;">
                        <?= $growth > 0 ? '▲' : '▼' ?> <?= abs(round($growth, 1)) ?>%
                    </span>
                <?php endif; ?>
            </strong>
        </div>
        <div class="stat-row">
            <span>Chat Messages</span>
            <strong><?= number_format($stats['chat_messages']) ?></strong>
        </div>
        <div class="stat-row">
            <span>Unique Users</span>
            <strong><?= $stats['unique_users'] ?></strong>
        </div>
        <div class="stat-row">
            <span>Avg Messages/Day</span>
            <strong><?= number_format($stats['avg_per_day'], 1) ?></strong>
        </div>
        <div class="stat-row">
            <span>Last Activity</span>
            <strong title="<?= $stats['last_activity'] ?>"><?= time_ago(strtotime($stats['last_activity']) * 1000) ?></strong>
        </div>
    </div>

    <!-- Activity Breakdown -->
    <div class="panel">
        <h2>Activity Breakdown</h2>
        <div class="stat-row">
            <span>💬 Chat Messages</span>
            <strong><?= number_format($stats['chat_messages']) ?></strong>
        </div>
        <div class="stat-row">
            <span>➡️ Joins</span>
            <strong><?= number_format($stats['joins']) ?></strong>
        </div>
        <div class="stat-row">
            <span>⬅️ Parts</span>
            <strong><?= number_format($stats['parts']) ?></strong>
        </div>
        <div class="stat-row">
            <span>🚪 Quits</span>
            <strong><?= number_format($stats['quits']) ?></strong>
        </div>
        <div class="stat-row">
            <span>🦶 Kicks</span>
            <strong><?= number_format($stats['kicks']) ?></strong>
        </div>
        <div class="stat-row">
            <span>⚙️ Mode Changes</span>
            <strong><?= number_format($stats['mode_changes']) ?></strong>
        </div>
    </div>

    <!-- Peak Hours -->
    <div class="panel">
        <h2>Most Active Hours (UTC)</h2>
        <?php foreach ($peak_hours as $i => $hour): ?>
        <div class="stat-row">
            <span><?= $i + 1 ?>. <?= str_pad($hour['hour'], 2, '0', STR_PAD_LEFT) ?>:00</span>
            <strong><?= number_format($hour['count']) ?> msgs</strong>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Activity Heatmap -->
<div class="panel">
    <h2>Activity Heatmap - Last 30 Days</h2>
    <p style="color: #888; margin-bottom: 15px;">
        Hover for details • Peak: <?= number_format($max_heat) ?> messages
    </p>
    <div class="heatmap">
        <div></div>
        <?php for ($h = 0; $h < 24; $h++): ?>
            <div class="heatmap-label"><?= $h ?></div>
        <?php endfor; ?>
        
        <?php $days_labels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; ?>
        <?php for ($d = 0; $d < 7; $d++): ?>
            <div class="heatmap-label"><?= $days_labels[$d] ?></div>
            <?php for ($h = 0; $h < 24; $h++): 
                $value = $heatmap[$d][$h];
                $intensity = $max_heat > 0 ? $value / $max_heat : 0;
                
                // Better color gradient
                if ($intensity > 0.75) $color = '#5865f2'; // Very high
                elseif ($intensity > 0.5) $color = '#7983f5'; // High
                elseif ($intensity > 0.25) $color = '#a3abf8'; // Medium
                elseif ($intensity > 0) $color = '#d4d8fb'; // Low
                else $color = '#2b2d31'; // None
            ?>
                <div class="heatmap-cell" style="background: <?= $color ?>; cursor: pointer;" 
                     title="<?= $days_labels[$d] ?> <?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00 - <?= number_format($value) ?> messages"></div>
            <?php endfor; ?>
        <?php endfor; ?>
    </div>
</div>

<style>
.time-selector {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}
.time-btn {
    padding: 8px 16px;
    background: #2b2d31;
    color: #b5bac1;
    border-radius: 4px;
    text-decoration: none;
    transition: all 0.2s;
}
.time-btn:hover {
    background: #383a40;
}
.time-btn.active {
    background: #5865f2;
    color: white;
}
.progress-bar {
    height: 8px;
    background: #2b2d31;
    border-radius: 4px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #5865f2, #7983f5);
    transition: width 0.3s ease;
}
.heatmap-cell:hover {
    transform: scale(1.1);
    z-index: 10;
    box-shadow: 0 0 8px rgba(88, 101, 242, 0.6);
}
.mode-badge {
    display: inline-block;
    font-family: monospace;
    font-size: 1.1em;
    cursor: help;
    text-shadow: 0 0 2px rgba(0,0,0,0.5);
}
</style>
