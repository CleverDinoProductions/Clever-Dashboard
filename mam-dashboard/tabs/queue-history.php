<?php
// ============================================================================
// QUEUE HISTORY TAB - Historical Queue Data by Invite Windows
// ============================================================================

// Handle comparison form submission
if (isset($_GET['compare']) && isset($_GET['compare_win'])) {
    $compare_mode = implode(',', $_GET['compare_win']);
    header("Location: ?tab=queue-history&compare=" . urlencode($compare_mode));
    exit;
}

$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;
$window_date = $_GET['window'] ?? null;
$filter = $_GET['filter'] ?? 'all'; // all, wednesday, saturday
$compare_mode = $_GET['compare'] ?? null;

$queue_channel = '#an-q';
$voice_channel = '#anonamouse.net';

// Parse comparison windows
$compare_windows = [];
if ($compare_mode) {
    $compare_windows = array_filter(explode(',', $compare_mode));
}

// Month names
$month_names = [
    '01'=>'January', '02'=>'February', '03'=>'March', '04'=>'April',
    '05'=>'May', '06'=>'June', '07'=>'July', '08'=>'August',
    '09'=>'September', '10'=>'October', '11'=>'November', '12'=>'December'
];

// Helper to strip mIRC codes
function strip_mirc_codes($text) {
    $text = preg_replace('/\x03(\d{1,2}(,\d{1,2})?)?/', '', $text);
    $text = str_replace(["\x02", "\x0F", "\x16", "\x1F"], '', $text);
    return $text;
}

// Get available years with voice grant data (Wed/Sat START days)
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

