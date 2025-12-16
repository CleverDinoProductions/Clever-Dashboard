<?php
$videos = query("
    SELECT 
        v.video_id,
        v.title,
        v.channel_name,
        v.published_date,
        v.detected_location,
        v.url,
        g.gap_days
    FROM videos v
    LEFT JOIN upload_gaps g ON v.video_id = g.video_id
    ORDER BY v.published_date DESC
    LIMIT 50
");
?>

<div class="content-area">
    <h2>📅 Upload Timeline</h2>
    <p class="tab-description">Recent video uploads across all monitored channels</p>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= getValue("SELECT COUNT(*) FROM videos") ?></div>
            <div class="stat-label">Total Videos</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= getValue("SELECT COUNT(*) FROM channels") ?></div>
            <div class="stat-label">Channels</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= round(getValue("SELECT AVG(gap_days) FROM upload_gaps") ?? 0, 1) ?></div>
            <div class="stat-label">Avg Gap (days)</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= getValue("SELECT COUNT(*) FROM upload_gaps WHERE gap_days > 14") ?></div>
            <div class="stat-label">Critical Gaps</div>
        </div>
    </div>
    
    <div class="video-timeline">
        <?php while ($video = $videos->fetchArray(SQLITE3_ASSOC)): ?>
            <?php
            $gap_class = '';
            $gap_alert = '';
            if ($video['gap_days']) {
                if ($video['gap_days'] > 14) {
                    $gap_class = 'gap-critical';
                    $gap_alert = "⚠️ {$video['gap_days']} day gap - ALGORITHM DANGER!";
                } elseif ($video['gap_days'] > 7) {
                    $gap_class = 'gap-warning';
                    $gap_alert = "⚠️ {$video['gap_days']} day gap";
                }
            }
            
            $timestamp = strtotime($video['published_date']);
            ?>
            
            <article class="video-card <?= $gap_class ?>">
                <?php if ($gap_alert): ?>
                    <div class="gap-alert"><?= $gap_alert ?></div>
                <?php endif; ?>
                
                <div class="video-header">
                    <span class="channel-badge"><?= htmlspecialchars($video['channel_name']) ?></span>
                    <time class="timestamp">
                        <?= date('D, M j, Y', $timestamp) ?> at <?= date('g:i A', $timestamp) ?>
                    </time>
                </div>
                
                <h3 class="video-title">
                    <a href="<?= htmlspecialchars($video['url']) ?>" target="_blank" rel="noopener">
                        <?= htmlspecialchars($video['title']) ?>
                    </a>
                </h3>
                
                <div class="video-footer">
                    <?php if ($video['detected_location']): ?>
                        <span class="location-badge">
                            📍 <?= htmlspecialchars($video['detected_location']) ?>
                        </span>
                    <?php endif; ?>
                    <span class="video-id">ID: <?= htmlspecialchars($video['video_id']) ?></span>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
</div>
