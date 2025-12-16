<?php
$total_videos = getValue("SELECT COUNT(*) FROM videos");
$total_channels = getValue("SELECT COUNT(*) FROM channels");
$avg_gap = getValue("SELECT AVG(gap_days) FROM upload_gaps");

$frequency = query("
    SELECT 
        channel_name,
        COUNT(*) as video_count,
        AVG(g.gap_days) as avg_gap,
        MAX(g.gap_days) as max_gap
    FROM videos v
    LEFT JOIN upload_gaps g ON v.video_id = g.video_id
    GROUP BY channel_name
    ORDER BY video_count DESC
");

$locations = query("
    SELECT 
        detected_location,
        COUNT(*) as count
    FROM videos
    WHERE detected_location IS NOT NULL
    GROUP BY detected_location
    ORDER BY count DESC
");
?>

<div class="content-area">
    <h2>📊 Dashboard Analytics</h2>
    <p class="tab-description">Overall statistics and performance metrics</p>
    
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-number"><?= $total_videos ?></div>
            <div class="stat-label">Total Videos</div>
        </div>
        
        <div class="stat-box">
            <div class="stat-number"><?= $total_channels ?></div>
            <div class="stat-label">Channels</div>
        </div>
        
        <div class="stat-box">
            <div class="stat-number"><?= round($avg_gap ?? 0, 1) ?></div>
            <div class="stat-label">Avg Gap (days)</div>
        </div>
        
        <div class="stat-box">
            <div class="stat-number"><?= getValue("SELECT COUNT(DISTINCT detected_location) FROM videos WHERE detected_location IS NOT NULL") ?></div>
            <div class="stat-label">Locations Detected</div>
        </div>
    </div>
    
    <div class="analytics-tables">
        <div class="analytics-table">
            <h3>📈 Upload Frequency by Channel</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Videos</th>
                        <th>Avg Gap</th>
                        <th>Max Gap</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $frequency->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['channel_name']) ?></td>
                            <td><?= $row['video_count'] ?></td>
                            <td><?= round($row['avg_gap'] ?? 0, 1) ?></td>
                            <td><?= round($row['max_gap'] ?? 0) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="analytics-table">
            <h3>🌍 Location Breakdown</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>Videos</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $locations->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td>📍 <?= htmlspecialchars($row['detected_location']) ?></td>
                            <td><?= $row['count'] ?></td>
                            <td><?= round(($row['count'] / $total_videos) * 100, 1) ?>%</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
