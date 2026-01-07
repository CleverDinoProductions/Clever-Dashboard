<?php
// INVITE TRACKING TAB - Fixed to properly parse today's MouseBot messages


$timeframe = $_GET['timeframe'] ?? 7;
$voice_channel = '#anonamouse.net'; // Where +v/-v happens
$queue_channel = '#an-q'; // Where MouseBot announces


// Calculate timeframe
$since_timestamp = time() * 1000 - (intval($timeframe) * 86400 * 1000);


// ============================================
// HOLIDAY BREAK DETECTION
// ============================================
$holiday_start = strtotime('2025-12-14 09:00:00 UTC');
$holiday_end = strtotime('2026-01-07 08:00:00 UTC');
$now = time();
$in_holiday_break = ($now >= $holiday_start && $now <= $holiday_end);

// ============================================
// PEAK PERIOD DETECTION
// ============================================
// Last window before break: Sat Dec 13 08:00 - Sun Dec 14 09:00
$last_before_break_start = strtotime('2025-12-13 08:00:00 UTC');
$last_before_break_end = strtotime('2025-12-14 09:00:00 UTC');
$is_last_before_break = ($now >= $last_before_break_start && $now < $last_before_break_end);

// First 3 windows after break
$windows_after_break = [
    ['start' => strtotime('2026-01-07 08:00:00 UTC'), 'end' => strtotime('2026-01-08 09:00:00 UTC'), 'label' => '1st'],
    ['start' => strtotime('2026-01-10 08:00:00 UTC'), 'end' => strtotime('2026-01-11 09:00:00 UTC'), 'label' => '2nd'],
    ['start' => strtotime('2026-01-14 08:00:00 UTC'), 'end' => strtotime('2026-01-15 09:00:00 UTC'), 'label' => '3rd']
];

$is_after_break_window = false;
$after_break_label = '';
foreach ($windows_after_break as $window) {
    if ($now >= $window['start'] && $now < $window['end']) {
        $is_after_break_window = true;
        $after_break_label = $window['label'];
        break;
    }
}


// ============================================
// PARSE LATEST MOUSEBOT QUEUE ANNOUNCEMENTS
// ============================================

$limit = 50;  // Will be adjusted later based on window status

