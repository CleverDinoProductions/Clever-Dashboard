<?php
$frequency = query("
    SELECT 
        channel_name,
        COUNT(*) as total_videos,
        AVG(g.gap_days) as avg_gap,
        MIN(g.gap_days) as min_gap,
        MAX(g.gap_days) as max_gap
    FROM videos v
    LEFT JOIN upload_gaps g ON v.video_id = g.video_id
    GROUP BY channel_name
    ORDER BY total_videos DESC
");

$day_frequency = query("
    SELECT 
        CASE CAST(strftime('%w', published_date) AS INTEGER)
            WHEN 0 THEN 'Sunday'
            WHEN 1 THEN 'Monday'
            WHEN 2 THEN 'Tuesday'
            WHEN 3 THEN 'Wednesday'
            WHEN 4 THEN 'Thursday'
            WHEN 5 THEN 'Friday'
            WHEN 6 THEN 'Saturday'
        END as day_name,
        COUNT(*) as upload_count
    FROM videos
    GROUP BY strftime('%w', published_date)
    ORDER BY CAST(strftime('%w', published_date) AS INTEGER)
");

$hour_frequency = query("
    SELECT 
        CAST(strftime('%H', published_date) AS INTEGER) as hour,
        COUNT(*) as upload_count
    FROM videos
    GROUP BY hour
    ORDER BY hour
");
?>

<div class="content-area">
    <h2>📈 Upload Frequency Analysis</h2>
    <p class="tab-description">When and how often channels upload content</p>
    
    <h3>Channel Upload Patterns</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Channel</th>
                <th>Total Videos</th>
                <th>Avg Gap (days)</th>
                <th>Min Gap</th>
                <th>Max Gap</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($freq = $frequency->fetchArray(SQLITE3_ASSOC)): ?>
                <tr>
                    <td><?= htmlspecialchars($freq['channel_name']) ?></td>
                    <td><?= $freq['total_videos'] ?></td>
                    <td><?= round($freq['avg_gap'] ?? 0, 1) ?></td>
                    <td><?= round($freq['min_gap'] ?? 0) ?></td>
                    <td><?= round($freq['max_gap'] ?? 0) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <h3>📅 Uploads by Day of Week</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Day</th>
                <th>Upload Count</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total = getValue("SELECT COUNT(*) FROM videos");
            while ($day = $day_frequency->fetchArray(SQLITE3_ASSOC)): 
            ?>
                <tr>
                    <td><?= $day['day_name'] ?></td>
                    <td><?= $day['upload_count'] ?></td>
                    <td><?= round(($day['upload_count'] / $total) * 100, 1) ?>%</td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <h3>🕐 Uploads by Hour (UTC)</h3>
    <div class="hour-heatmap">
        <?php 
        $max_count = getValue("SELECT MAX(cnt) FROM (SELECT COUNT(*) as cnt FROM videos GROUP BY strftime('%H', published_date))");
        while ($hour = $hour_frequency->fetchArray(SQLITE3_ASSOC)): 
        ?>
            <div class="hour-bar" style="width: <?= $max_count > 0 ? ($hour['upload_count'] / $max_count * 100) : 0 ?>%">
                <?= sprintf('%02d:00', $hour['hour']) ?> - <?= $hour['upload_count'] ?> uploads
            </div>
        <?php endwhile; ?>
    </div>
</div>
