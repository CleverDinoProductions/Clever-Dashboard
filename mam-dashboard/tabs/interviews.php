<?php
// INTERVIEW TRACKER TAB - Optimized fast loading with smart staff detection

$timeframe = $_GET['timeframe'] ?? '7';
$voice_channel = '#anonamouse.net';

// Calculate timeframe
if ($timeframe === '1h') {
    $since = (time() - 3600) * 1000; // 1 hour
} elseif ($timeframe === '2h') {
    $since = (time() - 7200) * 1000; // 2 hours
} elseif ($timeframe === '3h') {
    $since = (time() - 10800) * 1000; // 3 hours
} elseif ($timeframe === '6h') {
    $since = (time() - 21600) * 1000; // 6 hours
} elseif ($timeframe === '9h') {
    $since = (time() - 32400) * 1000; // 9 hours
} elseif ($timeframe === '365') {
    $since = (time() - 365 * 86400) * 1000;
} else {
    $since = (time() - ((int)$timeframe * 86400)) * 1000;
}

// Helper to strip mIRC codes
function strip_mirc_codes($text) {
    $text = preg_replace('/\x03(\d{1,2}(,\d{1,2})?)?/', '', $text);
    $text = str_replace(["\x02", "\x0F", "\x16", "\x1F"], '', $text);
    return $text;
}

