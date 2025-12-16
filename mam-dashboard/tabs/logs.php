<?php
// LOG VIEWER TAB - Logic & View

// Get parameters
$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;
$week = $_GET['week'] ?? null;
$day = $_GET['day'] ?? null;
$channel = $_GET['ch'] ?? '#an-q';

// Initialize arrays
$years = [];
$months = [];
$weeks = [];
$days = [];
$messages = [];

// Get available years
$stmt = $db->query("
    SELECT DISTINCT strftime('%Y', time/1000, 'unixepoch') as year
    FROM messages
    WHERE year IS NOT NULL
    ORDER BY year DESC
");
$years = $stmt->fetchAll(PDO::FETCH_COLUMN);

// If year selected, get months
if ($year) {
    $stmt = $db->prepare("
        SELECT 
            strftime('%m', time/1000, 'unixepoch') as month_num,
            strftime('%Y-%m', time/1000, 'unixepoch') as month_key,
            COUNT(*) as message_count
        FROM messages
        WHERE strftime('%Y', time/1000, 'unixepoch') = :year
        AND channel = :channel
        GROUP BY month_key
        ORDER BY month_num
    ");
    $stmt->execute(['year' => $year, 'channel' => $channel]);
    $months = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// If month selected, get weeks
if ($year && $month) {
    $stmt = $db->prepare("
        SELECT 
            strftime('%W', time/1000, 'unixepoch') as week_num,
            strftime('%Y-W%W', time/1000, 'unixepoch') as week_key,
            MIN(date(time/1000, 'unixepoch')) as week_start,
            MAX(date(time/1000, 'unixepoch')) as week_end,
            COUNT(*) as message_count
        FROM messages
        WHERE strftime('%Y', time/1000, 'unixepoch') = :year
        AND strftime('%m', time/1000, 'unixepoch') = :month
        AND channel = :channel
        GROUP BY week_key
        ORDER BY week_num
    ");
    $stmt->execute(['year' => $year, 'month' => $month, 'channel' => $channel]);
    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// If week selected, get days
if ($year && $week) {
    $stmt = $db->prepare("
        SELECT 
            date(time/1000, 'unixepoch') as date,
            COUNT(*) as message_count,
            COUNT(CASE WHEN type = 'mode' AND json_extract(msg, '$.text') LIKE '%VOICE on%' THEN 1 END) as voice_grants
        FROM messages
        WHERE strftime('%Y-W%W', time/1000, 'unixepoch') = :week
        AND channel = :channel
        GROUP BY date
        ORDER BY date
    ");
    $stmt->execute(['week' => $week, 'channel' => $channel]);
    $days = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// If day selected, get actual messages
if ($day) {
    $stmt = $db->prepare("
        SELECT 
            datetime(time/1000, 'unixepoch') as timestamp,
            type,
            json_extract(msg, '$.from.nick') as nick,
            json_extract(msg, '$.from.mode') as mode,
            json_extract(msg, '$.text') as text
        FROM messages
        WHERE date(time/1000, 'unixepoch') = :day
        AND channel = :channel
        ORDER BY time
    ");
    $stmt->execute(['day' => $day, 'channel' => $channel]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Month names array
$month_names = [
    '01'=>'January', '02'=>'February', '03'=>'March', '04'=>'April',
    '05'=>'May', '06'=>'June', '07'=>'July', '08'=>'August',
    '09'=>'September', '10'=>'October', '11'=>'November', '12'=>'December'
];
?>

<!-- LOG VIEWER VIEW -->

<!-- Channel selector -->
<div class="channel-selector" style="margin-bottom: 20px;">
    <a href="?tab=logs&ch=%23an-q" class="channel-btn <?= $channel == '#an-q' ? 'active' : '' ?>">#an-q</a>
    <a href="?tab=logs&ch=%23anonamouse.net" class="channel-btn <?= $channel == '#anonamouse.net' ? 'active' : '' ?>">#anonamouse.net</a>
    <a href="?tab=logs&ch=%23am-members" class="channel-btn <?= $channel == '#am-members' ? 'active' : '' ?>">#am-members</a>
    <a href="?tab=logs&ch=%23help" class="channel-btn <?= $channel == '#help' ? 'active' : '' ?>">#help</a>
</div>

<!-- Breadcrumb navigation -->
<div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
    <span style="color: #888;">Navigate:</span>
    <a href="?tab=logs&ch=<?= urlencode($channel) ?>" style="color: #5865F2; text-decoration: none; margin: 0 5px;">📅 Years</a>
    <?php if ($year): ?>
        → <a href="?tab=logs&year=<?= $year ?>&ch=<?= urlencode($channel) ?>" style="color: #5865F2; text-decoration: none;"><?= $year ?></a>
    <?php endif; ?>
    <?php if ($month): ?>
        → <a href="?tab=logs&year=<?= $year ?>&month=<?= $month ?>&ch=<?= urlencode($channel) ?>" style="color: #5865F2; text-decoration: none;"><?= $month_names[$month] ?></a>
    <?php endif; ?>
    <?php if ($week): ?>
        → <span style="color: #5865F2;">Week <?= substr($week, -2) ?></span>
    <?php endif; ?>
    <?php if ($day): ?>
        → <span style="color: #5865F2;"><?= date('D, M j', strtotime($day)) ?></span>
    <?php endif; ?>
</div>

<?php if (empty($years)): ?>
    <!-- NO DATA WARNING -->
    <div class="panel" style="border: 2px solid #f04747;">
        <h2 style="color: #f04747;">⚠️ No Data Found</h2>
        <p>No messages found in the database. Make sure the import script completed successfully.</p>
        <p style="margin-top: 10px;">Run: <code>C:\xampp\php\php.exe C:\xampp\htdocs\import-text-logs.php</code></p>
    </div>

<?php elseif ($day): ?>
    <!-- MESSAGE VIEW (Check day FIRST) -->
    <div class="panel">
        <h2>💬 Messages for <?= date('l, F j, Y', strtotime($day)) ?></h2>
        <p style="color: #888; margin-bottom: 15px;">Showing <?= count($messages) ?> messages from <?= htmlspecialchars($channel) ?></p>
        
        <?php if (empty($messages)): ?>
            <p style="color: #888;">No messages found for this day</p>
        <?php else: ?>
        <div style="background: #1a1a1e; border-radius: 8px; padding: 15px; max-height: 600px; overflow-y: auto;">
            <?php foreach ($messages as $msg): ?>
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
                <?php elseif ($msg['type'] == 'join'): ?>
                    <span class="badge" style="background: #43b581;">JOIN</span>
                    <span style="color: #43b581;"><?= htmlspecialchars($msg['text']) ?></span>
                <?php elseif ($msg['type'] == 'part'): ?>
                    <span class="badge" style="background: #f04747;">PART</span>
                    <span style="color: #f04747;"><?= htmlspecialchars($msg['text']) ?></span>
                <?php else: ?>
                    <span class="badge" style="background: #888;">SYSTEM</span>
                    <span style="color: #888;"><?= htmlspecialchars($msg['text']) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

<?php elseif ($week): ?>
    <!-- DAY VIEW (Check week BEFORE month) -->
    <div class="panel">
        <h2>📆 Week <?= substr($week, -2) ?> - Select Day</h2>
        <?php if (empty($days)): ?>
            <p style="color: #888;">No messages found for this week</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Messages</th>
                <?php if ($channel == '#an-q'): ?><th>Voice Grants</th><?php endif; ?>
                <th>Actions</th>
            </tr>
            <?php foreach ($days as $d): ?>
            <tr>
                <td><strong><?= $d['date'] ?></strong></td>
                <td><?= date('l', strtotime($d['date'])) ?></td>
                <td><?= number_format($d['message_count']) ?></td>
                <?php if ($channel == '#an-q'): ?>
                    <td><?= $d['voice_grants'] ?></td>
                <?php endif; ?>
                <td>
                    <a href="?tab=logs&day=<?= $d['date'] ?>&ch=<?= urlencode($channel) ?>" 
                       class="badge badge-voice">View Messages</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>

<?php elseif ($year && $month): ?>
    <!-- WEEK VIEW -->
    <div class="panel">
        <h2>📊 <?= $month_names[$month] ?> <?= $year ?> - Select Week</h2>
        <?php if (empty($weeks)): ?>
            <p style="color: #888;">No messages found for this month</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Week</th>
                <th>Date Range</th>
                <th>Messages</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($weeks as $idx => $w): ?>
            <tr>
                <td><strong>Week <?= $idx + 1 ?></strong></td>
                <td><?= $w['week_start'] ?> to <?= $w['week_end'] ?></td>
                <td><?= number_format($w['message_count']) ?></td>
                <td>
                    <a href="?tab=logs&year=<?= $year ?>&week=<?= $w['week_key'] ?>&ch=<?= urlencode($channel) ?>" 
                       class="badge badge-user">View Days</a>
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
        <?php if (empty($months)): ?>
            <p style="color: #888;">No messages found for this year in <?= htmlspecialchars($channel) ?></p>
        <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach ($months as $m): ?>
            <a href="?tab=logs&year=<?= $year ?>&month=<?= $m['month_num'] ?>&ch=<?= urlencode($channel) ?>" style="text-decoration: none;">
                <div style="background: #40444b; padding: 20px; border-radius: 8px; text-align: center; transition: all 0.2s;"
                     onmouseover="this.style.background='#4752c4'" 
                     onmouseout="this.style.background='#40444b'">
                    <div style="font-size: 24px; font-weight: bold; color: #5865F2;"><?= $month_names[$m['month_num']] ?></div>
                    <div style="color: #888; font-size: 13px; margin-top: 5px;"><?= number_format($m['message_count']) ?> messages</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- YEAR VIEW -->
    <div class="panel">
        <h2>📅 Select Year</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach ($years as $y): ?>
            <a href="?tab=logs&year=<?= $y ?>&ch=<?= urlencode($channel) ?>" style="text-decoration: none;">
                <div style="background: #40444b; padding: 30px; border-radius: 8px; text-align: center; transition: all 0.2s;" 
                     onmouseover="this.style.background='#4752c4'" 
                     onmouseout="this.style.background='#40444b'">
                    <div style="font-size: 36px; font-weight: bold; color: #5865F2;"><?= $y ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
