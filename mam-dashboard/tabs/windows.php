<?php
// INVITE WINDOWS TAB - Hierarchical Navigation with Channel Selector
// Voice grants always from #anonamouse.net, messages from selected channel

$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;
$window_date = $_GET['window'] ?? null;
$filter = $_GET['filter'] ?? 'all'; // all, wednesday, saturday
$channel = $_GET['ch'] ?? '#an-q';

// Available MAM channels
$mam_channels = [
    '#an-q',
    '#anonamouse.net',
    '#am-members',
    '#help',
];

$voice_channel = '#anonamouse.net'; // Voice grants always from here

// Month names
$month_names = [
    '01'=>'January', '02'=>'February', '03'=>'March', '04'=>'April',
    '05'=>'May', '06'=>'June', '07'=>'July', '08'=>'August',
    '09'=>'September', '10'=>'October', '11'=>'November', '12'=>'December'
];

// Get available years with window data (Wed/Sat START days from voice channel)
$stmt = $db->query("
    SELECT DISTINCT strftime('%Y', time/1000, 'unixepoch') as year
    FROM messages
    WHERE channel = '$voice_channel'
    AND type = 'mode'
    AND json_extract(msg, '$.text') LIKE '+v %'
    AND strftime('%w', time/1000, 'unixepoch') IN ('3', '6')
    ORDER BY year DESC
");
$years = $stmt->fetchAll(PDO::FETCH_COLUMN);

// If year selected, get months with windows
$months = [];
if ($year) {
    $stmt = $db->prepare("
        SELECT 
            strftime('%m', time/1000, 'unixepoch') as month_num,
            COUNT(DISTINCT date(time/1000, 'unixepoch')) as window_days
        FROM messages
        WHERE channel = :voice_channel
        AND type = 'mode'
        AND json_extract(msg, '$.text') LIKE '+v %'
        AND strftime('%Y', time/1000, 'unixepoch') = :year
        AND strftime('%w', time/1000, 'unixepoch') IN ('3', '6')
        GROUP BY month_num
        ORDER BY month_num
    ");
    $stmt->execute(['voice_channel' => $voice_channel, 'year' => $year]);
    $months = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// If month selected, get all windows in that month (Wed/Sat START dates)
$windows = [];
if ($year && $month) {
    $stmt = $db->prepare("
        SELECT DISTINCT 
            date(time/1000, 'unixepoch') as date,
            strftime('%w', time/1000, 'unixepoch') as dow
        FROM messages
        WHERE channel = :voice_channel
        AND type = 'mode'
        AND json_extract(msg, '$.text') LIKE '+v %'
        AND strftime('%Y', time/1000, 'unixepoch') = :year
        AND strftime('%m', time/1000, 'unixepoch') = :month
        AND strftime('%w', time/1000, 'unixepoch') IN ('3', '6')
        ORDER BY date
    ");
    $stmt->execute(['voice_channel' => $voice_channel, 'year' => $year, 'month' => $month]);
    $window_days = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($window_days as $day) {
        // Window starts at 08:00 UTC on this date
        $start_time = strtotime($day['date'] . ' 08:00:00 UTC');
        // And runs for 25 hours (into next day)
        $end_time = $start_time + (25 * 3600);
        
        // Get window stats: voice grants from #anonamouse.net, messages from selected channel
        $stmt = $db->prepare("
            SELECT 
                COUNT(CASE WHEN channel = :voice_channel AND type='mode' AND json_extract(msg, '$.text') LIKE '+v %' THEN 1 END) as voice_grants,
                COUNT(CASE WHEN channel = :channel THEN 1 END) as total_messages
            FROM messages
            WHERE (channel = :voice_channel OR channel = :channel)
            AND time >= :start_time
            AND time <= :end_time
        ");
        $stmt->execute([
            'voice_channel' => $voice_channel,
            'channel' => $channel,
            'start_time' => $start_time * 1000,
            'end_time' => $end_time * 1000
        ]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $windows[] = [
            'date' => $day['date'],
            'dow' => $day['dow'],
            'type' => ($day['dow'] == '3') ? 'Wednesday' : 'Saturday',
            'start' => date('D M j, Y H:i', $start_time) . ' UTC',
            'end' => date('D M j, Y H:i', $end_time) . ' UTC',
            'start_ts' => $start_time,
            'end_ts' => $end_time,
            'voice_grants' => $stats['voice_grants'],
            'total_messages' => $stats['total_messages']
        ];
    }
    
    // Apply filter
    if ($filter == 'wednesday') {
        $windows = array_filter($windows, fn($w) => $w['type'] == 'Wednesday');
    } elseif ($filter == 'saturday') {
        $windows = array_filter($windows, fn($w) => $w['type'] == 'Saturday');
    }
}

// If specific window selected, get detailed data
$window_detail = null;
$hourly_data = [];
$window_messages = [];
$wait_times = [];

if ($window_date) {
    $start_time = strtotime($window_date . ' 08:00:00 UTC');
    $end_time = $start_time + (25 * 3600);
    $dow = date('w', $start_time);
    
    $window_detail = [
        'date' => $window_date,
        'type' => ($dow == 3) ? 'Wednesday' : 'Saturday',
        'start' => date('l, M j, Y H:i', $start_time) . ' UTC',
        'end' => date('l, M j, Y H:i', $end_time) . ' UTC',
        'start_ts' => $start_time,
        'end_ts' => $end_time
    ];
    
    // Get overall stats: voice from #anonamouse.net, messages from selected channel
    $stmt = $db->prepare("
        SELECT 
            COUNT(CASE WHEN channel = :voice_channel AND type='mode' AND json_extract(msg, '$.text') LIKE '+v %' THEN 1 END) as voice_grants,
            COUNT(CASE WHEN channel = :channel THEN 1 END) as total_messages
        FROM messages
        WHERE (channel = :voice_channel OR channel = :channel)
        AND time >= :start_time
        AND time <= :end_time
    ");
    $stmt->execute([
        'voice_channel' => $voice_channel,
        'channel' => $channel,
        'start_time' => $start_time * 1000,
        'end_time' => $end_time * 1000
    ]);
    $window_detail = array_merge($window_detail, $stmt->fetch(PDO::FETCH_ASSOC));
    
    // Get hourly breakdown: voice from #anonamouse.net, messages from selected channel
    $stmt = $db->prepare("
        SELECT 
            strftime('%Y-%m-%d %H:00:00', time/1000, 'unixepoch') as hour,
            COUNT(CASE WHEN channel = :voice_channel AND type='mode' AND json_extract(msg, '$.text') LIKE '+v %' THEN 1 END) as voice_grants,
            COUNT(CASE WHEN channel = :channel THEN 1 END) as messages
        FROM messages
        WHERE (channel = :voice_channel OR channel = :channel)
        AND time >= :start_time
        AND time <= :end_time
        GROUP BY hour
        ORDER BY hour
    ");
    $stmt->execute([
        'voice_channel' => $voice_channel,
        'channel' => $channel,
        'start_time' => $start_time * 1000,
        'end_time' => $end_time * 1000
    ]);
    $hourly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate wait times from #anonamouse.net voice grants
    $stmt = $db->prepare("
        SELECT 
            datetime(time/1000, 'unixepoch') as timestamp,
            json_extract(msg, '$.text') as text,
            time
        FROM messages 
        WHERE channel = :voice_channel
        AND type = 'mode'
        AND (json_extract(msg, '$.text') LIKE '+v %' 
             OR json_extract(msg, '$.text') LIKE '-v %')
        AND time >= :start_time
        AND time <= :end_time
        ORDER BY time
    ");
    $stmt->execute([
        'voice_channel' => $voice_channel,
        'start_time' => $start_time * 1000,
        'end_time' => $end_time * 1000
    ]);
    
    $user_entry_times = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $text = trim($row['text']);
        
        if (preg_match('/^\+v\s+(.+)$/', $text, $matches)) {
            $username = trim($matches[1]);
            $user_entry_times[$username] = $row['time'];
        } elseif (preg_match('/^-v\s+(.+)$/', $text, $matches)) {
            $username = trim($matches[1]);
            if (isset($user_entry_times[$username])) {
                $wait_ms = $row['time'] - $user_entry_times[$username];
                $wait_min = round($wait_ms / 60000, 1);
                $wait_times[] = $wait_min;
                unset($user_entry_times[$username]);
            }
        }
    }
    
    // Get sample messages from selected channel
    $stmt = $db->prepare("
        SELECT 
            datetime(time/1000, 'unixepoch') as timestamp,
            type,
            json_extract(msg, '$.from.nick') as nick,
            json_extract(msg, '$.from.mode') as mode,
            json_extract(msg, '$.text') as text
        FROM messages
        WHERE channel = :channel
        AND time >= :start_time
        AND time <= :end_time
        ORDER BY time
        LIMIT 1000
    ");
    $stmt->execute([
        'channel' => $channel,
        'start_time' => $start_time * 1000,
        'end_time' => $end_time * 1000
    ]);
    $window_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!-- INVITE WINDOWS VIEW -->

<!-- Channel selector -->
<div class="channel-selector" style="margin-bottom: 20px;">
    <?php foreach ($mam_channels as $ch): ?>
        <a href="?tab=windows&ch=<?= urlencode($ch) ?><?= $year ? '&year='.$year : '' ?><?= $month ? '&month='.$month : '' ?><?= $window_date ? '&window='.$window_date : '' ?>" 
           class="channel-btn <?= $channel == $ch ? 'active' : '' ?>">
           <?= htmlspecialchars($ch) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Breadcrumb navigation -->
<div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
    <span style="color: #888;">Navigate:</span>
    <a href="?tab=windows&ch=<?= urlencode($channel) ?>" style="color: #5865F2; text-decoration: none; margin: 0 5px;">📅 Years</a>
    <?php if ($year): ?>
        → <a href="?tab=windows&year=<?= $year ?>&ch=<?= urlencode($channel) ?>" style="color: #5865F2; text-decoration: none;"><?= $year ?></a>
    <?php endif; ?>
    <?php if ($month): ?>
        → <a href="?tab=windows&year=<?= $year ?>&month=<?= $month ?>&ch=<?= urlencode($channel) ?>" style="color: #5865F2; text-decoration: none;"><?= $month_names[$month] ?></a>
    <?php endif; ?>
    <?php if ($window_date): ?>
        → <span style="color: #5865F2;"><?= date('M j', strtotime($window_date)) ?> Window</span>
    <?php endif; ?>
</div>

<?php if (empty($years)): ?>
    <!-- NO DATA -->
    <div class="panel" style="border: 2px solid #f04747;">
        <h2 style="color: #f04747;">⚠️ No Window Data Found</h2>
        <p>No invite window data found. Voice grants are tracked in <?= htmlspecialchars($voice_channel) ?>.</p>
        <p style="margin-top: 10px;">Data will appear once invite windows occur and voice grants are logged.</p>
    </div>

<?php elseif ($window_date): ?>
    <!-- WINDOW DETAIL VIEW -->
    
    <div class="panel">
        <h2>🎫 <?= $window_detail['type'] ?> Invite Window</h2>
        <h3 style="color: #888; font-size: 16px; margin-top: 5px;">
            <?= $window_detail['start'] ?> → <?= $window_detail['end'] ?>
        </h3>
        <p style="color: #888; font-size: 14px; margin-top: 5px;">
            Voice grants from <?= htmlspecialchars($voice_channel) ?> • Messages from <?= htmlspecialchars($channel) ?> • 25-hour window
        </p>
        
        <div class="grid" style="margin-top: 20px;">
            <div class="panel" style="background: #40444b;">
                <h3 style="color: #5865F2; font-size: 16px;">Voice Grants</h3>
                <div class="stat-big" style="font-size: 42px;"><?= number_format($window_detail['voice_grants']) ?></div>
                <p style="color: #888; font-size: 12px; margin-top: 5px;">From <?= htmlspecialchars($voice_channel) ?></p>
            </div>
            <div class="panel" style="background: #40444b;">
                <h3 style="color: #5865F2; font-size: 16px;">Total Messages</h3>
                <div class="stat-big" style="font-size: 42px;"><?= number_format($window_detail['total_messages']) ?></div>
                <p style="color: #888; font-size: 12px; margin-top: 5px;">From <?= htmlspecialchars($channel) ?></p>
            </div>
            <div class="panel" style="background: #40444b;">
                <h3 style="color: #5865F2; font-size: 16px;">Avg Wait Time</h3>
                <div class="stat-big" style="font-size: 42px;">
                    <?php if (!empty($wait_times)): ?>
                        <?= round(array_sum($wait_times) / count($wait_times), 1) ?><span style="font-size: 20px;">min</span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel" style="background: #40444b;">
                <h3 style="color: #5865F2; font-size: 16px;">Wait Range</h3>
                <div style="padding: 10px; text-align: center;">
                    <?php if (!empty($wait_times)): ?>
                        <div style="color: #43b581; font-size: 18px; font-weight: bold;">Min: <?= min($wait_times) ?>m</div>
                        <div style="color: #f04747; font-size: 18px; font-weight: bold;">Max: <?= max($wait_times) ?>m</div>
                    <?php else: ?>
                        <div style="color: #888;">No data</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="panel">
        <h2>📈 Hourly Breakdown (25-hour window)</h2>
        <table>
            <tr><th>Hour (UTC)</th><th>Voice Grants</th><th>Messages</th><th>Activity</th></tr>
            <?php 
            $max_grants = !empty($hourly_data) ? max(array_column($hourly_data, 'voice_grants')) : 1;
            if ($max_grants == 0) $max_grants = 1;
            foreach ($hourly_data as $hour): 
            ?>
            <tr>
                <td><strong><?= date('D H:i', strtotime($hour['hour'])) ?></strong></td>
                <td><span class="badge badge-voice"><?= $hour['voice_grants'] ?></span></td>
                <td><?= number_format($hour['messages']) ?></td>
                <td>
                    <div style="background: rgba(88, 101, 242, <?= $hour['voice_grants'] / $max_grants ?>); height: 20px; width: <?= ($hour['voice_grants'] / $max_grants) * 200 ?>px; border-radius: 3px;"></div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="panel">
        <h2>💬 Window Chat Logs from <?= htmlspecialchars($channel) ?></h2>
        <p style="color: #888; margin-bottom: 10px;">Showing <?= count($window_messages) ?> messages from this window</p>
        <div style="background: #1a1a1e; border-radius: 8px; padding: 15px; max-height: 600px; overflow-y: auto;">
            <?php foreach ($window_messages as $msg): ?>
            <div class="feed-item" style="font-family: 'Courier New', monospace; font-size: 13px;">
                <span class="timestamp" style="min-width: 150px;"><?= $msg['timestamp'] ?></span>
                <?php if ($msg['type'] == 'message'): ?>
                    <span class="badge <?= in_array($msg['mode'], ['@','&','~','%']) ? 'badge-staff' : 'badge-user' ?>">
                        <?= $msg['mode'] ?><?= htmlspecialchars($msg['nick']) ?>
                    </span>
                    <span style="color: #dcddde;"><?= htmlspecialchars($msg['text']) ?></span>
                <?php elseif ($msg['type'] == 'mode'): ?>
                    <span class="badge badge-voice">MODE</span>
                    <span style="color: #faa61a;"><?= htmlspecialchars($msg['text']) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php elseif ($year && $month): ?>
    <!-- WINDOWS LIST FOR MONTH -->
    
    <div class="channel-selector">
        <a href="?tab=windows&year=<?= $year ?>&month=<?= $month ?>&filter=all&ch=<?= urlencode($channel) ?>" class="channel-btn <?= $filter == 'all' ? 'active' : '' ?>">All Windows</a>
        <a href="?tab=windows&year=<?= $year ?>&month=<?= $month ?>&filter=wednesday&ch=<?= urlencode($channel) ?>" class="channel-btn <?= $filter == 'wednesday' ? 'active' : '' ?>">Wednesday Only</a>
        <a href="?tab=windows&year=<?= $year ?>&month=<?= $month ?>&filter=saturday&ch=<?= urlencode($channel) ?>" class="channel-btn <?= $filter == 'saturday' ? 'active' : '' ?>">Saturday Only</a>
    </div>
    
    <div class="panel">
        <h2>🎫 Invite Windows - <?= $month_names[$month] ?> <?= $year ?></h2>
        <p style="color: #888; margin-bottom: 15px;">
            All <?= ucfirst($filter) ?> invite windows • Voice grants from <?= htmlspecialchars($voice_channel) ?>
        </p>
        <p style="color: #888; font-size: 12px; margin-bottom: 15px;">
            Wed 08:00 → Thu 09:00 UTC (25h) • Sat 08:00 → Sun 09:00 UTC (25h)
        </p>
        
        <?php if (empty($windows)): ?>
            <p style="color: #888;">No windows found for this period</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Voice Grants</th>
                <th>Messages (<?= htmlspecialchars($channel) ?>)</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($windows as $win): ?>
            <tr>
                <td><strong><?= date('D, M j', strtotime($win['date'])) ?></strong></td>
                <td><span class="badge <?= $win['type'] == 'Wednesday' ? 'badge-user' : 'badge-voice' ?>"><?= $win['type'] ?></span></td>
                <td><?= number_format($win['voice_grants']) ?></td>
                <td><?= number_format($win['total_messages']) ?></td>
                <td>
                    <a href="?tab=windows&window=<?= $win['date'] ?>&ch=<?= urlencode($channel) ?>" class="badge badge-voice">View Window</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>

<?php elseif ($year): ?>
    <!-- MONTH VIEW -->
    
    <div class="panel">
        <h2>📂 <?= $year ?> - Select Month</h2>
        <p style="color: #888; margin-bottom: 15px;">Months with invite window activity</p>
        <?php if (empty($months)): ?>
            <p style="color: #888;">No invite window data for this year</p>
        <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach ($months as $m): ?>
            <a href="?tab=windows&year=<?= $year ?>&month=<?= $m['month_num'] ?>&ch=<?= urlencode($channel) ?>" style="text-decoration: none;">
                <div style="background: #40444b; padding: 20px; border-radius: 8px; text-align: center; transition: all 0.2s;"
                     onmouseover="this.style.background='#4752c4'" 
                     onmouseout="this.style.background='#40444b'">
                    <div style="font-size: 24px; font-weight: bold; color: #5865F2;"><?= $month_names[$m['month_num']] ?></div>
                    <div style="color: #888; font-size: 13px; margin-top: 5px;"><?= $m['window_days'] ?> window start days</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- YEAR VIEW -->
    
    <div class="panel">
        <h2>📅 Invite Windows - Select Year</h2>
        <p style="color: #888; margin-bottom: 15px;">Years with recorded invite window activity</p>
        <?php if (empty($years)): ?>
            <p style="color: #888;">No data available</p>
        <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach ($years as $y): ?>
            <a href="?tab=windows&year=<?= $y ?>&ch=<?= urlencode($channel) ?>" style="text-decoration: none;">
                <div style="background: #40444b; padding: 30px; border-radius: 8px; text-align: center; transition: all 0.2s;" 
                     onmouseover="this.style.background='#4752c4'" 
                     onmouseout="this.style.background='#40444b'">
                    <div style="font-size: 36px; font-weight: bold; color: #5865F2;"><?= $y ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
