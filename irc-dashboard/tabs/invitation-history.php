<?php
// ============================================================================
// INVITATION HISTORY TAB - Complete Hierarchical Drill-Down
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
    AND type = 'action'
    AND msg LIKE '%MouseBot%'
    AND msg LIKE '%welcomes %'
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
            COUNT(*) as invite_count
        FROM messages
        WHERE channel = :ch
        AND type = 'action'
        AND msg LIKE '%MouseBot%'
        AND msg LIKE '%welcomes %'
        AND json_valid(msg) = 1
        AND strftime('%Y', time/1000, 'unixepoch') = :year
        GROUP BY month_num
        ORDER BY month_num
    ");
    $stmt->execute(['ch' => $voice_channel, 'year' => $year]);
    $months = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper function to get invitation data
function get_invitation_data($db, $channel, $year, $month = null, $week = null, $day = null) {
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

// Process data based on current level
$data = [];
if ($day) {
    // DAY DETAIL VIEW
    $raw_invites = get_invitation_data($db, $voice_channel, $year, $month, null, $day);
    
    // Get staff attribution
    $invite_times = array_column($raw_invites, 'time');
    $staff_messages_by_time = [];
    if (!empty($invite_times)) {
        $stmt = $db->prepare("SELECT time, msg FROM messages 
            WHERE channel = :ch AND type = 'message' AND json_valid(msg) = 1
            AND time >= :start AND time <= :end ORDER BY time");
        $stmt->execute(['ch' => $voice_channel, 'start' => min($invite_times) - 30000, 'end' => max($invite_times)]);
        
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
    
    foreach ($raw_invites as $row) {
        $msg_data = @json_decode($row['msg'], true);
        if (!$msg_data || ($msg_data['from']['nick'] ?? '') !== 'MouseBot') continue;
        
        $text = strip_mirc_codes($msg_data['text'] ?? '');
        if (preg_match('/^welcomes\s+(\S+),/i', $text, $m)) {
            $username = trim($m[1]);
            $invite_time = $row['time'];
            
            $staff = 'Unknown';
            foreach (array_reverse($staff_times) as $msg_time) {
                if ($msg_time < $invite_time && $msg_time >= $invite_time - 30000) {
                    $staff = $staff_messages_by_time[$msg_time];
                    break;
                }
            }
            
            $data['invites'][] = [
                'username' => $username,
                'time' => $row['ts'],
                'staff' => $staff
            ];
        }
    }
    
} elseif ($week) {
    // WEEK SUMMARY
    $raw_invites = get_invitation_data($db, $voice_channel, $year, $month, $week);
    
    foreach ($raw_invites as $row) {
        $msg_data = @json_decode($row['msg'], true);
        if (!$msg_data || ($msg_data['from']['nick'] ?? '') !== 'MouseBot') continue;
        
        $text = strip_mirc_codes($msg_data['text'] ?? '');
        if (preg_match('/^welcomes\s+(\S+),/i', $text, $m)) {
            $date = $row['date'];
            if (!isset($data['daily'][$date])) {
                $data['daily'][$date] = 0;
            }
            $data['daily'][$date]++;
        }
    }
    krsort($data['daily']);
    
} elseif ($month) {
    // MONTH SUMMARY
    $raw_invites = get_invitation_data($db, $voice_channel, $year, $month);
    
    foreach ($raw_invites as $row) {
        $msg_data = @json_decode($row['msg'], true);
        if (!$msg_data || ($msg_data['from']['nick'] ?? '') !== 'MouseBot') continue;
        
        $text = strip_mirc_codes($msg_data['text'] ?? '');
        if (preg_match('/^welcomes\s+(\S+),/i', $text, $m)) {
            $week_num = $row['week_num'];
            if (!isset($data['weekly'][$week_num])) {
                $data['weekly'][$week_num] = 0;
            }
            $data['weekly'][$week_num]++;
        }
    }
    ksort($data['weekly']);
}

// Calculate totals
$total_invites = 0;
if (isset($data['invites'])) {
    $total_invites = count($data['invites']);
} elseif (isset($data['daily'])) {
    $total_invites = array_sum($data['daily']);
} elseif (isset($data['weekly'])) {
    $total_invites = array_sum($data['weekly']);
}
?>

<!-- INVITATION HISTORY VIEW -->

<!-- Breadcrumb navigation -->
<div style="background: #40444b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
    <span style="color: #888;">Navigate:</span>
    <a href="?tab=invitation-history" style="color: #43b581; text-decoration: none; margin: 0 5px;">🧀 Years</a>
    <?php if ($year): ?>
        → <a href="?tab=invitation-history&year=<?= $year ?>" style="color: #43b581; text-decoration: none;"><?= $year ?></a>
    <?php endif; ?>
    <?php if ($month): ?>
        → <a href="?tab=invitation-history&year=<?= $year ?>&month=<?= $month ?>" style="color: #43b581; text-decoration: none;"><?= $month_names[$month] ?></a>
    <?php endif; ?>
    <?php if ($week): ?>
        → <a href="?tab=invitation-history&year=<?= $year ?>&month=<?= $month ?>&week=<?= $week ?>" style="color: #43b581; text-decoration: none;">Week <?= $week ?></a>
    <?php endif; ?>
    <?php if ($day): ?>
        → <span style="color: #43b581;"><?= date('M j', strtotime($day)) ?></span>
    <?php endif; ?>
</div>

<?php if (empty($years)): ?>
    <div class="panel" style="border: 2px solid #f04747;">
        <h2 style="color: #f04747;">⚠️ No Invitation History Found</h2>
        <p>No successful invitations found in <?= htmlspecialchars($voice_channel) ?>.</p>
    </div>

<?php elseif ($day): ?>
    <!-- DAY DETAIL VIEW -->
    <div class="panel">
        <h2>🧀 <?= date('l, F j, Y', strtotime($day)) ?></h2>
        <p style="color: #888;">All invitations for this day</p>
        
        <div class="panel" style="background: #40444b; margin-top: 20px;">
            <h3 style="color: #43b581;">Total Invitations</h3>
            <div class="stat-big" style="font-size: 64px; color: #43b581;"><?= $total_invites ?></div>
        </div>
    </div>
    
<?php elseif ($week): ?>
    <!-- WEEK SUMMARY VIEW -->
    <div class="panel">
        <h2>📊 Week <?= $week ?> Summary - <?= $month_names[$month] ?> <?= $year ?></h2>
        <p style="color: #888;">Daily breakdown for this week</p>
        
        <div class="panel" style="background: #40444b; margin-top: 20px;">
            <h3 style="color: #43b581;">Week Total</h3>
            <div class="stat-big" style="font-size: 64px; color: #43b581;"><?= $total_invites ?></div>
        </div>
    </div>
    
    <?php if (!empty($data['daily'])): ?>
    <div class="panel">
        <h2>📅 Daily Breakdown</h2>
        <table>
            <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Invitations</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($data['daily'] as $date => $count): ?>
            <tr>
                <td><strong><?= date('M j', strtotime($date)) ?></strong></td>
                <td><?= date('l', strtotime($date)) ?></td>
                <td><span class="badge" style="background: #43b581;"><?= $count ?></span></td>
                <td>
                    <a href="?tab=invitation-history&year=<?= $year ?>&month=<?= $month ?>&week=<?= $week ?>&day=<?= $date ?>" 
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
        
        <div class="panel" style="background: #40444b; margin-top: 20px;">
            <h3 style="color: #43b581;">Month Total</h3>
            <div class="stat-big" style="font-size: 64px; color: #43b581;"><?= $total_invites ?></div>
        </div>
    </div>
    
    <?php if (!empty($data['weekly'])): ?>
    <div class="panel">
        <h2>📅 Weekly Breakdown</h2>
        <table>
            <tr>
                <th>Week</th>
                <th>Invitations</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($data['weekly'] as $week_num => $count): ?>
            <tr>
                <td><strong>Week <?= $week_num ?></strong></td>
                <td><span class="badge" style="background: #43b581;"><?= $count ?></span></td>
                <td>
                    <a href="?tab=invitation-history&year=<?= $year ?>&month=<?= $month ?>&week=<?= $week_num ?>" 
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
        <p style="color: #888; margin-bottom: 15px;">Months with invitation activity</p>
        <?php if (empty($months)): ?>
            <p style="color: #888;">No invitation data for this year</p>
        <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach ($months as $m): ?>
            <a href="?tab=invitation-history&year=<?= $year ?>&month=<?= $m['month_num'] ?>" style="text-decoration: none;">
                <div style="background: #40444b; padding: 20px; border-radius: 8px; text-align: center; transition: all 0.2s;"
                     onmouseover="this.style.background='#3a9f7c'" 
                     onmouseout="this.style.background='#40444b'">
                    <div style="font-size: 24px; font-weight: bold; color: #43b581;"><?= $month_names[$m['month_num']] ?></div>
                    <div style="color: #888; font-size: 13px; margin-top: 5px;"><?= number_format($m['invite_count']) ?> 🧀 invites</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- ROOT VIEW - YEARS -->
    <div class="panel">
        <h2>🧀 Invitation History - Select Year</h2>
        <p style="color: #888; margin-bottom: 15px;">Years with recorded invitation activity</p>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach ($years as $y): ?>
            <a href="?tab=invitation-history&year=<?= $y ?>" style="text-decoration: none;">
                <div style="background: #40444b; padding: 30px; border-radius: 8px; text-align: center; transition: all 0.2s;" 
                     onmouseover="this.style.background='#3a9f7c'" 
                     onmouseout="this.style.background='#40444b'">
                    <div style="font-size: 36px; font-weight: bold; color: #43b581;"><?= $y ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
