<?php
// ============================================================================
// INTERVIEW HISTORY TAB - Complete Hierarchical Drill-Down
// ============================================================================

$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;
$week = $_GET['week'] ?? null;
$day = $_GET['day'] ?? null;
$voice_channel = '#anonamouse.net';

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

// Get available years
$stmt = $db->prepare("
    SELECT DISTINCT strftime('%Y', time/1000, 'unixepoch') as year
    FROM messages
    WHERE channel = :ch
    AND type = 'message'
    AND msg LIKE '%MouseBot%'
    AND msg LIKE '%interview%'
    AND json_valid(msg) = 1
    ORDER BY year DESC
");
$stmt->execute(['ch' => $voice_channel]);
$years = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get months for selected year
$months = [];
if ($year) {
    $stmt = $db->prepare("
        SELECT 
            strftime('%m', time/1000, 'unixepoch') as month_num,
            COUNT(*) as interview_count
        FROM messages
        WHERE channel = :ch
        AND type = 'message'
        AND msg LIKE '%MouseBot%'
        AND msg LIKE '%interview%'
        AND json_valid(msg) = 1
        AND strftime('%Y', time/1000, 'unixepoch') = :year
        GROUP BY month_num
        ORDER BY month_num
    ");
    $stmt->execute(['ch' => $voice_channel, 'year' => $year]);
    $months = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper function to get interview data
function get_interview_data($db, $channel, $year, $month = null, $week = null, $day = null) {
    $where_clauses = ["channel = :ch", "type = 'message'", "msg LIKE '%MouseBot%'", "msg LIKE '%interview%'", "json_valid(msg) = 1"];
    $params = ['ch' => $channel];
    
    if ($year) {
        $where_clauses[] = "strftime('%Y', time/1000, 'unixepoch') = :year";
        $params['year'] = $year;
    }
    if ($month) {
        $where_clauses[] = "strftime('%m', time/1000, 'unixepoch') = :month";
        $params['month'] = $month;
    }
    if ($week) {
        $where_clauses[] = "strftime('%W', time/1000, 'unixepoch') = :week";
        $params['week'] = $week;
    }
    if ($day) {
        $where_clauses[] = "date(time/1000, 'unixepoch') = :day";
        $params['day'] = $day;
    }
    
    $sql = "SELECT time, datetime(time/1000, 'unixepoch') AS ts, 
                   strftime('%W', time/1000, 'unixepoch') AS week_num,
                   date(time/1000, 'unixepoch') AS date, msg
            FROM messages
            WHERE " . implode(' AND ', $where_clauses) . "
            ORDER BY time DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper to get welcomes
function get_welcomes($db, $channel, $year, $month = null, $week = null, $day = null) {
    $where_clauses = ["channel = :ch", "type = 'action'", "msg LIKE '%MouseBot%'", "msg LIKE '%welcomes %'", "json_valid(msg) = 1"];
    $params = ['ch' => $channel];
    
    if ($year) {
        $where_clauses[] = "strftime('%Y', time/1000, 'unixepoch') = :year";
        $params['year'] = $year;
    }
    if ($month) {
        $where_clauses[] = "strftime('%m', time/1000, 'unixepoch') = :month";
        $params['month'] = $month;
    }
    if ($week) {
        $where_clauses[] = "strftime('%W', time/1000, 'unixepoch') = :week";
        $params['week'] = $week;
    }
    if ($day) {
        $where_clauses[] = "date(time/1000, 'unixepoch') = :day";
        $params['day'] = $day;
    }
    
    $sql = "SELECT time, msg FROM messages WHERE " . implode(' AND ', $where_clauses) . " ORDER BY time";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Process data based on current level
$data = [];
if ($day) {
    // DAY DETAIL VIEW - Show individual interviews
    $raw_interviews = get_interview_data($db, $voice_channel, $year, $month, null, $day);
    $welcomes_raw = get_welcomes($db, $voice_channel, $year, $month, null, $day);
    
    // Build welcome lookup
    $welcomes_by_user = [];
    foreach ($welcomes_raw as $row) {
        $msg_data = @json_decode($row['msg'], true);
        if (!$msg_data) continue;
        $text = strip_mirc_codes($msg_data['text'] ?? '');
        if (preg_match('/^welcomes\s+(\S+),/i', $text, $m)) {
            $welcomes_by_user[trim($m[1])] = $row['time'];
        }
    }
    
    // Get staff attribution
    $welcome_times = array_column($welcomes_raw, 'time');
    $staff_messages_by_time = [];
    if (!empty($welcome_times)) {
        $stmt = $db->prepare("SELECT time, msg FROM messages 
            WHERE channel = :ch AND type = 'message' AND json_valid(msg) = 1
            AND time >= :start AND time <= :end ORDER BY time");
        $stmt->execute(['ch' => $voice_channel, 'start' => min($welcome_times) - 30000, 'end' => max($welcome_times)]);
        
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $msg_data = @json_decode($row['msg'], true);
            if (!$msg_data) continue;
            if (in_array($msg_data['from']['mode'] ?? '', ['@', '&', '~', '%'])) {
                $staff_messages_by_time[$row['time']] = $msg_data['from']['nick'] ?? 'Unknown';
            }
        }
        ksort($staff_messages_by_time);
    }
    $staff_times = array_keys($staff_messages_by_time);
    
    // Process interviews
    foreach ($raw_interviews as $row) {
        $msg_data = @json_decode($row['msg'], true);
        if (!$msg_data || ($msg_data['from']['nick'] ?? '') !== 'MouseBot') continue;
        
        $text = strip_mirc_codes($msg_data['text'] ?? '');
        if (preg_match('/Now up for an .*interview.* in .*room\s+([A-Z0-9]+),\s*(\S+)/i', $text, $m)) {
            $room = strtoupper($m[1]);
            $username = $m[2];
            
            $successful = false;
            $staff = 'Unknown';
            if (isset($welcomes_by_user[$username])) {
                $time_diff = $welcomes_by_user[$username] - $row['time'];
                if ($time_diff >= 0 && $time_diff <= 2 * 3600 * 1000) {
                    $successful = true;
                    $welcome_time = $welcomes_by_user[$username];
                    foreach (array_reverse($staff_times) as $msg_time) {
                        if ($msg_time < $welcome_time && $msg_time >= $welcome_time - 30000) {
                            $staff = $staff_messages_by_time[$msg_time];
                            break;
                        }
                    }
                }
            }
            
            $data['interviews'][] = [
                'username' => $username,
                'room' => $room,
                'time' => $row['ts'],
                'successful' => $successful,
                'staff' => $staff
            ];
        }
    }
    
} elseif ($week) {
    // WEEK SUMMARY - Show daily breakdown
    $raw_interviews = get_interview_data($db, $voice_channel, $year, $month, $week);
    $welcomes_raw = get_welcomes($db, $voice_channel, $year, $month, $week);
    
    $welcomes_by_user = [];
    foreach ($welcomes_raw as $row) {
        $msg_data = @json_decode($row['msg'], true);
        if (!$msg_data) continue;
        $text = strip_mirc_codes($msg_data['text'] ?? '');
        if (preg_match('/^welcomes\s+(\S+),/i', $text, $m)) {
            $welcomes_by_user[trim($m[1])] = $row['time'];
        }
    }
    
    foreach ($raw_interviews as $row) {
        $msg_data = @json_decode($row['msg'], true);
        if (!$msg_data || ($msg_data['from']['nick'] ?? '') !== 'MouseBot') continue;
        
        $text = strip_mirc_codes($msg_data['text'] ?? '');
        if (preg_match('/Now up for an .*interview.* in .*room\s+([A-Z0-9]+),\s*(\S+)/i', $text, $m)) {
            $username = $m[2];
            $date = $row['date'];
            
            $successful = isset($welcomes_by_user[$username]) && 
                         ($welcomes_by_user[$username] - $row['time']) >= 0 && 
                         ($welcomes_by_user[$username] - $row['time']) <= 2 * 3600 * 1000;
            
            if (!isset($data['daily'][$date])) {
                $data['daily'][$date] = ['total' => 0, 'success' => 0];
            }
            $data['daily'][$date]['total']++;
            if ($successful) $data['daily'][$date]['success']++;
        }
    }
    krsort($data['daily']);
    
} elseif ($month) {
    // MONTH SUMMARY - Show weekly breakdown
    $raw_interviews = get_interview_data($db, $voice_channel, $year, $month);
    $welcomes_raw = get_welcomes($db, $voice_channel, $year, $month);
    
    $welcomes_by_user = [];
    foreach ($welcomes_raw as $row) {
        $msg_data = @json_decode($row['msg'], true);
        if (!$msg_data) continue;
        $text = strip_mirc_codes($msg_data['text'] ?? '');
        if (preg_match('/^welcomes\s+(\S+),/i', $text, $m)) {
            $welcomes_by_user[trim($m[1])] = $row['time'];
        }
    }
    
    foreach ($raw_interviews as $row) {
        $msg_data = @json_decode($row['msg'], true);
        if (!$msg_data || ($msg_data['from']['nick'] ?? '') !== 'MouseBot') continue;
        
        $text = strip_mirc_codes($msg_data['text'] ?? '');
        if (preg_match('/Now up for an .*interview.* in .*room\s+([A-Z0-9]+),\s*(\S+)/i', $text, $m)) {
            $username = $m[2];
            $week_num = $row['week_num'];
            
            $successful = isset($welcomes_by_user[$username]) && 
                         ($welcomes_by_user[$username] - $row['time']) >= 0 && 
                         ($welcomes_by_user[$username] - $row['time']) <= 2 * 3600 * 1000;
            
            if (!isset($data['weekly'][$week_num])) {
                $data['weekly'][$week_num] = ['total' => 0, 'success' => 0];
            }
            $data['weekly'][$week_num]['total']++;
            if ($successful) $data['weekly'][$week_num]['success']++;
        }
    }
    ksort($data['weekly']);
}

// Calculate totals
$total_interviews = 0;
$total_success = 0;
if (isset($data['interviews'])) {
    $total_interviews = count($data['interviews']);
    $total_success = count(array_filter($data['interviews'], fn($i) => $i['successful']));
} elseif (isset($data['daily'])) {
    $total_interviews = array_sum(array_column($data['daily'], 'total'));
    $total_success = array_sum(array_column($data['daily'], 'success'));
} elseif (isset($data['weekly'])) {
    $total_interviews = array_sum(array_column($data['weekly'], 'total'));
    $total_success = array_sum(array_column($data['weekly'], 'success'));
}
$success_rate = $total_interviews > 0 ? round(($total_success / $total_interviews) * 100, 1) : 0;
?>

<!-- INTERVIEW HISTORY VIEW -->

<!-- Breadcrumb navigation -->
<div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
    <span style="color: #888;">Navigate:</span>
    <a href="?tab=interview-history" style="color: #5865F2; text-decoration: none; margin: 0 5px;">📅 Years</a>
    <?php if ($year): ?>
        → <a href="?tab=interview-history&year=<?= $year ?>" style="color: #5865F2; text-decoration: none;"><?= $year ?></a>
    <?php endif; ?>
    <?php if ($month): ?>
        → <a href="?tab=interview-history&year=<?= $year ?>&month=<?= $month ?>" style="color: #5865F2; text-decoration: none;"><?= $month_names[$month] ?></a>
    <?php endif; ?>
    <?php if ($week): ?>
        → <a href="?tab=interview-history&year=<?= $year ?>&month=<?= $month ?>&week=<?= $week ?>" style="color: #5865F2; text-decoration: none;">Week <?= $week ?></a>
    <?php endif; ?>
    <?php if ($day): ?>
        → <span style="color: #5865F2;"><?= date('M j', strtotime($day)) ?></span>
    <?php endif; ?>
</div>

<?php if (empty($years)): ?>
    <div class="panel" style="border: 2px solid #f04747;">
        <h2 style="color: #f04747;">⚠️ No Interview History Found</h2>
        <p>No interview data found in <?= htmlspecialchars($voice_channel) ?>.</p>
    </div>

<?php elseif ($day): ?>
    <!-- DAY DETAIL VIEW -->
    <div class="panel">
        <h2>📅 <?= date('l, F j, Y', strtotime($day)) ?></h2>
        <p style="color: #888;">Detailed interview log for this day</p>
        
        <div class="grid" style="margin-top: 20px;">
            <div class="panel" style="background: #40444b;">
                <h3 style="color: #5865F2;">Total Interviews</h3>
                <div class="stat-big" style="font-size: 42px;"><?= $total_interviews ?></div>
            </div>
            <div class="panel" style="background: #40444b;">
                <h3 style="color: #43b581;">Successful</h3>
                <div class="stat-big" style="font-size: 42px; color: #43b581;"><?= $total_success ?></div>
            </div>
            <div class="panel" style="background: #40444b;">
                <h3 style="color: #5865F2;">Success Rate</h3>
                <div class="stat-big" style="font-size: 42px;"><?= $success_rate ?>%</div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($data['interviews'])): ?>
    <div class="panel">
        <h2>👥 All Interviews (<?= count($data['interviews']) ?>)</h2>
        <table>
            <tr>
                <th>Username</th>
                <th>Room</th>
                <th>Time</th>
                <th>Result</th>
                <th>Staff</th>
            </tr>
            <?php foreach ($data['interviews'] as $interview): ?>
            <tr>
                <td><span class="badge badge-user"><?= htmlspecialchars($interview['username']) ?></span></td>
                <td><span class="badge badge-voice">Room <?= htmlspecialchars($interview['room']) ?></span></td>
                <td style="font-size: 11px;"><?= $interview['time'] ?></td>
                <td>
                    <?php if ($interview['successful']): ?>
                        <span style="color: #43b581; font-weight: bold;">✓ Success</span>
                    <?php else: ?>
                        <span style="color: #f04747;">✗ Failed</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($interview['successful']): ?>
                        <span class="badge badge-voice"><?= htmlspecialchars($interview['staff']) ?></span>
                    <?php else: ?>
                        <span style="color: #888;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

<?php elseif ($week): ?>
    <!-- WEEK SUMMARY VIEW -->
    <div class="panel">
        <h2>📊 Week <?= $week ?> Summary - <?= $month_names[$month] ?> <?= $year ?></h2>
        <p style="color: #888;">Daily breakdown for this week</p>
        
        <div class="grid" style="margin-top: 20px;">
            <div class="panel" style="background: #40444b;">
                <h3 style="color: #5865F2;">Week Total</h3>
                <div class="stat-big" style="font-size: 42px;"><?= $total_interviews ?></div>
            </div>
            <div class="panel" style="background: #40444b;">
                <h3 style="color: #43b581;">Successful</h3>
                <div class="stat-big" style="font-size: 42px; color: #43b581;"><?= $total_success ?></div>
            </div>
            <div class="panel" style="background: #40444b;">
                <h3 style="color: #5865F2;">Success Rate</h3>
                <div class="stat-big" style="font-size: 42px;"><?= $success_rate ?>%</div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($data['daily'])): ?>
    <div class="panel">
        <h2>📅 Daily Breakdown</h2>
        <table>
            <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Total</th>
                <th>Successful</th>
                <th>Success Rate</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($data['daily'] as $date => $stats): 
                $day_rate = $stats['total'] > 0 ? round(($stats['success'] / $stats['total']) * 100, 1) : 0;
            ?>
            <tr>
                <td><strong><?= date('M j', strtotime($date)) ?></strong></td>
                <td><?= date('l', strtotime($date)) ?></td>
                <td><?= $stats['total'] ?></td>
                <td><span style="color: #43b581;"><?= $stats['success'] ?></span></td>
                <td>
                    <span class="badge" style="background: <?= $day_rate >= 80 ? '#43b581' : ($day_rate >= 60 ? '#faa61a' : '#f04747') ?>;">
                        <?= $day_rate ?>%
                    </span>
                </td>
                <td>
                    <a href="?tab=interview-history&year=<?= $year ?>&month=<?= $month ?>&week=<?= $week ?>&day=<?= $date ?>" 
                       class="badge badge-voice">View Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

<?php elseif ($month): ?>
    <!-- MONTH SUMMARY VIEW -->
    <div class="panel">
        <h2>📊 <?= $month_names[$month] ?> <?= $year ?> Summary</h2>
        <p style="color: #888;">Weekly breakdown for this month</p>
        
        <div class="grid" style="margin-top: 20px;">
            <div class="panel" style="background: #40444b;">
                <h3 style="color: #5865F2;">Month Total</h3>
                <div class="stat-big" style="font-size: 42px;"><?= $total_interviews ?></div>
            </div>
            <div class="panel" style="background: #40444b;">
                <h3 style="color: #43b581;">Successful</h3>
                <div class="stat-big" style="font-size: 42px; color: #43b581;"><?= $total_success ?></div>
            </div>
            <div class="panel" style="background: #40444b;">
                <h3 style="color: #5865F2;">Success Rate</h3>
                <div class="stat-big" style="font-size: 42px;"><?= $success_rate ?>%</div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($data['weekly'])): ?>
    <div class="panel">
        <h2>📅 Weekly Breakdown</h2>
        <table>
            <tr>
                <th>Week</th>
                <th>Total</th>
                <th>Successful</th>
                <th>Success Rate</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($data['weekly'] as $week_num => $stats): 
                $week_rate = $stats['total'] > 0 ? round(($stats['success'] / $stats['total']) * 100, 1) : 0;
            ?>
            <tr>
                <td><strong>Week <?= $week_num ?></strong></td>
                <td><?= $stats['total'] ?></td>
                <td><span style="color: #43b581;"><?= $stats['success'] ?></span></td>
                <td>
                    <span class="badge" style="background: <?= $week_rate >= 80 ? '#43b581' : ($week_rate >= 60 ? '#faa61a' : '#f04747') ?>;">
                        <?= $week_rate ?>%
                    </span>
                </td>
                <td>
                    <a href="?tab=interview-history&year=<?= $year ?>&month=<?= $month ?>&week=<?= $week_num ?>" 
                       class="badge badge-voice">View Week</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

<?php elseif ($year): ?>
    <!-- YEAR VIEW - MONTHS -->
    <div class="panel">
        <h2>📂 <?= $year ?> - Select Month</h2>
        <p style="color: #888; margin-bottom: 15px;">Months with interview activity</p>
        <?php if (empty($months)): ?>
            <p style="color: #888;">No interview data for this year</p>
        <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach ($months as $m): ?>
            <a href="?tab=interview-history&year=<?= $year ?>&month=<?= $m['month_num'] ?>" style="text-decoration: none;">
                <div style="background: #40444b; padding: 20px; border-radius: 8px; text-align: center; transition: all 0.2s;"
                     onmouseover="this.style.background='#4752c4'" 
                     onmouseout="this.style.background='#40444b'">
                    <div style="font-size: 24px; font-weight: bold; color: #5865F2;"><?= $month_names[$m['month_num']] ?></div>
                    <div style="color: #888; font-size: 13px; margin-top: 5px;"><?= number_format($m['interview_count']) ?> interviews</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- ROOT VIEW - YEARS -->
    <div class="panel">
        <h2>📅 Interview History - Select Year</h2>
        <p style="color: #888; margin-bottom: 15px;">Years with recorded interview activity</p>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach ($years as $y): ?>
            <a href="?tab=interview-history&year=<?= $y ?>" style="text-decoration: none;">
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
