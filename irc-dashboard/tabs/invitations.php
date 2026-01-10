<?php
// INVITATIONS TAB - Track MouseBot Welcome Actions (Successful Invites)

$timeframe = $_GET['timeframe'] ?? '30'; // days
$channel = $_GET['ch'] ?? '#anonamouse.net';

// Available channels
$mam_channels = [
    '#anonamouse.net',
    '#am-members',
    '#an-q',
    '#help',
];

// Calculate timestamp
if ($timeframe == '1h') {
    $since_timestamp = (time() - 3600) * 1000;
} elseif ($timeframe == '365') {
    $since_timestamp = (time() - (365 * 86400)) * 1000;
} else {
    $since_timestamp = (time() - ($timeframe * 86400)) * 1000;
}

// Get all MouseBot welcome actions
$stmt = $db->prepare("
    SELECT 
        datetime(time/1000, 'unixepoch') as timestamp,
        json_extract(msg, '$.text') as text,
        json_extract(msg, '$.users') as users_json,
        time
    FROM messages
    WHERE channel = :channel
    AND json_extract(msg, '$.from.nick') = 'MouseBot'
    AND json_extract(msg, '$.text') LIKE 'welcomes %'
    AND time >= :since
    ORDER BY time DESC
");
$stmt->execute(['channel' => $channel, 'since' => $since_timestamp]);
$welcomes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse welcomes to extract usernames
$invitations = [];
foreach ($welcomes as $welcome) {
    // Extract username from "welcomes USERNAME, handing them..."
    if (preg_match('/^welcomes (\S+),/', $welcome['text'], $matches)) {
        $username = $matches[1];
        $invitations[] = [
            'timestamp' => $welcome['timestamp'],
            'username' => $username,
            'time' => $welcome['time']
        ];
    }
}

// Total invitations
$total_invitations = count($invitations);

// Get today's invitations
$today_start = strtotime('today 00:00:00') * 1000;
$today_invites = array_filter($invitations, fn($inv) => $inv['time'] >= $today_start);
$today_count = count($today_invites);

// Daily breakdown
$daily_stats = [];
foreach ($invitations as $inv) {
    $date = date('Y-m-d', $inv['time'] / 1000);
    if (!isset($daily_stats[$date])) {
        $daily_stats[$date] = 0;
    }
    $daily_stats[$date]++;
}
krsort($daily_stats); // Sort by date descending

// Recent invitations (last 50)
$recent_invitations = array_slice($invitations, 0, 50);

// Timeframe display
$timeframe_display = match($timeframe) {
    '1h' => 'Last Hour',
    '1' => 'Last 24 Hours',
    '7' => 'Last 7 Days',
    '30' => 'Last 30 Days',
    '90' => 'Last 90 Days',
    '365' => 'Last Year',
    default => "Last $timeframe Days"
};

$timeframe_days = ($timeframe == '1h') ? (1/24) : (($timeframe == '365') ? 365 : $timeframe);

// Calculate stats
$avg_daily = $timeframe_days > 0 ? round($total_invitations / $timeframe_days, 1) : 0;
$busiest_day = !empty($daily_stats) ? array_keys($daily_stats, max($daily_stats))[0] : null;
$busiest_count = !empty($daily_stats) ? max($daily_stats) : 0;
?>

<!-- INVITATIONS TAB -->

<!-- Channel selector -->
<div class="channel-selector" style="margin-bottom: 20px;">
    <?php foreach ($mam_channels as $ch): ?>
        <a href="?tab=invitations&ch=<?= urlencode($ch) ?>&timeframe=<?= $timeframe ?>" 
           class="channel-btn <?= $channel == $ch ? 'active' : '' ?>">
           <?= htmlspecialchars($ch) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Timeframe selector -->
<div class="channel-selector" style="margin-bottom: 20px;">
    <a href="?tab=invitations&timeframe=1h&ch=<?= urlencode($channel) ?>" class="channel-btn <?= $timeframe == '1h' ? 'active' : '' ?>">Last Hour</a>
    <a href="?tab=invitations&timeframe=1&ch=<?= urlencode($channel) ?>" class="channel-btn <?= $timeframe == '1' ? 'active' : '' ?>">Last 24h</a>
    <a href="?tab=invitations&timeframe=7&ch=<?= urlencode($channel) ?>" class="channel-btn <?= $timeframe == '7' ? 'active' : '' ?>">Last 7 Days</a>
    <a href="?tab=invitations&timeframe=30&ch=<?= urlencode($channel) ?>" class="channel-btn <?= $timeframe == '30' ? 'active' : '' ?>">Last 30 Days</a>
    <a href="?tab=invitations&timeframe=90&ch=<?= urlencode($channel) ?>" class="channel-btn <?= $timeframe == '90' ? 'active' : '' ?>">Last 90 Days</a>
    <a href="?tab=invitations&timeframe=365&ch=<?= urlencode($channel) ?>" class="channel-btn <?= $timeframe == '365' ? 'active' : '' ?>">Last Year</a>
</div>

<div class="panel">
    <h2>🧀 Successful Invitations</h2>
    <p style="color: #888;">Tracking MouseBot welcomes in <?= htmlspecialchars($channel) ?></p>
    <p style="color: #888; font-size: 12px;"><?= $timeframe_display ?> • Auto-refresh every 60s</p>
</div>

<!-- Stats Grid -->
<div class="grid">
    <div class="panel" style="background: #40444b; border: 2px solid #43b581;">
        <h3 style="color: #43b581;">🎉 Total Invitations</h3>
        <div class="stat-big" style="font-size: 72px; color: #43b581;"><?= number_format($total_invitations) ?></div>
        <p style="color: #888; font-size: 13px; margin-top: 5px;"><?= $timeframe_display ?></p>
    </div>
    
    <div class="panel" style="background: #40444b;">
        <h3 style="color: #5865F2;">Today's Invitations</h3>
        <div class="stat-big" style="font-size: 48px;"><?= number_format($today_count) ?></div>
        <p style="color: #888; font-size: 13px; margin-top: 5px;">Welcomed Today</p>
    </div>
    
    <div class="panel" style="background: #40444b;">
        <h3 style="color: #faa61a;">Average Daily Rate</h3>
        <div class="stat-big" style="font-size: 48px;"><?= $avg_daily ?></div>
        <p style="color: #888; font-size: 13px; margin-top: 5px;">Invites per day</p>
    </div>
    
    <div class="panel" style="background: #40444b;">
        <h3 style="color: #5865F2;">Busiest Day</h3>
        <?php if ($busiest_day): ?>
            <div style="padding: 10px; text-align: center;">
                <div style="color: #5865F2; font-size: 20px; font-weight: bold;"><?= date('M j, Y', strtotime($busiest_day)) ?></div>
                <div style="color: #43b581; font-size: 28px; font-weight: bold; margin-top: 5px;"><?= $busiest_count ?> invites</div>
            </div>
        <?php else: ?>
            <div style="padding: 10px; text-align: center; color: #888;">No data</div>
        <?php endif; ?>
    </div>
</div>

<!-- Daily Breakdown -->
<div class="panel">
    <h2>📊 Daily Invitation Activity</h2>
    <p style="color: #888; margin-bottom: 15px;">Number of successful invitations per day</p>
    <?php if (empty($daily_stats)): ?>
        <p style="color: #888;">No invitations in selected timeframe</p>
    <?php else: ?>
    <table>
        <tr>
            <th>Date</th>
            <th>Invitations</th>
            <th>Activity</th>
        </tr>
        <?php 
        $max_daily = max($daily_stats);
        if ($max_daily == 0) $max_daily = 1;
        foreach ($daily_stats as $date => $count): 
        ?>
        <tr>
            <td><strong><?= date('D, M j', strtotime($date)) ?></strong></td>
            <td><span class="badge" style="background: #43b581;"><?= number_format($count) ?></span></td>
            <td>
                <div style="background: rgba(67, 181, 129, <?= $count / $max_daily ?>); height: 20px; width: <?= ($count / $max_daily) * 200 ?>px; border-radius: 3px;"></div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<!-- Summary Stats -->
<div class="panel">
    <h2>📈 Summary Statistics</h2>
    <div class="grid">
        <div style="background: #40444b; padding: 15px; border-radius: 8px;">
            <h3 style="color: #5865F2; font-size: 14px; margin-bottom: 5px;">Total Welcomed</h3>
            <div style="font-size: 32px; font-weight: bold;"><?= number_format($total_invitations) ?></div>
            <p style="color: #888; font-size: 12px;">Successful invites</p>
        </div>
        <div style="background: #40444b; padding: 15px; border-radius: 8px;">
            <h3 style="color: #5865F2; font-size: 14px; margin-bottom: 5px;">Days with Activity</h3>
            <div style="font-size: 32px; font-weight: bold;"><?= count($daily_stats) ?></div>
            <p style="color: #888; font-size: 12px;">Days with invites</p>
        </div>
        <div style="background: #40444b; padding: 15px; border-radius: 8px;">
            <h3 style="color: #5865F2; font-size: 14px; margin-bottom: 5px;">Peak Day Count</h3>
            <div style="font-size: 32px; font-weight: bold;"><?= $busiest_count ?></div>
            <p style="color: #888; font-size: 12px;">Most in one day</p>
        </div>
        <div style="background: #40444b; padding: 15px; border-radius: 8px;">
            <h3 style="color: #5865F2; font-size: 14px; margin-bottom: 5px;">Tracking</h3>
            <div style="font-size: 14px; margin-top: 10px;">
                <div style="color: #43b581;">✓ MouseBot welcomes</div>
                <div style="color: #888; font-size: 12px;">🧀 Cheese handouts</div>
            </div>
        </div>
    </div>
</div>
