<?php
// SUPPORT TRACKING TAB - Logic & View

// Staff activity in #help
$stmt = $db->query("
    SELECT 
        json_extract(msg, '$.from.nick') as staff_nick,
        json_extract(msg, '$.from.mode') as mode,
        COUNT(*) as responses,
        datetime(MAX(time/1000), 'unixepoch') as last_active
    FROM messages
    WHERE channel = '#help'
    AND type = 'message'
    AND json_extract(msg, '$.from.mode') IN ('@', '&', '~', '%')
    AND time > strftime('%s', 'now', '-7 days') * 1000
    GROUP BY staff_nick
    ORDER BY responses DESC
    LIMIT 20
");
$staff_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent #help activity
$stmt = $db->query("
    SELECT 
        datetime(time/1000, 'unixepoch') as timestamp,
        json_extract(msg, '$.from.nick') as nick,
        json_extract(msg, '$.from.mode') as mode,
        json_extract(msg, '$.text') as text
    FROM messages
    WHERE channel = '#help'
    AND type = 'message'
    AND time > strftime('%s', 'now', '-2 hours') * 1000
    ORDER BY time DESC
    LIMIT 30
");
$help_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Support coverage by hour
$stmt = $db->query("
    SELECT 
        strftime('%H', time/1000, 'unixepoch') as hour,
        COUNT(*) as staff_messages
    FROM messages
    WHERE channel = '#help'
    AND type = 'message'
    AND json_extract(msg, '$.from.mode') IN ('@', '&', '~', '%')
    AND time > strftime('%s', 'now', '-7 days') * 1000
    GROUP BY hour
    ORDER BY hour
");
$support_coverage = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Your support interactions
$stmt = $db->query("
    SELECT 
        datetime(time/1000, 'unixepoch') as timestamp,
        json_extract(msg, '$.from.nick') as from_nick,
        json_extract(msg, '$.text') as text
    FROM messages
    WHERE channel = '#help'
    AND type = 'message'
    AND (json_extract(msg, '$.from.nick') = 'DinoDude' 
         OR json_extract(msg, '$.text') LIKE '%DinoDude%')
    AND time > strftime('%s', 'now', '-30 days') * 1000
    ORDER BY time DESC
    LIMIT 20
");
$your_support = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- SUPPORT TRACKING VIEW -->

<div class="grid">
    <div class="panel">
        <h2>👮 Active Staff (#help - 7 Days)</h2>
        <table>
            <tr><th>Staff Member</th><th>Mode</th><th>Responses</th><th>Last Active</th></tr>
            <?php foreach ($staff_activity as $staff): ?>
            <tr>
                <td><span class="badge badge-staff"><?= htmlspecialchars($staff['staff_nick']) ?></span></td>
                <td><?= htmlspecialchars($staff['mode']) ?></td>
                <td><?= number_format($staff['responses']) ?></td>
                <td class="timestamp"><?= $staff['last_active'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="panel">
        <h2>📊 Support Coverage by Hour (7 Days)</h2>
        <div class="chart-container">
            <canvas id="supportChart"></canvas>
        </div>
    </div>
</div>

<div class="grid">
    <div class="panel">
        <h2>💬 Recent #help Activity (Last 2 Hours)</h2>
        <?php foreach ($help_messages as $msg): ?>
        <div class="feed-item">
            <span class="timestamp" style="min-width: 130px;"><?= $msg['timestamp'] ?></span>
            <span class="badge <?= in_array($msg['mode'], ['@','&','~','%']) ? 'badge-staff' : 'badge-user' ?>">
                <?= $msg['mode'] ?><?= htmlspecialchars($msg['nick']) ?>
            </span>
            <span style="flex: 1; color: #dcddde; font-size: 13px;"><?= htmlspecialchars(substr($msg['text'], 0, 100)) ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="panel">
        <h2>🔍 Your Support Activity (30 Days)</h2>
        <?php if (empty($your_support)): ?>
            <p style="color: #888;">No mentions or messages from you in #help</p>
        <?php else: ?>
            <?php foreach ($your_support as $msg): ?>
            <div class="feed-item">
                <span class="timestamp" style="min-width: 130px;"><?= $msg['timestamp'] ?></span>
                <span class="badge badge-user"><?= htmlspecialchars($msg['from_nick']) ?></span>
                <span style="flex: 1; color: #dcddde; font-size: 13px;"><?= htmlspecialchars(substr($msg['text'], 0, 80)) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Support Coverage Chart
const ctx = document.getElementById('supportChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [<?php 
                $hours = array_fill(0, 24, 0);
                foreach ($support_coverage as $row) {
                    $hours[$row['hour']] = $row['staff_messages'];
                }
                echo implode(',', array_map(function($h) { return "'$h:00'"; }, range(0, 23)));
            ?>],
            datasets: [{
                label: 'Staff Messages',
                data: [<?= implode(',', $hours) ?>],
                backgroundColor: 'rgba(88, 101, 242, 0.8)',
                borderColor: 'rgba(88, 101, 242, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}
</script>
