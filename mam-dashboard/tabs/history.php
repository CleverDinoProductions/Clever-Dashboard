<?php
// HISTORICAL TRENDS TAB - Logic & View

// Check if monthly_stats table exists
$table_check = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='monthly_stats'");
$has_history = $table_check->fetch() !== false;

if ($has_history) {
    // Get all monthly data
    $stmt = $db->query("
        SELECT * FROM monthly_stats 
        ORDER BY month DESC, channel
    ");
    $monthly_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get month-over-month for #an-q
    $stmt = $db->query("
        SELECT 
            month,
            voice_grants,
            avg_wait_time,
            total_messages
        FROM monthly_stats
        WHERE channel = '#an-q'
        ORDER BY month DESC
    ");
    $queue_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $monthly_history = [];
    $queue_history = [];
}
?>

<!-- HISTORICAL TRENDS VIEW -->

<?php if (!$has_history): ?>
    <div class="panel" style="border: 2px solid #f39c12;">
        <h2 style="color: #f39c12;">⚠️ No Historical Data</h2>
        <p>Monthly statistics haven't been generated yet.</p>
        <p style="margin-top: 10px;">Run the backfill script to generate historical trends:</p>
        <code style="display: block; background: #1a1a1e; padding: 10px; border-radius: 5px; margin-top: 10px;">
            C:\xampp\php\php.exe C:\xampp\htdocs\backfill-history.php
        </code>
    </div>

<?php else: ?>
    <div class="panel">
        <h2>📅 Monthly Queue Stats (#an-q)</h2>
        <?php if (empty($queue_history)): ?>
            <p style="color: #888;">No queue history available</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Month</th>
                <th>Voice Grants</th>
                <th>Avg Wait Time</th>
                <th>Total Messages</th>
            </tr>
            <?php foreach ($queue_history as $row): ?>
            <tr>
                <td><strong><?= $row['month'] ?></strong></td>
                <td><?= number_format($row['voice_grants']) ?></td>
                <td><?= $row['avg_wait_time'] ?> min</td>
                <td><?= number_format($row['total_messages']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2>📊 All Channels - Monthly Breakdown</h2>
        <?php if (empty($monthly_history)): ?>
            <p style="color: #888;">No monthly data available</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Month</th>
                <th>Channel</th>
                <th>Messages</th>
                <th>Unique Users</th>
                <?php if (in_array('voice_grants', array_keys($monthly_history[0]))): ?>
                    <th>Voice Grants</th>
                <?php endif; ?>
            </tr>
            <?php foreach ($monthly_history as $row): ?>
            <tr>
                <td><?= $row['month'] ?></td>
                <td><?= htmlspecialchars($row['channel']) ?></td>
                <td><?= number_format($row['total_messages']) ?></td>
                <td><?= $row['unique_users'] ?></td>
                <?php if (isset($row['voice_grants'])): ?>
                    <td><?= $row['voice_grants'] > 0 ? number_format($row['voice_grants']) : '-' ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
<?php endif; ?>