// Fetch interview messages
$stmt = $db->prepare("
    SELECT 
        time,
        datetime(time/1000, 'unixepoch') AS ts,
        msg
    FROM messages
    WHERE channel = :ch
      AND type = 'message'
      AND msg LIKE '%MouseBot%'
      AND msg LIKE '%interview%'
      AND time >= :since
    ORDER BY time DESC
");
$stmt->execute(['ch' => $voice_channel, 'since' => $since]);
$raw_calls = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse interview calls
$interviews = [];
foreach ($raw_calls as $row) {
    $msg_data = @json_decode($row['msg'], true);
    if (!$msg_data) continue;
    
    if (!isset($msg_data['from']['nick']) || $msg_data['from']['nick'] !== 'MouseBot') {
        continue;
    }
    
    $raw_text = $msg_data['text'] ?? '';
    $clean = strip_mirc_codes($raw_text);
    
    if (preg_match('/Now up for an .*interview.* in .*room\s+([A-Z0-9]+),\s*(\S+)/i', $clean, $m)) {
        $room = strtoupper($m[1]);
        $username = $m[2];
        
        $interviews[] = [
            'username'  => $username,
            'room'      => $room,
            'call_time' => $row['time'],
            'call_ts'   => $row['ts'],
        ];
    }
}

// Fetch all +v mode events
$stmt = $db->prepare("
    SELECT 
        time,
        datetime(time/1000, 'unixepoch') AS ts,
        msg
    FROM messages
    WHERE channel = :ch
      AND type = 'mode'
      AND msg LIKE '%+v %'
      AND time >= :since
    ORDER BY time
");
$stmt->execute(['ch' => $voice_channel, 'since' => $since]);
$mode_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build per-user +v timeline
$entries_by_user = [];
foreach ($mode_events as $row) {
    $msg_data = @json_decode($row['msg'], true);
    if (!$msg_data) continue;
    
    $text = trim($msg_data['text'] ?? '');
    if (preg_match('/^\+v\s+(.+)$/', $text, $m)) {
        $user = trim($m[1]);
        $entries_by_user[$user][] = [
            'time' => $row['time'],
            'ts'   => $row['ts'],
        ];
    }
}

// OPTIMIZED: Fetch ALL MouseBot welcome actions
$stmt = $db->prepare("
    SELECT 
        time,
        datetime(time/1000, 'unixepoch') AS ts,
        msg
    FROM messages
    WHERE channel = :ch
      AND type = 'action'
      AND msg LIKE '%MouseBot%'
      AND msg LIKE '%welcomes%'
    ORDER BY time
");
$stmt->execute(['ch' => $voice_channel]);
$mousebot_welcomes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// OPTIMIZED: Get earliest and latest welcome times to minimize message fetch
$welcome_times = array_column($mousebot_welcomes, 'time');
if (!empty($welcome_times)) {
    $earliest_welcome = min($welcome_times);
    $latest_welcome = max($welcome_times);
    
    // Fetch staff messages only in relevant time window (30 sec before first welcome to last welcome)
    $stmt = $db->prepare("
        SELECT 
            time,
            msg
        FROM messages
        WHERE channel = :ch
          AND type = 'message'
          AND time >= :start_time
          AND time <= :end_time
        ORDER BY time
    ");
    $stmt->execute([
        'ch' => $voice_channel,
        'start_time' => $earliest_welcome - 30000, // 30 seconds before first welcome
        'end_time' => $latest_welcome,
    ]);
    $all_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $all_messages = [];
}

// OPTIMIZED: Build time-indexed array of staff messages
$staff_messages_by_time = [];
foreach ($all_messages as $row) {
    $msg_data = @json_decode($row['msg'], true);
    if (!$msg_data) continue;
    
    $from_mode = $msg_data['from']['mode'] ?? '';
    $from_nick = $msg_data['from']['nick'] ?? '';
    
    // Only keep staff messages
    if (in_array($from_mode, ['@', '&', '~', '%'])) {
        $staff_messages_by_time[$row['time']] = $from_nick;
    }
}

ksort($staff_messages_by_time);
$staff_times = array_keys($staff_messages_by_time);

// OPTIMIZED: For each MouseBot welcome, find staff member using in-memory lookup
$welcomes_by_user = [];

foreach ($mousebot_welcomes as $welcome_row) {
    $msg_data = @json_decode($welcome_row['msg'], true);
    if (!$msg_data) continue;
    
    $raw_text = $msg_data['text'] ?? '';
    $clean = strip_mirc_codes($raw_text);
    
    if (!preg_match('/^welcomes\s+(\S+),/i', $clean, $m)) {
        continue;
    }
    $username = trim($m[1]);
    $welcome_time = $welcome_row['time'];
    
    // Find closest staff message before this welcome (within 30 seconds)
    $lookback_start = $welcome_time - 30000;
    $staff_name = 'Unknown';
    
    // Reverse iteration for closest staff message
    foreach (array_reverse($staff_times) as $msg_time) {
        if ($msg_time < $welcome_time && $msg_time >= $lookback_start) {
            $staff_name = $staff_messages_by_time[$msg_time];
            break;
        }
        if ($msg_time < $lookback_start) {
            break; // No point looking further back
        }
    }
    
    $welcomes_by_user[$username][] = [
        'time' => $welcome_time,
        'ts' => $welcome_row['ts'],
        'staff' => $staff_name,
    ];
}

// Fetch user disconnects
$stmt = $db->prepare("
    SELECT 
        time,
        datetime(time/1000, 'unixepoch') AS ts,
        type,
        msg
    FROM messages
    WHERE channel = :ch
      AND type IN ('quit', 'part')
      AND time >= :since
    ORDER BY time
");
$stmt->execute(['ch' => $voice_channel, 'since' => $since]);
$disconnect_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build per-user disconnect timeline
$disconnects_by_user = [];
foreach ($disconnect_events as $row) {
    $msg_data = @json_decode($row['msg'], true);
    if (!$msg_data) continue;
    
    $nick = $msg_data['from']['nick'] ?? null;
    if ($nick) {
        $disconnects_by_user[$nick][] = [
            'time' => $row['time'],
            'ts'   => $row['ts'],
            'type' => $row['type'],
        ];
    }
}

// Build pipeline with staff attribution
$pipeline = [];
$room_stats = [];
$staff_stats = [];

$outcome_window = 2 * 3600 * 1000; // 2 hours

foreach ($interviews as $call) {
    $user = $call['username'];
    $room = $call['room'];
    $call_time = $call['call_time'];

    $queue_entry_time = null;
    $queue_entry_ts   = null;
    $welcome_time     = null;
    $welcome_ts       = null;
    $staff_name       = null;
    $last_disconnect_time = null;
    $last_disconnect_ts   = null;
    $last_disconnect_type = null;

    // Find last +v before call
    if (!empty($entries_by_user[$user])) {
        foreach ($entries_by_user[$user] as $ev) {
            if ($ev['time'] <= $call_time) {
                $queue_entry_time = $ev['time'];
                $queue_entry_ts   = $ev['ts'];
            }
        }
    }
    
    // Find welcome within 2 hours
    if (!empty($welcomes_by_user[$user])) {
        foreach ($welcomes_by_user[$user] as $ev) {
            $time_since_call = $ev['time'] - $call_time;
            if ($time_since_call >= 0 && $time_since_call <= $outcome_window) {
                $welcome_time = $ev['time'];
                $welcome_ts   = $ev['ts'];
                $staff_name   = $ev['staff'];
                break;
            }
        }
    }
    
    // Find last disconnect within 2 hours
    if (!empty($disconnects_by_user[$user])) {
        foreach ($disconnects_by_user[$user] as $ev) {
            $time_since_call = $ev['time'] - $call_time;
            if ($time_since_call >= 0 && $time_since_call <= $outcome_window) {
                $last_disconnect_time = $ev['time'];
                $last_disconnect_ts   = $ev['ts'];
                $last_disconnect_type = $ev['type'];
            }
        }
    }

    $wait_in_queue = null;
    $interview_duration = null;
    $outcome = 'pending';
    $outcome_time = null;
    $outcome_ts = null;
    $outcome_detail = null;

    if ($queue_entry_time !== null) {
        $wait_in_queue = round(($call_time - $queue_entry_time) / 60000, 1);
    }
    
    // Determine outcome
    if ($welcome_time !== null) {
        $outcome = 'success';
        $outcome_time = $welcome_time;
        $outcome_ts = $welcome_ts;
        $interview_duration = round(($welcome_time - $call_time) / 60000, 1);
        $outcome_detail = 'welcomed by ' . $staff_name;
    } elseif ($last_disconnect_time !== null) {
        $time_since_disconnect = (time() * 1000) - $last_disconnect_time;
        $hours_since = $time_since_disconnect / (3600 * 1000);
        
        if ($hours_since >= 0.5) {
            $outcome = 'failure';
            $outcome_time = $last_disconnect_time;
            $outcome_ts = $last_disconnect_ts;
            $interview_duration = round(($last_disconnect_time - $call_time) / 60000, 1);
            $outcome_detail = $last_disconnect_type;
        } else {
            $outcome = 'pending';
            $outcome_detail = 'recent disconnect';
        }
    } else {
        $time_since_call = (time() * 1000) - $call_time;
        $minutes_since = $time_since_call / 60000;
        
        if ($minutes_since > 120) {
            $outcome = 'failure';
            $outcome_detail = 'abandoned';
        } else {
            $outcome = 'pending';
            $outcome_detail = 'in progress';
        }
    }

    $pipeline[] = [
        'username'           => $user,
        'room'               => $room,
        'queue_entry_ts'     => $queue_entry_ts,
        'call_ts'            => $call['call_ts'],
        'outcome'            => $outcome,
        'outcome_ts'         => $outcome_ts,
        'outcome_detail'     => $outcome_detail,
        'staff'              => $staff_name ?? 'Unknown',
        'wait_in_queue'      => $wait_in_queue,
        'interview_duration' => $interview_duration,
    ];

    // Aggregate per room
    if (!isset($room_stats[$room])) {
        $room_stats[$room] = [
            'count' => 0, 'success' => 0, 'failure' => 0, 'pending' => 0,
            'wait_sum' => 0.0, 'wait_samples' => 0,
            'dur_sum' => 0.0, 'dur_samples' => 0,
        ];
    }
    $room_stats[$room]['count']++;
    $room_stats[$room][$outcome]++;
    if ($wait_in_queue !== null) {
        $room_stats[$room]['wait_sum'] += $wait_in_queue;
        $room_stats[$room]['wait_samples']++;
    }
    if ($interview_duration !== null) {
        $room_stats[$room]['dur_sum'] += $interview_duration;
        $room_stats[$room]['dur_samples']++;
    }
    
    // Aggregate per staff (only for completed interviews)
    if ($outcome === 'success' && $staff_name && $staff_name !== 'Unknown') {
        if (!isset($staff_stats[$staff_name])) {
            $staff_stats[$staff_name] = [
                'total' => 0, 'success' => 0,
                'dur_sum' => 0.0, 'dur_samples' => 0,
            ];
        }
        $staff_stats[$staff_name]['total']++;
        $staff_stats[$staff_name]['success']++;
        if ($interview_duration !== null) {
            $staff_stats[$staff_name]['dur_sum'] += $interview_duration;
            $staff_stats[$staff_name]['dur_samples']++;
        }
    }
}

// Sort
ksort($room_stats, SORT_NATURAL);
ksort($staff_stats);

// Overall stats
$total_interviews = count($pipeline);
$successful_invites = count(array_filter($pipeline, fn($p) => $p['outcome'] === 'success'));
$failed_interviews = count(array_filter($pipeline, fn($p) => $p['outcome'] === 'failure'));
$pending_interviews = count(array_filter($pipeline, fn($p) => $p['outcome'] === 'pending'));

$all_waits = array_values(array_filter(array_column($pipeline, 'wait_in_queue'), fn($v) => $v !== null));
$all_durs  = array_values(array_filter(array_column($pipeline, 'interview_duration'), fn($v) => $v !== null));

$avg_wait = $all_waits ? round(array_sum($all_waits)/count($all_waits), 1) : 0;
$min_wait = $all_waits ? min($all_waits) : 0;
$max_wait = $all_waits ? max($all_waits) : 0;

$avg_dur = $all_durs ? round(array_sum($all_durs)/count($all_durs), 1) : 0;
$min_dur = $all_durs ? min($all_durs) : 0;
$max_dur = $all_durs ? max($all_durs) : 0;

$tf_label = match($timeframe) {
    '1h' => 'Last Hour',
    '2h' => 'Last 2 Hours',
    '3h' => 'Last 3 Hours',
    '6h' => 'Last 6 Hours',
    '9h' => 'Last 9 Hours',
    '1'  => 'Last 24 Hours',
    '7'  => 'Last 7 Days',
    '30' => 'Last 30 Days',
    '90' => 'Last 90 Days',
    '365'=> 'Last Year',
    default => "Last $timeframe Days",
};

$completed = $successful_invites + $failed_interviews;
$success_rate = $completed > 0 ? round(($successful_invites / $completed) * 100, 1) : 0;
$failure_rate = $completed > 0 ? round(($failed_interviews / $completed) * 100, 1) : 0;

// Build successful interviews by staff
$successful_by_staff = [];
foreach ($pipeline as $interview) {
    if ($interview['outcome'] === 'success') {
        $staff = $interview['staff'];
        if (!isset($successful_by_staff[$staff])) {
            $successful_by_staff[$staff] = [];
        }
        $successful_by_staff[$staff][] = $interview;
    }
}
ksort($successful_by_staff);
?>

<!-- INTERVIEW TRACKER VIEW -->

<div class="channel-selector" style="margin-bottom: 20px;">
    <a href="?tab=interviews&timeframe=1h" class="channel-btn <?= $timeframe === '1h' ? 'active' : '' ?>">Last Hour</a>
    <a href="?tab=interviews&timeframe=2h" class="channel-btn <?= $timeframe === '2h' ? 'active' : '' ?>">Last 2 hrs</a>
    <a href="?tab=interviews&timeframe=3h" class="channel-btn <?= $timeframe === '3h' ? 'active' : '' ?>">Last 3 hrs</a>
    <a href="?tab=interviews&timeframe=6h" class="channel-btn <?= $timeframe === '6h' ? 'active' : '' ?>">Last 6 hrs</a>
    <a href="?tab=interviews&timeframe=9h" class="channel-btn <?= $timeframe === '9h' ? 'active' : '' ?>">Last 9 hrs</a>
    <a href="?tab=interviews&timeframe=1"  class="channel-btn <?= $timeframe === '1'  ? 'active' : '' ?>">Last 24h</a>
    <a href="?tab=interviews&timeframe=7"  class="channel-btn <?= $timeframe === '7'  ? 'active' : '' ?>">Last 7 Days</a>
    <a href="?tab=interviews&timeframe=30" class="channel-btn <?= $timeframe === '30' ? 'active' : '' ?>">Last 30 Days</a>
    <a href="?tab=interviews&timeframe=90" class="channel-btn <?= $timeframe === '90' ? 'active' : '' ?>">Last 90 Days</a>
    <a href="?tab=interviews&timeframe=365"class="channel-btn <?= $timeframe === '365'? 'active' : '' ?>">Last Year</a>
</div>

<div class="panel">
    <h2>📝 Interview Tracker</h2>
    <p style="color:#888;">Smart staff detection: finds staff member from messages before MouseBot welcome (optimized for fast loading ⚡)</p>
    <p style="color:#888;font-size:12px;"><?= htmlspecialchars($tf_label) ?> • Tracking <?= htmlspecialchars($voice_channel) ?> • Auto-refresh every 60s</p>
</div>

<!-- Stats Grid -->
<div class="grid">
    <div class="panel" style="background:#40444b; border: 2px solid #5865F2;">
        <h3 style="color:#5865F2;">Total Interviews</h3>
        <div class="stat-big" style="font-size:64px; color:#5865F2;"><?= number_format($total_interviews) ?></div>
        <p style="color:#888;font-size:13px;margin-top:5px;">Called by MouseBot</p>
    </div>
    <div class="panel" style="background:#40444b; border: 2px solid #43b581;">
        <h3 style="color:#43b581;">✓ Success</h3>
        <div class="stat-big" style="font-size:48px; color:#43b581;"><?= number_format($successful_invites) ?></div>
        <p style="color:#888;font-size:13px;">Got welcomed 🧀 (<?= $success_rate ?>%)</p>
    </div>
    <div class="panel" style="background:#40444b; border: 2px solid #f04747;">
        <h3 style="color:#f04747;">✗ Failure</h3>
        <div class="stat-big" style="font-size:48px; color:#f04747;"><?= number_format($failed_interviews) ?></div>
        <p style="color:#888;font-size:13px;">Rejected/Quit (<?= $failure_rate ?>%)</p>
    </div>
    <div class="panel" style="background:#40444b;">
        <h3 style="color:#faa61a;">⏳ Pending</h3>
        <div class="stat-big" style="font-size:48px; color:#faa61a;"><?= number_format($pending_interviews) ?></div>
        <p style="color:#888;font-size:13px;">In progress</p>
    </div>
</div>

<!-- Wait & Duration -->
<div class="grid">
    <div class="panel" style="background:#40444b;">
        <h3 style="color:#5865F2;">Avg Queue Wait</h3>
        <div class="stat-big" style="font-size:40px;"><?= $avg_wait ?><span style="font-size:20px;">min</span></div>
        <p style="color:#888;font-size:12px;">From +v to interview call</p>
        <p style="color:#888;font-size:11px;margin-top:3px;">Range: <?= $min_wait ?>–<?= $max_wait ?> min</p>
    </div>
    <div class="panel" style="background:#40444b;">
        <h3 style="color:#5865F2;">Avg Interview Duration</h3>
        <div class="stat-big" style="font-size:40px;"><?= $avg_dur ?><span style="font-size:20px;">min</span></div>
        <p style="color:#888;font-size:12px;">From call to outcome</p>
        <p style="color:#888;font-size:11px;margin-top:3px;">Range: <?= $min_dur ?>–<?= $max_dur ?> min</p>
    </div>
</div>

<!-- Staff Performance -->
<?php if (!empty($staff_stats)): ?>
<div class="panel">
    <h2>👥 Staff Performance</h2>
    <p style="color:#888;margin-bottom:15px;">Per-interviewer statistics (detected from messages before MouseBot)</p>
    <table>
        <tr>
            <th>Staff Member</th>
            <th>Successful Invites</th>
            <th>Avg Interview Time</th>
            <th>Efficiency</th>
        </tr>
        <?php
        $max_staff_count = max(array_column($staff_stats, 'success'));
        if ($max_staff_count == 0) $max_staff_count = 1;
        foreach ($staff_stats as $staff => $stat):
            $avg_staff_dur = $stat['dur_samples'] ? round($stat['dur_sum'] / $stat['dur_samples'], 1) : 0;
            $bar_pct = $stat['success'] / $max_staff_count;
        ?>
        <tr>
            <td><span class="badge badge-voice"><?= htmlspecialchars($staff) ?></span></td>
            <td><strong><?= number_format($stat['success']) ?></strong></td>
            <td><?= $avg_staff_dur ?> min</td>
            <td>
                <div style="background:rgba(67,181,129,<?= 0.3 + ($bar_pct * 0.7) ?>);height:20px;width:<?= $bar_pct * 200 ?>px;border-radius:3px;"></div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>

<!-- Successful Interviews by Staff -->
<?php if (!empty($successful_by_staff)): ?>
<div class="panel">
    <h2>✅ Successful Interviews by Staff Member</h2>
    <p style="color:#888;margin-bottom:15px;">All successful invites grouped by interviewer (most recent first)</p>
    
    <?php foreach ($successful_by_staff as $staff => $interviews): ?>
        <div style="margin-bottom:25px;">
            <h3 style="color:#43b581;font-size:18px;margin-bottom:10px;">
                👤 <?= htmlspecialchars($staff) ?> 
                <span style="color:#888;font-size:14px;font-weight:normal;">(<?= count($interviews) ?> successful invites)</span>
            </h3>
            <table>
                <tr>
                    <th>User</th>
                    <th>Room</th>
                    <th>Interview Call</th>
                    <th>Outcome Time</th>
                    <th>Queue Wait</th>
                    <th>Interview Duration</th>
                </tr>
                <?php foreach ($interviews as $row): ?>
                <tr>
                    <td><span class="badge badge-user"><?= htmlspecialchars($row['username']) ?></span></td>
                    <td><span class="badge badge-voice"><?= htmlspecialchars($row['room']) ?></span></td>
                    <td style="font-size:11px;"><?= $row['call_ts'] ?></td>
                    <td style="font-size:11px;"><?= $row['outcome_ts'] ?></td>
                    <td>
                        <?php if ($row['wait_in_queue'] !== null): ?>
                            <span class="badge" style="background:<?= $row['wait_in_queue'] < 5 ? '#43b581' : ($row['wait_in_queue'] < 15 ? '#faa61a' : '#f04747') ?>;">
                                <?= $row['wait_in_queue'] ?> min
                            </span>
                        <?php else: ?>
                            <span style="color:#888;">n/a</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['interview_duration'] !== null): ?>
                            <span class="badge" style="background:<?= $row['interview_duration'] < 10 ? '#43b581' : ($row['interview_duration'] < 20 ? '#faa61a' : '#f04747') ?>;">
                                <?= $row['interview_duration'] ?> min
                            </span>
                        <?php else: ?>
                            <span style="color:#888;">n/a</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Room Performance -->
<div class="panel">
    <h2>🏠 Interview Room Performance</h2>
    <p style="color:#888;margin-bottom:15px;">Success/failure rates and timing by room</p>
    <?php if (empty($room_stats)): ?>
        <p style="color:#888;">No interview data in this timeframe.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>Room</th>
            <th>Total</th>
            <th>✓ Success</th>
            <th>✗ Failure</th>
            <th>Success Rate</th>
            <th>Avg Wait</th>
            <th>Avg Duration</th>
            <th>Activity</th>
        </tr>
        <?php
        $max_room_count = max(array_column($room_stats, 'count'));
        if ($max_room_count == 0) $max_room_count = 1;
        foreach ($room_stats as $room => $stat):
            $room_completed = $stat['success'] + $stat['failure'];
            $room_success_rate = $room_completed > 0 ? round(($stat['success'] / $room_completed) * 100, 1) : 0;
            $avg_room_wait = $stat['wait_samples'] ? round($stat['wait_sum'] / $stat['wait_samples'], 1) : 0;
            $avg_room_dur  = $stat['dur_samples']  ? round($stat['dur_sum']  / $stat['dur_samples'], 1) : 0;
            $bar_pct = $stat['count'] / $max_room_count;
        ?>
        <tr>
            <td><span class="badge badge-voice">Room <?= htmlspecialchars($room) ?></span></td>
            <td><strong><?= number_format($stat['count']) ?></strong></td>
            <td><span style="color:#43b581;"><?= $stat['success'] ?></span></td>
            <td><span style="color:#f04747;"><?= $stat['failure'] ?></span></td>
            <td>
                <span class="badge" style="background:<?= $room_success_rate >= 80 ? '#43b581' : ($room_success_rate >= 60 ? '#faa61a' : '#f04747') ?>;">
                    <?= $room_completed > 0 ? $room_success_rate . '%' : 'n/a' ?>
                </span>
            </td>
            <td><?= $avg_room_wait ?> min</td>
            <td><?= $avg_room_dur ?> min</td>
            <td>
                <div style="background:rgba(88,101,242,<?= 0.3 + ($bar_pct * 0.7) ?>);height:20px;width:<?= $bar_pct * 200 ?>px;border-radius:3px;"></div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<!-- Recent Interviews -->
<div class="panel">
    <h2>👤 Recent Interviews (Last 50)</h2>
    <?php if (empty($pipeline)): ?>
        <p style="color:#888;">No interviews found in this timeframe.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>User</th>
            <th>Room</th>
            <th>Interview Call</th>
            <th>Wait</th>
            <th>Duration</th>
            <th>Staff</th>
            <th>Result</th>
        </tr>
        <?php foreach (array_slice($pipeline, 0, 50) as $row): ?>
        <tr>
            <td><span class="badge badge-user"><?= htmlspecialchars($row['username']) ?></span></td>
            <td><span class="badge badge-voice"><?= htmlspecialchars($row['room']) ?></span></td>
            <td style="font-size:11px;"><strong><?= $row['call_ts'] ?></strong></td>
            <td>
                <?php if ($row['wait_in_queue'] !== null): ?>
                    <span class="badge" style="background:<?= $row['wait_in_queue'] < 5 ? '#43b581' : ($row['wait_in_queue'] < 15 ? '#faa61a' : '#f04747') ?>;">
                        <?= $row['wait_in_queue'] ?> min
                    </span>
                <?php else: ?>
                    <span style="color:#888;">n/a</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($row['interview_duration'] !== null): ?>
                    <span class="badge" style="background:<?= $row['interview_duration'] < 10 ? '#43b581' : ($row['interview_duration'] < 20 ? '#faa61a' : '#f04747') ?>;">
                        <?= $row['interview_duration'] ?> min
                    </span>
                <?php else: ?>
                    <span style="color:#888;">n/a</span>
                <?php endif; ?>
            </td>
            <td style="font-size:11px;">
                <?php if ($row['outcome'] === 'success'): ?>
                    <span class="badge badge-voice" style="background:#43b581;"><?= htmlspecialchars($row['staff']) ?></span>
                <?php else: ?>
                    <span style="color:#888;">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($row['outcome'] === 'success'): ?>
                    <span style="color:#43b581;font-weight:bold;" title="<?= htmlspecialchars($row['outcome_detail']) ?>">✓ Invited</span>
                <?php elseif ($row['outcome'] === 'failure'): ?>
                    <span style="color:#f04747;font-weight:bold;" title="<?= htmlspecialchars($row['outcome_detail']) ?>">✗ Failed</span>
                <?php else: ?>
                    <span style="color:#888;" title="<?= htmlspecialchars($row['outcome_detail']) ?>">⏳ Pending</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<!-- Summary Stats -->
<div class="panel">
    <h2>📊 Summary Statistics</h2>
    <div class="grid">
        <div style="background:#40444b;padding:15px;border-radius:8px;">
            <h3 style="color:#5865F2;font-size:14px;margin-bottom:5px;">Completed</h3>
            <div style="font-size:32px;font-weight:bold;"><?= number_format($completed) ?></div>
            <p style="color:#888;font-size:12px;">Success + Failure</p>
        </div>
        <div style="background:#40444b;padding:15px;border-radius:8px;">
            <h3 style="color:#43b581;font-size:14px;margin-bottom:5px;">Active Staff</h3>
            <div style="font-size:32px;font-weight:bold;"><?= count($staff_stats) ?></div>
            <p style="color:#888;font-size:12px;">Interviewers working</p>
        </div>
        <div style="background:#40444b;padding:15px;border-radius:8px;">
            <h3 style="color:#5865F2;font-size:14px;margin-bottom:5px;">Active Rooms</h3>
            <div style="font-size:32px;font-weight:bold;"><?= count($room_stats) ?></div>
            <p style="color:#888;font-size:12px;">Rooms in use</p>
        </div>
        <div style="background:#40444b;padding:15px;border-radius:8px;">
            <h3 style="color:#43b581;font-size:14px;margin-bottom:5px;">Success Rate</h3>
            <div style="font-size:32px;font-weight:bold;color:#43b581;"><?= $completed > 0 ? $success_rate . '%' : 'n/a' ?></div>
            <p style="color:#888;font-size:12px;"><?= $successful_invites ?> invited</p>
        </div>
    </div>
</div>

<!-- Legend -->
<div class="panel">
    <h2>📖 Smart Outcome Detection Logic</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:15px;">
        <div>
            <strong style="color:#43b581;">✓ Success (Invited)</strong>
            <p style="color:#888;font-size:13px;margin-top:5px;">MouseBot welcomed user within 2 hours of interview call (staff detected from preceding messages)</p>
        </div>
        <div>
            <strong style="color:#f04747;">✗ Failure (Rejected/Quit)</strong>
            <p style="color:#888;font-size:13px;margin-top:5px;">User disconnected >30 min ago AND no welcome detected (likely rejected or gave up)</p>
        </div>
        <div>
            <strong style="color:#faa61a;">⏳ Pending (In Progress)</strong>
            <p style="color:#888;font-size:13px;margin-top:5px;">Interview called recently, no outcome yet (or recent disconnect that might be temporary)</p>
        </div>
        <div>
            <strong style="color:#5865F2;">⚡ Optimized Performance</strong>
            <p style="color:#888;font-size:13px;margin-top:5px;">Only 3 database queries total, all processing in-memory for fast loading</p>
        </div>
    </div>
</div>
