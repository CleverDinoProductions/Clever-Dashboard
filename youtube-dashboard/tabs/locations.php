<?php
$location_stats = query("
    SELECT 
        detected_location,
        COUNT(*) as video_count,
        MIN(published_date) as first_video,
        MAX(published_date) as last_video
    FROM videos
    WHERE detected_location IS NOT NULL
    GROUP BY detected_location
    ORDER BY video_count DESC
");

$recent_by_location = query("
    SELECT 
        detected_location,
        title,
        channel_name,
        published_date,
        url
    FROM videos
    WHERE detected_location IS NOT NULL
    ORDER BY published_date DESC
    LIMIT 30
");
?>

<div class="content-area">
    <h2>🌍 Location Tracking</h2>
    <p class="tab-description">Geographic analysis of video content based on title keywords</p>
    
    <div class="stats-grid">
        <?php 
        $top_locations = query("SELECT detected_location, COUNT(*) as count FROM videos WHERE detected_location IS NOT NULL GROUP BY detected_location ORDER BY count DESC LIMIT 4");
        while ($loc = $top_locations->fetchArray(SQLITE3_ASSOC)):
        ?>
            <div class="stat-card">
                <div class="stat-value"><?= $loc['count'] ?></div>
                <div class="stat-label">📍 <?= htmlspecialchars($loc['detected_location']) ?></div>
            </div>
        <?php endwhile; ?>
    </div>
    
    <h3>📊 Location Breakdown</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Location</th>
                <th>Videos</th>
                <th>First Detected</th>
                <th>Latest Video</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($stat = $location_stats->fetchArray(SQLITE3_ASSOC)): ?>
                <tr>
                    <td>📍 <?= htmlspecialchars($stat['detected_location']) ?></td>
                    <td><?= $stat['video_count'] ?></td>
                    <td><?= date('M j, Y', strtotime($stat['first_video'])) ?></td>
                    <td><?= date('M j, Y', strtotime($stat['last_video'])) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <h3>📍 Recent Videos by Location</h3>
    <div class="video-timeline">
        <?php while ($video = $recent_by_location->fetchArray(SQLITE3_ASSOC)): ?>
            <article class="video-card">
                <div class="video-header">
                    <span class="location-badge">📍 <?= htmlspecialchars($video['detected_location']) ?></span>
                    <span class="channel-badge"><?= htmlspecialchars($video['channel_name']) ?></span>
                </div>
                <h3 class="video-title">
                    <a href="<?= htmlspecialchars($video['url']) ?>" target="_blank">
                        <?= htmlspecialchars($video['title']) ?>
                    </a>
                </h3>
                <time><?= date('M j, Y g:i A', strtotime($video['published_date'])) ?></time>
            </article>
        <?php endwhile; ?>
    </div>
</div>
