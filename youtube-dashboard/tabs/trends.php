<?php
$monthly_trends = query("
    SELECT 
        strftime('%Y-%m', published_date) as month,
        COUNT(*) as video_count,
        AVG(g.gap_days) as avg_gap
    FROM videos v
    LEFT JOIN upload_gaps g ON v.video_id = g.video_id
    GROUP BY month
    ORDER BY month DESC
    LIMIT 12
");
?>

<div class="content-area">
    <h2>📉 Historical Trends</h2>
    <p class="tab-description">Upload patterns and trends over time</p>
    
    <h3>📊 Monthly Upload Trends</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Month</th>
                <th>Videos Uploaded</th>
                <th>Avg Upload Gap (days)</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($trend = $monthly_trends->fetchArray(SQLITE3_ASSOC)): ?>
                <tr>
                    <td><?= date('F Y', strtotime($trend['month'] . '-01')) ?></td>
                    <td><?= $trend['video_count'] ?></td>
                    <td><?= round($trend['avg_gap'] ?? 0, 1) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