// If year selected, get months with activity
$months = [];
if ($year) {
    $stmt = $db->prepare("
        SELECT 
            strftime('%m', time/1000, 'unixepoch') as month_num,
            COUNT(*) as grant_count
        FROM messages
        WHERE channel = :channel
        AND type = 'mode'
        AND json_extract(msg, '$.text') LIKE '+v %'
        AND strftime('%Y', time/1000, 'unixepoch') = :year
        AND strftime('%w', time/1000, 'unixepoch') IN ('3', '6')
        GROUP BY month_num
        ORDER BY month_num
    ");
    $stmt->execute(['channel' => $voice_channel, 'year' => $year]);
    $months = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// If month selected, detect all Wed/Sat windows in that month
$windows = [];
if ($year && $month) {
    $stmt = $db->prepare("
        SELECT DISTINCT 
            date(time/1000, 'unixepoch') as date,
            strftime('%w', time/1000, 'unixepoch') as dow
        FROM messages
        WHERE channel = :channel
        AND type = 'mode'
        AND json_extract(msg, '$.text') LIKE '+v %'
        AND strftime('%Y', time/1000, 'unixepoch') = :year
        AND strftime('%m', time/1000, 'unixepoch') = :month
        AND strftime('%w', time/1000, 'unixepoch') IN ('3', '6')
        ORDER BY date
    ");
    $stmt->execute(['channel' => $voice_channel, 'year' => $year, 'month' => $month]);
    $window_days = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($window_days as $day) {
        $start_time = strtotime($day['date'] . ' 08:00:00 UTC');
        $end_time = $start_time + (25 * 3600);
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(CASE WHEN json_extract(msg, '$.text') LIKE '+v %' THEN 1 END) as entries,
                COUNT(CASE WHEN json_extract(msg, '$.text') LIKE '-v %' THEN 1 END) as exits
            FROM messages
            WHERE channel = :channel
            AND type = 'mode'
            AND time >= :start_time
            AND time <= :end_time
        ");
        $stmt->execute([
            'channel' => $voice_channel,
            'start_time' => $start_time * 1000,
            'end_time' => $end_time * 1000
        ]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $windows[] = [
            'date' => $day['date'],
            'dow' => $day['dow'],
            'type' => ($day['dow'] == '3') ? 'Wed/Thu' : 'Sat/Sun',
            'start' => date('D M j, Y H:i', $start_time) . ' UTC',
            'end' => date('D M j, Y H:i', $end_time) . ' UTC',
            'start_ts' => $start_time,
            'end_ts' => $end_time,
            'entries' => $stats['entries'],
            'exits' => $stats['exits']
        ];
    }
    
    if ($filter == 'wednesday') {
        $windows = array_filter($windows, fn($w) => $w['type'] == 'Wed/Thu');
    } elseif ($filter == 'saturday') {
        $windows = array_filter($windows, fn($w) => $w['type'] == 'Sat/Sun');
    }
}

// If specific window selected, get detailed data
$window_detail = null;
$hourly_data = [];
$wait_times = [];
$recent_entries = [];
$recent_exits = [];
$queue_snapshots = [];
$total_grants = 0;

if ($window_date) {
    $start_time = strtotime($window_date . ' 08:00:00 UTC');
    $end_time = $start_time + (25 * 3600);
    $dow = date('w', $start_time);
    
    $window_detail = [
        'date' => $window_date,
        'type' => ($dow == 3) ? 'Wed/Thu' : 'Sat/Sun',
        'start' => date('l, M j @ H:i', $start_time) . ' UTC',
        'end' => date('l, M j @ H:i', $end_time) . ' UTC',
        'start_ts' => $start_time,
        'end_ts' => $end_time
    ];
    
    $stmt = $db->prepare("
        SELECT 
            COUNT(CASE WHEN json_extract(msg, '$.text') LIKE '+v %' THEN 1 END) as entries,
            COUNT(CASE WHEN json_extract(msg, '$.text') LIKE '-v %' THEN 1 END) as exits
        FROM messages
        WHERE channel = :channel
        AND type = 'mode'
        AND time >= :start_time
        AND time <= :end_time
    ");
    $stmt->execute([
        'channel' => $voice_channel,
        'start_time' => $start_time * 1000,
        'end_time' => $end_time * 1000
    ]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $window_detail = array_merge($window_detail, $stats);
    $total_grants = $stats['entries'];
    
    $stmt = $db->prepare("
        SELECT 
            strftime('%Y-%m-%d %H:00:00', time/1000, 'unixepoch') as hour,
            COUNT(CASE WHEN json_extract(msg, '$.text') LIKE '+v %' THEN 1 END) as entries,
            COUNT(CASE WHEN json_extract(msg, '$.text') LIKE '-v %' THEN 1 END) as exits
        FROM messages
        WHERE channel = :channel
        AND type = 'mode'
        AND time >= :start_time
        AND time <= :end_time
        GROUP BY hour
        ORDER BY hour
    ");
    $stmt->execute([
        'channel' => $voice_channel,
        'start_time' => $start_time * 1000,
        'end_time' => $end_time * 1000
    ]);
    $hourly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Queue snapshots
    $stmt = $db->prepare("
        SELECT 
            time,
            datetime(time/1000, 'unixepoch') as timestamp,
            json_extract(msg, '$.text') as text
        FROM messages
        WHERE channel = :queue_channel
        AND type = 'message'
        AND json_extract(msg, '$.from.nick') = 'MouseBot'
        AND (json_extract(msg, '$.text') LIKE 'Inv queue%'
             OR json_extract(msg, '$.text') LIKE 'Sup queue%')
        AND time >= :start_time
        AND time <= :end_time
        ORDER BY time
    ");
    $stmt->execute([
        'queue_channel' => $queue_channel,
        'start_time' => $start_time * 1000,
        'end_time' => $end_time * 1000
    ]);
    $all_announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $batches = [];
    $current_batch = [];
    $last_time = 0;
    
    foreach ($all_announcements as $announcement) {
        $time_seconds = (int)($announcement['time'] / 1000);
        
        if (!empty($current_batch) && ($time_seconds - $last_time) > 10) {
            $batches[] = $current_batch;
            $current_batch = [];
        }
        
        $current_batch[] = $announcement;
        $last_time = $time_seconds;
    }
    
    if (!empty($current_batch)) {
        $batches[] = $current_batch;
    }
    
    $last_snapshot_time = 0;
    $snapshot_interval = 30 * 60;
    
    foreach ($batches as $batch) {
        $batch_time = (int)($batch[0]['time'] / 1000);
        
        if (($batch_time - $last_snapshot_time) < $snapshot_interval) {
            continue;
        }
        
        $all_members = [];
        $queue_type = null;
        $total_count = 0;
        
        foreach ($batch as $msg) {
            $text = strip_mirc_codes($msg['text']);
            
            if (preg_match('/(Inv|Sup) queue \((\d+)-(\d+) of (\d+)\)[ː:](.*)/', $text, $matches)) {
                if (!$queue_type) {
                    $queue_type = $matches[1];
                }
                
                $total = (int)$matches[4];
                if ($total > $total_count) {
                    $total_count = $total;
                }
                
                $users_text = trim($matches[5]);
                
                if (!empty($users_text)) {
                    preg_match_all('/(\S+)\s+\((\d{2}):(\d{2}):(\d{2})\)/', $users_text, $user_matches, PREG_SET_ORDER);
                    
                    foreach ($user_matches as $user_match) {
                        $username = $user_match[1];
                        $hours = (int)$user_match[2];
                        $minutes = (int)$user_match[3];
                        $seconds = (int)$user_match[4];
                        $wait_minutes = round(($hours * 60) + $minutes + ($seconds / 60), 1);
                        
                        $found = false;
                        foreach ($all_members as $existing) {
                            if ($existing['username'] == $username) {
                                $found = true;
                                break;
                            }
                        }
                        
                        if (!$found) {
                            $all_members[] = [
                                'username' => $username,
                                'wait_minutes' => $wait_minutes
                            ];
                        }
                    }
                }
            }
        }
        
        $queue_snapshots[] = [
            'time' => $batch_time,
            'time_label' => date('D M j, H:i', $batch_time),
            'queue_type' => $queue_type ?? 'Inv',
            'queue_size' => $total_count,
            'members' => $all_members,
            'avg_wait' => !empty($all_members) ? round(array_sum(array_column($all_members, 'wait_minutes')) / count($all_members), 1) : 0
        ];
        
        $last_snapshot_time = $batch_time;
    }
    
    // Calculate wait times
    $stmt = $db->prepare("
        SELECT 
            json_extract(msg, '$.text') as text,
            time
        FROM messages
        WHERE channel = :channel
        AND type = 'mode'
        AND (json_extract(msg, '$.text') LIKE '+v %' 
             OR json_extract(msg, '$.text') LIKE '-v %')
        AND time >= :start_time
        AND time <= :end_time
        ORDER BY time
    ");
    $stmt->execute([
        'channel' => $voice_channel,
        'start_time' => $start_time * 1000,
        'end_time' => $end_time * 1000
    ]);
    $all_mode_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $user_entry_times = [];
    foreach ($all_mode_events as $row) {
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
    
    $avg_wait = !empty($wait_times) ? round(array_sum($wait_times) / count($wait_times), 1) : 0;
    $min_wait = !empty($wait_times) ? min($wait_times) : 0;
    $max_wait = !empty($wait_times) ? max($wait_times) : 0;
    
    // Recent entries
    $stmt = $db->prepare("
        SELECT 
            datetime(time/1000, 'unixepoch') as timestamp,
            json_extract(msg, '$.text') as text
        FROM messages
        WHERE channel = :channel
        AND type = 'mode'
        AND json_extract(msg, '$.text') LIKE '+v %'
        AND time >= :start_time
        AND time <= :end_time
        ORDER BY time DESC
        LIMIT 20
    ");
    $stmt->execute([
        'channel' => $voice_channel,
        'start_time' => $start_time * 1000,
        'end_time' => $end_time * 1000
    ]);
    $recent_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent exits
    $stmt = $db->prepare("
        SELECT 
            datetime(time/1000, 'unixepoch') as timestamp,
            json_extract(msg, '$.text') as text,
            time
        FROM messages
        WHERE channel = :channel
        AND type = 'mode'
        AND json_extract(msg, '$.text') LIKE '-v %'
        AND time >= :start_time
        AND time <= :end_time
        ORDER BY time DESC
        LIMIT 20
    ");
    $stmt->execute([
        'channel' => $voice_channel,
        'start_time' => $start_time * 1000,
        'end_time' => $end_time * 1000
    ]);
    $exits_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($exits_raw as $exit) {
        if (preg_match('/^-v\s+(.+)$/', $exit['text'], $matches)) {
            $username = trim($matches[1]);
            
            $stmt2 = $db->prepare("
                SELECT time
                FROM messages
                WHERE channel = :channel
                AND type = 'mode'
                AND json_extract(msg, '$.text') = :pattern
                AND time < :exit_time
                ORDER BY time DESC
                LIMIT 1
            ");
            $stmt2->execute([
                'channel' => $voice_channel,
                'pattern' => '+v ' . $username,
                'exit_time' => $exit['time']
            ]);
            $entry = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            $wait_time = 0;
            if ($entry) {
                $wait_ms = $exit['time'] - $entry['time'];
                $wait_time = round($wait_ms / 60000, 1);
            }
            
            $recent_exits[] = [
                'timestamp' => $exit['timestamp'],
                'username' => $username,
                'wait_time' => $wait_time
            ];
        }
    }
}
?>

<!-- Breadcrumb -->
<div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
    <span style="color: #888;">Navigate:</span>
    <a href="?tab=queue-history" style="color: #5865F2; text-decoration: none; margin: 0 5px;">📅 Years</a>
    <?php if ($year): ?>
        → <a href="?tab=queue-history&year=<?= $year ?>" style="color: #5865F2; text-decoration: none;"><?= $year ?></a>
    <?php endif; ?>
    <?php if ($month): ?>
        → <a href="?tab=queue-history&year=<?= $year ?>&month=<?= $month ?>" style="color: #5865F2; text-decoration: none;"><?= $month_names[$month] ?></a>
    <?php endif; ?>
    <?php if ($window_date): ?>
        → <span style="color: #5865F2;"><?= date('M j', strtotime($window_date)) ?> Window</span>
    <?php endif; ?>
    <?php if (!empty($compare_windows)): ?>
        → <span style="color: #faa61a;">🔀 Comparing <?= count($compare_windows) ?> Windows</span>
    <?php endif; ?>
</div>

<?php if (empty($years)): ?>
    <div class="panel" style="border: 2px solid #f04747;">
        <h2 style="color: #f04747;">⚠️ No Queue History Found</h2>
        <p>No voice grant data found in <?= htmlspecialchars($voice_channel) ?>.</p>
    </div>

<?php elseif (!empty($compare_windows) && count($compare_windows) >= 2): ?>
    <!-- COMPARISON VIEW -->
    <div class="panel" style="border: 2px solid #5865F2;">
        <h2>📊 Window Comparison Mode</h2>
        <p style="color: #888;">Comparing <?= count($compare_windows) ?> windows</p>
    </div>
    
    <?php
    $comparison_data = [];
    foreach ($compare_windows as $win_date) {
        $start_time = strtotime($win_date . ' 08:00:00 UTC');
        $end_time = $start_time + (25 * 3600);
        $dow = date('w', $start_time);
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(CASE WHEN json_extract(msg, '$.text') LIKE '+v %' THEN 1 END) as entries,
                COUNT(CASE WHEN json_extract(msg, '$.text') LIKE '-v %' THEN 1 END) as exits
            FROM messages
            WHERE channel = :channel
            AND type = 'mode'
            AND time >= :start_time
            AND time <= :end_time
        ");
        $stmt->execute([
            'channel' => $voice_channel,
            'start_time' => $start_time * 1000,
            'end_time' => $end_time * 1000
        ]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $db->prepare("
            SELECT 
                json_extract(msg, '$.text') as text,
                time
            FROM messages
            WHERE channel = :channel
            AND type = 'mode'
            AND (json_extract(msg, '$.text') LIKE '+v %' 
                 OR json_extract(msg, '$.text') LIKE '-v %')
            AND time >= :start_time
            AND time <= :end_time
            ORDER BY time
        ");
        $stmt->execute([
            'channel' => $voice_channel,
            'start_time' => $start_time * 1000,
            'end_time' => $end_time * 1000
        ]);
        $all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $wait_times = [];
        $user_entry_times = [];
        foreach ($all_events as $row) {
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
        
        $stmt = $db->prepare("
            SELECT 
                strftime('%H', time/1000, 'unixepoch') as hour,
                COUNT(*) as count
            FROM messages
            WHERE channel = :channel
            AND type = 'mode'
            AND json_extract(msg, '$.text') LIKE '+v %'
            AND time >= :start_time
            AND time <= :end_time
            GROUP BY hour
            ORDER BY count DESC
            LIMIT 1
        ");
        $stmt->execute([
            'channel' => $voice_channel,
            'start_time' => $start_time * 1000,
            'end_time' => $end_time * 1000
        ]);
        $peak_hour = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $comparison_data[] = [
            'date' => $win_date,
            'label' => date('D M j, Y', $start_time),
            'type' => ($dow == 3) ? 'Wed/Thu' : 'Sat/Sun',
            'entries' => $stats['entries'],
            'exits' => $stats['exits'],
            'avg_wait' => !empty($wait_times) ? round(array_sum($wait_times) / count($wait_times), 1) : 0,
            'min_wait' => !empty($wait_times) ? min($wait_times) : 0,
            'max_wait' => !empty($wait_times) ? max($wait_times) : 0,
            'hourly_rate' => round($stats['entries'] / 25, 1),
            'peak_hour' => $peak_hour ? $peak_hour['hour'] . ':00 UTC (' . $peak_hour['count'] . ' grants)' : 'N/A'
        ];
    }
    ?>
    
    <div class="panel">
        <h2>📈 Comparison Table</h2>
        <table>
            <tr>
                <th>Metric</th>
                <?php foreach ($comparison_data as $win): ?>
                <th style="background: #5865F2;">
                    <?= $win['label'] ?><br>
                    <small><?= $win['type'] ?></small>
                </th>
                <?php endforeach; ?>
                <th style="background: #faa61a;">Δ</th>
            </tr>
            <tr>
                <td><strong>Entries</strong></td>
                <?php foreach ($comparison_data as $win): ?>
                <td style="text-align:center; color:#43b581; font-size:18px;"><strong><?= $win['entries'] ?></strong></td>
                <?php endforeach; ?>
                <td style="text-align:center;">
                    <?php 
                    $diff = $comparison_data[count($comparison_data)-1]['entries'] - $comparison_data[0]['entries'];
                    $pct = $comparison_data[0]['entries'] > 0 ? round(($diff / $comparison_data[0]['entries']) * 100, 1) : 0;
                    ?>
                    <span style="color:<?= $diff >= 0 ? '#43b581' : '#f04747' ?>;">
                        <?= $diff >= 0 ? '+' : '' ?><?= $diff ?> (<?= $diff >= 0 ? '+' : '' ?><?= $pct ?>%)
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Avg Wait</strong></td>
                <?php foreach ($comparison_data as $win): ?>
                <td style="text-align:center; color:#faa61a; font-size:18px;"><strong><?= $win['avg_wait'] ?>min</strong></td>
                <?php endforeach; ?>
                <td style="text-align:center;">
                    <?php 
                    $diff = $comparison_data[count($comparison_data)-1]['avg_wait'] - $comparison_data[0]['avg_wait'];
                    ?>
                    <span style="color:<?= $diff <= 0 ? '#43b581' : '#f04747' ?>;">
                        <?= $diff >= 0 ? '+' : '' ?><?= $diff ?>min
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Max Wait</strong></td>
                <?php foreach ($comparison_data as $win): ?>
                <td style="text-align:center; color:#f04747;"><?= $win['max_wait'] ?>min</td>
                <?php endforeach; ?>
                <td style="text-align:center;">
                    <?php 
                    $diff = $comparison_data[count($comparison_data)-1]['max_wait'] - $comparison_data[0]['max_wait'];
                    ?>
                    <span style="color:<?= $diff <= 0 ? '#43b581' : '#f04747' ?>;">
                        <?= $diff >= 0 ? '+' : '' ?><?= $diff ?>min
                    </span>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="panel">
        <a href="?tab=queue-history" class="badge badge-user">← Back</a>
    </div>

<?php elseif ($window_date): ?>
    <!-- WINDOW DETAIL -->
    <div class="panel">
        <h2>📊 <?= $window_detail['type'] ?> Window</h2>
        <h3 style="color:#888;"><?= $window_detail['start'] ?> → <?= $window_detail['end'] ?></h3>
    </div>
    
    <div class="grid">
        <div class="panel" style="background:#40444b;">
            <h3 style="color:#43b581;">Queue Entries</h3>
            <div style="font-size:48px; font-weight:bold;"><?= $window_detail['entries'] ?></div>
        </div>
        <div class="panel" style="background:#40444b;">
            <h3 style="color:#f04747;">Queue Exits</h3>
            <div style="font-size:48px; font-weight:bold;"><?= $window_detail['exits'] ?></div>
        </div>
        <div class="panel" style="background:#40444b;">
            <h3 style="color:#faa61a;">Avg Wait</h3>
            <div style="font-size:48px; font-weight:bold;"><?= $avg_wait ?>min</div>
        </div>
        <div class="panel" style="background:#40444b;">
            <h3 style="color:#5865F2;">Max Wait</h3>
            <div style="font-size:48px; font-weight:bold;"><?= $max_wait ?>min</div>
        </div>
    </div>
    
    <?php if (!empty($queue_snapshots)): ?>
    <div class="panel">
        <h2>📸 Queue Snapshots (<?= count($queue_snapshots) ?>)</h2>
        <?php foreach ($queue_snapshots as $snap): ?>
        <div style="background:#40444b; padding:15px; margin:10px 0; border-radius:8px;">
            <strong><?= $snap['time_label'] ?></strong> - 
            <?= $snap['queue_size'] ?> people - 
            Avg: <?= $snap['avg_wait'] ?>min
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

<?php elseif ($year && $month): ?>
    <!-- MONTH VIEW -->
    <div class="panel">
        <h2>📊 <?= $month_names[$month] ?> <?= $year ?></h2>
        
        <form method="GET">
            <input type="hidden" name="tab" value="queue-history">
            <button type="submit" class="badge badge-voice" style="padding:10px 20px; border:none; cursor:pointer;">
                🔀 Compare Selected
            </button>
        </form>
        
        <table>
            <tr>
                <th>☑️</th>
                <th>Date</th>
                <th>Type</th>
                <th>Entries</th>
                <th>Exits</th>
                <th></th>
            </tr>
            <?php foreach ($windows as $win): ?>
            <tr>
                <td><input type="checkbox" name="compare_win[]" value="<?= $win['date'] ?>" style="width:20px;height:20px;"></td>
                <td><?= date('D, M j', strtotime($win['date'])) ?></td>
                <td><span class="badge <?= $win['type']=='Wed/Thu'?'badge-user':'badge-voice' ?>"><?= $win['type'] ?></span></td>
                <td><span class="badge" style="background:#43b581;"><?= $win['entries'] ?></span></td>
                <td><span class="badge" style="background:#f04747;"><?= $win['exits'] ?></span></td>
                <td><a href="?tab=queue-history&window=<?= $win['date'] ?>" class="badge badge-voice">View</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

<?php elseif ($year): ?>
    <!-- YEAR VIEW -->
    <div class="panel">
        <h2>📂 <?= $year ?></h2>
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:15px;">
            <?php foreach ($months as $m): ?>
            <a href="?tab=queue-history&year=<?= $year ?>&month=<?= $m['month_num'] ?>" style="text-decoration:none;">
                <div style="background:#40444b; padding:20px; border-radius:8px; text-align:center;">
                    <div style="font-size:24px; color:#5865F2;"><?= $month_names[$m['month_num']] ?></div>
                    <div style="color:#888; font-size:13px;"><?= $m['grant_count'] ?> grants</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

<?php else: ?>
    <!-- ROOT VIEW -->
    <div class="panel">
        <h2>📅 Queue History</h2>
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:15px;">
            <?php foreach ($years as $y): ?>
            <a href="?tab=queue-history&year=<?= $y ?>" style="text-decoration:none;">
                <div style="background:#40444b; padding:30px; border-radius:8px; text-align:center;">
                    <div style="font-size:36px; color:#5865F2;"><?= $y ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