$query_start = microtime(true);
$stmt = $db->prepare("
    SELECT time, datetime(time/1000, 'unixepoch') as timestamp, msg
    FROM messages 
    WHERE channel = ? 
    AND json_extract(msg, '$.from.nick') = 'MouseBot'
    ORDER BY time DESC
    LIMIT ?
");
$stmt->execute([$queue_channel, $limit]);
$all_mousebot = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Extract text and filter for Inv/Sup queues
$inv_messages = [];
$sup_messages = [];


foreach ($all_mousebot as $row) {
    $msg_data = json_decode($row['msg'], true);
    $text = $msg_data['text'] ?? '';
    
    if (stripos($text, 'Inv queue') !== false) {
        $inv_messages[] = [
            'time' => $row['time'],
            'timestamp' => $row['timestamp'],
            'text' => $text
        ];
    } elseif (stripos($text, 'Sup queue') !== false) {
        $sup_messages[] = [
            'time' => $row['time'],
            'timestamp' => $row['timestamp'],
            'text' => $text
        ];
    }
}


// Filter to only the LATEST batch (messages within 5 minutes of the most recent one)
if (!empty($inv_messages)) {
    $latest_time = $inv_messages[0]['time'];
    $inv_messages = array_filter($inv_messages, function($msg) use ($latest_time) {
        return ($latest_time - $msg['time']) < 300000; // Within 5 minutes
    });
}


if (!empty($sup_messages)) {
    $latest_time = $sup_messages[0]['time'];
    $sup_messages = array_filter($sup_messages, function($msg) use ($latest_time) {
        return ($latest_time - $msg['time']) < 300000; // Within 5 minutes
    });
}


// Parse current queue state
$current_queue_state = [
    'inv' => null,
    'sup' => null
];


// Helper function to strip mIRC color codes
function strip_mirc_codes($text) {
    $text = preg_replace('/\x03\d{1,2}(,\d{1,2})?/', '', $text);
    $text = str_replace(["\x02", "\x0F", "\x16", "\x1F"], '', $text);
    return trim($text);
}


// Helper function to calculate next invite window
function get_next_window($skip_holiday = false) {
    date_default_timezone_set('UTC');
    $now = time();
    $current_day = (int)date('w'); // 0=Sun, 3=Wed, 6=Sat
    
    // Holiday period
    $holiday_start = strtotime('2025-12-15 00:00:00 UTC');
    $holiday_end = strtotime('2026-01-05 23:59:59 UTC');
    
    // Check today's windows first
    if ($current_day == 3) { // Wednesday
        $today_window = strtotime('today 08:00');
        if ($now < $today_window && $today_window < $holiday_start) {
            return [
                'timestamp' => $today_window,
                'label' => 'Today!',
                'during_holiday' => false
            ];
        }
    } elseif ($current_day == 6) { // Saturday
        $today_window = strtotime('today 08:00');
        if ($now < $today_window && $today_window < $holiday_start) {
            return [
                'timestamp' => $today_window,
                'label' => 'Today!',
                'during_holiday' => false
            ];
        }
    }
    
    // Find next Wed/Sat window
    $days_until_wed = (4 - $current_day + 7) % 7;
    $days_until_sat = (7 - $current_day + 7) % 7;
    
    if ($days_until_wed == 0) $days_until_wed = 7;
    if ($days_until_sat == 0) $days_until_sat = 7;
    
    $next_wed = strtotime("+$days_until_wed days 08:00");
    $next_sat = strtotime("+$days_until_sat days 08:00");
    
    $next_window = ($next_wed < $next_sat) ? $next_wed : $next_sat;
    $days_away = ($next_wed < $next_sat) ? $days_until_wed : $days_until_sat;
    
    // Check if next window is during holiday
    if ($next_window >= $holiday_start && $next_window <= $holiday_end) {
        // Skip to first window after holiday
        $first_after_holiday = strtotime('2026-01-08 08:00:00 UTC'); // First Wed after Jan 5
        return [
            'timestamp' => $first_after_holiday,
            'label' => 'After Holiday Break',
            'during_holiday' => true
        ];
    }
    
    $label = '';
    if ($days_away == 1) {
        $label = 'Tomorrow';
    }
    
    return [
        'timestamp' => $next_window,
        'label' => $label,
        'during_holiday' => false
    ];
}


// Parse Inv queue (combine multiple messages)
if (!empty($inv_messages)) {
    $all_members = [];
    $total_count = 0;
    $latest_timestamp = null;
    
    foreach ($inv_messages as $msg) {
        $clean_text = strip_mirc_codes($msg['text']);
        
        if (preg_match('/Inv queue \((\d+)-(\d+) of (\d+)\)[ː:](.*)/', $clean_text, $matches)) {
            $total = intval($matches[3]);
            $member_string = trim($matches[4]);
            
            if ($total > $total_count) {
                $total_count = $total;
            }
            if (!$latest_timestamp) {
                $latest_timestamp = $msg['timestamp'];
            }
            
            if (!empty($member_string)) {
                $parts = explode(',', $member_string);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (preg_match('/^(.+?)\s+\((\d{2}):(\d{2}):(\d{2})\)/', $part, $m)) {
                        $username = trim($m[1]);
                        $hours = intval($m[2]);
                        $minutes = intval($m[3]);
                        $seconds = intval($m[4]);
                        $wait_minutes = ($hours * 60) + $minutes + round($seconds / 60, 1);
                        
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
    }
    
    if ($total_count > 0 || !empty($all_members)) {
        $current_queue_state['inv'] = [
            'timestamp' => $latest_timestamp,
            'total' => $total_count,
            'members' => $all_members
        ];
    }
}


// Parse Sup queue (combine multiple messages)
if (!empty($sup_messages)) {
    $all_members = [];
    $total_count = 0;
    $latest_timestamp = null;
    
    foreach ($sup_messages as $msg) {
        $clean_text = strip_mirc_codes($msg['text']);
        
        if (preg_match('/Sup queue \((\d+)-(\d+) of (\d+)\)[ː:](.*)/', $clean_text, $matches)) {
            $total = intval($matches[3]);
            $member_string = trim($matches[4]);
            
            if ($total > $total_count) {
                $total_count = $total;
            }
            if (!$latest_timestamp) {
                $latest_timestamp = $msg['timestamp'];
            }
            
            if (!empty($member_string)) {
                $parts = explode(',', $member_string);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (preg_match('/^(.+?)\s+\((\d{2}):(\d{2}):(\d{2})\)/', $part, $m)) {
                        $username = trim($m[1]);
                        $hours = intval($m[2]);
                        $minutes = intval($m[3]);
                        $seconds = intval($m[4]);
                        $wait_minutes = ($hours * 60) + $minutes + round($seconds / 60, 1);
                        
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
    }
    
    if ($total_count > 0 || !empty($all_members)) {
        $current_queue_state['sup'] = [
            'timestamp' => $latest_timestamp,
            'total' => $total_count,
            'members' => $all_members
        ];
    }
}


// ============================================
// CALCULATE QUEUE STATS FROM +v/-v EVENTS
// ============================================


$stmt = $db->prepare("
    SELECT 
        datetime(time/1000, 'unixepoch') as timestamp,
        time,
        json_extract(msg, '$.text') as text
    FROM messages 
    WHERE channel = ?
    AND type = 'mode'
    AND (json_extract(msg, '$.text') LIKE '%+v%' OR json_extract(msg, '$.text') LIKE '%-v%')
    AND time >= ?
    ORDER BY time
");
$stmt->execute([$voice_channel, $since_timestamp]);
$mode_events = $stmt->fetchAll(PDO::FETCH_ASSOC);


$wait_times = [];
$user_entry_times = [];


foreach ($mode_events as $row) {
    $text = $row['text'];
    
    if (preg_match('/\+v\s+(\S+)/', $text, $matches)) {
        $username = trim($matches[1]);
        $user_entry_times[$username] = $row['time'];
    }
    elseif (preg_match('/-v\s+(\S+)/', $text, $matches)) {
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


$stmt = $db->prepare("
    SELECT COUNT(*) as total
    FROM messages 
    WHERE channel = ?
    AND type = 'mode'
    AND json_extract(msg, '$.text') LIKE '%+v%'
    AND date(time/1000, 'unixepoch') = date('now')
");
$stmt->execute([$voice_channel]);
$today_grants = $stmt->fetch(PDO::FETCH_ASSOC)['total'];


date_default_timezone_set('UTC');
$now_utc = time();
$current_hour_utc = (int)date('G');
$current_dow = (int)date('w');


$in_window = false;
if ($current_dow == 3 && $current_hour_utc >= 8) {
    $in_window = true;
} elseif ($current_dow == 4 && $current_hour_utc < 9) {
    $in_window = true;
} elseif ($current_dow == 6 && $current_hour_utc >= 8) {
    $in_window = true;
} elseif ($current_dow == 0 && $current_hour_utc < 9) {
    $in_window = true;
}


// Override if in holiday break
if ($in_holiday_break) {
    $in_window = false;
}

// Adjust limit retroactively if we're in an active window
if ($in_window) {
    $limit = 30;  // Already fetched 50, but good for next refresh
}

$next_window = get_next_window();
?>


<!-- INVITE TRACKING VIEW -->


<!-- Timeframe selector -->
<div class="channel-selector" style="margin-bottom: 20px;">
    <a href="?tab=invites&timeframe=1" class="channel-btn <?= $timeframe == 1 ? 'active' : '' ?>">Last 24h</a>
    <a href="?tab=invites&timeframe=7" class="channel-btn <?= $timeframe == 7 ? 'active' : '' ?>">Last 7 Days</a>
    <a href="?tab=invites&timeframe=30" class="channel-btn <?= $timeframe == 30 ? 'active' : '' ?>">Last 30 Days</a>
    <a href="?tab=invites&timeframe=90" class="channel-btn <?= $timeframe == 90 ? 'active' : '' ?>">Last 90 Days</a>
</div>


<!-- LAST WINDOW BEFORE BREAK BANNER -->
<?php if ($is_last_before_break): ?>
<div class="panel" style="border: 3px solid #f04747; background: linear-gradient(135deg, #3d0000 0%, #2e3136 100%);">
    <h2 style="color: #f04747; font-size: 26px; text-align: center; margin-bottom: 15px;">⚠️ LAST INVITE DAY OF 2025 ⚠️</h2>
    <p style="color: #dcddde; font-size: 16px; text-align: center; line-height: 1.8;">
        This is the <strong style="color: #f04747;">final invite window</strong> before the holiday break<br>
        <span style="color: #faa61a; font-size: 15px;">⏰ Expect significantly longer wait times than usual</span>
    </p>
    <div style="background: #2c2f33; padding: 15px; border-radius: 8px; margin-top: 15px; text-align: center;">
        <p style="color: #888; font-size: 14px; margin: 0;">
            🎄 No sessions from <strong style="color: #f04747;">Dec 15 - Jan 5</strong><br>
            Next window: <strong style="color: #43b581;">Wednesday, January 8, 2026</strong>
        </p>
    </div>
</div>
<?php endif; ?>


<!-- FIRST WINDOWS AFTER BREAK BANNER -->
<?php if ($is_after_break_window): ?>
<div class="panel" style="border: 3px solid #faa61a; background: linear-gradient(135deg, #3d2800 0%, #2e3136 100%);">
    <h2 style="color: #faa61a; font-size: 26px; text-align: center; margin-bottom: 15px;">⚠️ POST-HOLIDAY RUSH ⚠️</h2>
    <p style="color: #dcddde; font-size: 16px; text-align: center; line-height: 1.8;">
        This is the <strong style="color: #faa61a;"><?= $after_break_label ?> invite window</strong> after the holiday break<br>
        <span style="color: #f04747; font-size: 15px;">⏰ Expect significantly longer wait times than usual</span>
    </p>
    <div style="background: #2c2f33; padding: 15px; border-radius: 8px; margin-top: 15px; text-align: center;">
        <p style="color: #888; font-size: 14px; margin: 0;">
            Pent-up demand from <strong style="color: #faa61a;">3+ weeks of closure</strong><br>
            Wait times should normalize by the 4th window (Jan 18)
        </p>
    </div>
</div>
<?php endif; ?>


<!-- HOLIDAY BREAK BANNER -->
<?php if ($in_holiday_break): ?>
<div class="panel" style="border: 3px solid #faa61a; background: linear-gradient(135deg, #3d2800 0%, #2e3136 100%);">
    <h2 style="color: #faa61a; font-size: 28px; text-align: center; margin-bottom: 20px;">🎄 Holiday Break 🎄</h2>
    <p style="color: #dcddde; font-size: 18px; text-align: center; line-height: 1.8;">
        <strong>No invite sessions are being held</strong><br>
        <span style="color: #888; font-size: 14px;">December 15, 2025 - January 5, 2026</span>
    </p>
    <div style="background: #2c2f33; padding: 20px; border-radius: 8px; margin-top: 20px;">
        <h4 style="color: #5865F2; margin: 0 0 15px 0; text-align: center;">Next Invite Window</h4>
        <p style="color: #43b581; font-size: 20px; text-align: center; margin: 0; font-weight: bold;">
            <?= date('l, M j @ H:i', $next_window['timestamp']) ?> UTC
        </p>
        <p style="color: #888; font-size: 13px; text-align: center; margin-top: 10px;">
            Approximately <?= round(($next_window['timestamp'] - $now) / 86400, 1) ?> days from now
        </p>
    </div>
    <p style="color: #888; font-size: 13px; text-align: center; margin-top: 20px;">
        Please come to a session before or after the break
    </p>
</div>
<?php endif; ?>


<div class="panel">
    <h2>📊 Invite Queue Tracking</h2>
    <p style="color: #888;">Queue data from <?= htmlspecialchars($queue_channel) ?>, voice grants from <?= htmlspecialchars($voice_channel) ?></p>
    <p style="color: #888; font-size: 12px;">Last <?= $timeframe ?> days • Auto-refresh every 60s</p>
</div>


<!-- LIVE QUEUE STATS -->
<div class="grid">
    <div class="panel" style="background: #40444b; border: 2px solid <?= ($current_queue_state['inv'] && $current_queue_state['inv']['total'] > 0) ? '#f04747' : '#43b581' ?>;">
        <h3 style="color: <?= ($current_queue_state['inv'] && $current_queue_state['inv']['total'] > 0) ? '#f04747' : '#43b581' ?>;">LIVE - Inv Queue</h3>
        <div class="stat-big" style="font-size: 72px; color: <?= ($current_queue_state['inv'] && $current_queue_state['inv']['total'] > 0) ? '#f04747' : '#43b581' ?>;">
            <?= $current_queue_state['inv'] ? $current_queue_state['inv']['total'] : 0 ?>
        </div>
        <p style="color: #888; font-size: 13px; margin-top: 5px;">People Waiting Right Now</p>
        <?php if ($current_queue_state['inv']): ?>
        <p style="color: #5865F2; font-size: 11px; margin-top: 10px;">Updated: <?= $current_queue_state['inv']['timestamp'] ?></p>
        <?php endif; ?>
    </div>
    
    <div class="panel" style="background: #40444b;">
        <h3 style="color: #5865F2;">Today's Queue Activity</h3>
        <div class="stat-big" style="font-size: 48px;"><?= number_format($today_grants) ?></div>
        <p style="color: #888; font-size: 13px; margin-top: 5px;">People Joined Today</p>
    </div>
    
    <div class="panel" style="background: #40444b;">
        <h3 style="color: #faa61a;">Average Wait Time</h3>
        <div class="stat-big" style="font-size: 48px;"><?= $avg_wait ?><span style="font-size: 24px;">min</span></div>
        <p style="color: #888; font-size: 13px; margin-top: 5px;">+v to -v • Last <?= $timeframe ?> Days</p>
    </div>
    
    <div class="panel" style="background: #40444b;">
        <h3 style="color: #5865F2;">Queue Range</h3>
        <div style="padding: 10px; text-align: center;">
            <div style="color: #43b581; font-size: 24px; font-weight: bold;">Fastest: <?= $min_wait ?> min</div>
            <div style="color: #f04747; font-size: 24px; font-weight: bold; margin-top: 8px;">Slowest: <?= $max_wait ?> min</div>
        </div>
    </div>
</div>


<!-- CURRENT QUEUE MEMBERS -->
<?php
$all_current_members = [];


if ($current_queue_state['inv'] && !empty($current_queue_state['inv']['members'])) {
    foreach ($current_queue_state['inv']['members'] as $member) {
        $all_current_members[] = [
            'username' => $member['username'],
            'wait_minutes' => $member['wait_minutes'],
            'queue_type' => 'Inv',
            'position' => count($all_current_members) + 1
        ];
    }
}


if ($current_queue_state['sup'] && !empty($current_queue_state['sup']['members'])) {
    foreach ($current_queue_state['sup']['members'] as $member) {
        $all_current_members[] = [
            'username' => $member['username'],
            'wait_minutes' => $member['wait_minutes'],
            'queue_type' => 'Sup',
            'position' => count($all_current_members) + 1
        ];
    }
}

usort($all_current_members, fn($a, $b) => $b['wait_minutes'] <=> $a['wait_minutes']);

if (count($all_current_members) > 200) {
    // Emergency cap: only show first 200
    $all_current_members = array_slice($all_current_members, 0, 200);
    $queue_capped = true;
} else {
    $queue_capped = false;
}

$queue_timestamp = 0;
if ($current_queue_state['inv']) {
    $queue_timestamp = strtotime($current_queue_state['inv']['timestamp']);
} elseif ($current_queue_state['sup']) {
    $queue_timestamp = strtotime($current_queue_state['sup']['timestamp']);
}
$hours_old = $queue_timestamp > 0 ? (time() - $queue_timestamp) / 3600 : 999;
$is_stale = $hours_old > 2;
?>


<?php if (!empty($all_current_members)): ?>
    
    <?php if ($is_stale): ?>
    <div class="panel" style="border: 2px solid #faa61a; background: #40444b;">
        <h2 style="color: #faa61a;">⚠️ Historical Queue Data</h2>
        <p style="color: #dcddde; font-size: 14px;">
            This queue data is from <strong style="color: #faa61a;"><?= round($hours_old, 1) ?> hours ago</strong>
            (<?= $current_queue_state['inv']['timestamp'] ?? $current_queue_state['sup']['timestamp'] ?>)
        </p>
        <p style="color: #dcddde; margin-top: 15px; font-size: 14px;">
            <strong style="color: #5865F2;">Next invite window:</strong> 
            <span style="color: #43b581;">
                <?= date('l, M j @ H:i', $next_window['timestamp']) ?> UTC
                <?= $next_window['label'] ? "({$next_window['label']})" : '' ?>
            </span>
        </p>
        <p style="color: #888; font-size: 12px; margin-top: 10px;">
            Invite windows run Wed 08:00→Thu 09:00 UTC and Sat 08:00→Sun 09:00 UTC (25 hours each)
        </p>
    </div>
    <?php endif; ?>


<div class="panel" style="border: 2px solid #f39c12;">
    <h2 style="color: #faa61a;">👥 Current Queue Members (<?= count($all_current_members) ?>)</h2>
    <p style="color: #888; font-size: 13px; margin-bottom: 15px;">
        People currently waiting in queue (live from MouseBot announcements)
    </p>
    <p style="color: #5865F2; font-size: 12px; margin-bottom: 10px;">
        ⏱️ Live wait times updating - oldest first
    </p>
    
    <table>
        <tr>
            <th>Position</th>
            <th>Username</th>
            <th>Current Wait Time</th>
            <th>Queue Type</th>
            <th>Status</th>
        </tr>
        <?php foreach ($all_current_members as $idx => $member): ?>
        <tr>
            <td><strong style="color: #5865F2;">#<?= $idx + 1 ?></strong></td>
            <td><span class="badge badge-voice"><?= htmlspecialchars($member['username']) ?></span></td>
            <td>
                <span class="badge" style="background: <?= $member['wait_minutes'] < 10 ? '#43b581' : ($member['wait_minutes'] < 30 ? '#faa61a' : '#f04747') ?>;">
                    <?= round($member['wait_minutes'], 1) ?> min
                </span>
            </td>
            <td>
                <span class="badge" style="background: <?= $member['queue_type'] == 'Inv' ? '#43b581' : '#faa61a' ?>;">
                    <?= $member['queue_type'] ?>
                </span>
            </td>
            <td>
                <?php if ($member['wait_minutes'] < 10): ?>
                    <span style="color: #43b581;">✓ Normal</span>
                <?php elseif ($member['wait_minutes'] < 30): ?>
                    <span style="color: #faa61a;">⏳ Moderate Wait</span>
                <?php else: ?>
                    <span style="color: #f04747;">⚠️ Long Wait</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <div style="background: #2c2f33; padding: 15px; border-radius: 8px; margin-top: 20px;">
        <h4 style="color: #5865F2; margin: 0 0 10px 0;">📊 Queue Summary</h4>
        <ul style="margin: 0; padding-left: 20px; color: #888;">
            <li>Total in queue: <strong style="color: #5865F2;"><?= count($all_current_members) ?> people</strong></li>
            <?php if (!empty($all_current_members)): ?>
            <li>Longest wait: <strong style="color: #f04747;"><?= round(max(array_column($all_current_members, 'wait_minutes')), 1) ?> min</strong> (<?= $all_current_members[0]['username'] ?>)</li>
            <li>Newest in queue: <strong style="color: #43b581;"><?= round(min(array_column($all_current_members, 'wait_minutes')), 1) ?> min</strong> (<?= end($all_current_members)['username'] ?>)</li>
            <li>Average wait: <strong style="color: #5865F2;"><?= round(array_sum(array_column($all_current_members, 'wait_minutes')) / count($all_current_members), 1) ?> min</strong></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php if ($is_after_break_window && count($all_current_members) > 50): ?>
<div class="panel" style="border: 2px solid #f04747; background: #2c2f33;">
    <p style="color: #faa61a; text-align: center; margin: 0;">
        ⚠️ <strong>High queue load detected</strong> – refresh rate automatically adjusted
    </p>
</div>
<?php endif; ?>

<?php else: ?>
<div class="panel">
    <h2>👥 Current Queue Members</h2>
    
    <?php if ($in_holiday_break): ?>
        <p style="color: #faa61a; text-align: center; padding: 40px 20px; font-size: 18px;">
            🎄 <strong>Holiday Break</strong><br>
            <span style="font-size: 14px; margin-top: 10px; display: block; color: #888;">
                No interview sessions December 15 - January 5
            </span>
        </p>
    <?php elseif (!$in_window): ?>
        <p style="color: #faa61a; text-align: center; padding: 40px 20px; font-size: 18px;">
            🚫 <strong>Invite Window Closed</strong><br>
            <span style="font-size: 14px; margin-top: 10px; display: block; color: #888;">
                Queues only operate during invite windows
            </span>
        </p>
    <?php else: ?>
        <p style="color: #888; text-align: center; padding: 40px 20px;">
            ✅ Queue is currently empty<br>
            <span style="font-size: 12px; margin-top: 10px; display: block;">No users waiting for invites or support</span>
        </p>
    <?php endif; ?>
    
    <?php if (!$in_holiday_break): ?>
    <p style="color: #5865F2; text-align: center; font-size: 14px; margin-top: 15px;">
        <strong>Next invite window:</strong> 
        <span style="color: #43b581;">
            <?= date('l, M j @ H:i', $next_window['timestamp']) ?> UTC
            <?= $next_window['label'] ? "({$next_window['label']})" : '' ?>
        </span>
    </p>
    <?php endif; ?>
</div>
<?php endif; ?>


<!-- RECENT QUEUE ACTIVITY -->
<?php
$stmt = $db->prepare("
    SELECT 
        datetime(time/1000, 'unixepoch') as timestamp,
        msg
    FROM messages 
    WHERE channel = ?
    AND type = 'mode'
    AND time >= ?
    ORDER BY time DESC 
    LIMIT 40
");
$stmt->execute([$voice_channel, $since_timestamp]);
$recent_modes = $stmt->fetchAll(PDO::FETCH_ASSOC);


$recent_entries = [];
$recent_exits = [];


foreach ($recent_modes as $row) {
    $msg_data = json_decode($row['msg'], true);
    $text = $msg_data['text'] ?? '';
    
    if (stripos($text, '+v') !== false) {
        $recent_entries[] = [
            'timestamp' => $row['timestamp'],
            'text' => $text
        ];
    } elseif (stripos($text, '-v') !== false) {
        $recent_exits[] = [
            'timestamp' => $row['timestamp'],
            'text' => $text
        ];
    }
}


$recent_entries = array_slice($recent_entries, 0, 20);
$recent_exits = array_slice($recent_exits, 0, 20);

$query_duration = microtime(true) - $query_start;
if ($query_duration > 1.0) {
    error_log("SLOW INVITES TAB: {$query_duration}s - timeframe: {$timeframe}");
}
?>


<div class="grid">
    <div class="panel">
        <h2>📥 Recent Queue Entries (+v)</h2>
        <p style="color: #888; font-size: 13px; margin-bottom: 15px;">Latest 20 people who joined queue</p>
        <?php if (empty($recent_entries)): ?>
            <p style="color: #888;">No recent queue entries</p>
        <?php else: ?>
            <table>
                <tr><th>User</th><th>Joined</th></tr>
                <?php foreach ($recent_entries as $entry): ?>
                    <?php if (preg_match('/\+v\s+(\S+)/', $entry['text'], $m)): ?>
                    <tr>
                        <td><span class="badge badge-voice"><?= htmlspecialchars($m[1]) ?></span></td>
                        <td class="timestamp"><?= $entry['timestamp'] ?></td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="panel">
        <h2>📤 Recent Queue Exits (-v)</h2>
        <p style="color: #888; font-size: 13px; margin-bottom: 15px;">Latest 20 people processed</p>
        <?php if (empty($recent_exits)): ?>
            <p style="color: #888;">No recent queue exits</p>
        <?php else: ?>
            <table>
                <tr><th>User</th><th>Processed</th></tr>
                <?php foreach ($recent_exits as $exit): ?>
                    <?php if (preg_match('/-v\s+(\S+)/', $exit['text'], $m)): ?>
                    <tr>
                        <td><span class="badge badge-exit"><?= htmlspecialchars($m[1]) ?></span></td>
                        <td class="timestamp"><?= $exit['timestamp'] ?></td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
<?php if (!$in_holiday_break && $in_window): ?>
    // Adaptive refresh: slower when queue is huge
    const queueSize = <?= count($all_current_members) ?>;
    const refreshInterval = queueSize > 100 ? 120000 : 
                           queueSize > 50  ? 90000  : 60000;
    setTimeout(() => location.reload(), refreshInterval);
<?php else: ?>
    // Slow refresh during break
    setTimeout(() => location.reload(), 300000); // 5 min
<?php endif; ?>
</script>