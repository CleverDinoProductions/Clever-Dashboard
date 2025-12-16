<?php
$channels = query("
    SELECT 
        c.channel_name,
        c.channel_id,
        c.last_checked,
        COUNT(v.video_id) as video_count,
        MAX(v.published_date) as last_upload,
        AVG(g.gap_days) as avg_gap
    FROM channels c
    LEFT JOIN videos v ON c.channel_id = v.channel_id
    LEFT JOIN upload_gaps g ON v.video_id = g.video_id
    GROUP BY c.channel_id
    ORDER BY last_upload DESC
");
?>

<div class="content-area">
    <h2>📺 Channel Overview</h2>
    <p class="tab-description">Statistics and activity for all monitored channels</p>
    
    <div class="channel-grid">
        <?php while ($channel = $channels->fetchArray(SQLITE3_ASSOC)): ?>
            <?php
            $hours_ago = $channel['last_upload'] 
                ? round((time() - strtotime($channel['last_upload'])) / 3600, 1)
                : null;
            $status = !$hours_ago || $hours_ago > 72 ? 'inactive' : 'active';
            ?>
            
            <div class="channel-card status-<?= $status ?>">
                <h3><?= htmlspecialchars($channel['channel_name']) ?></h3>
                
                <div class="channel-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?= $channel['video_count'] ?></span>
                        <span class="stat-label">Videos</span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-value"><?= $hours_ago ? $hours_ago . 'h' : 'N/A' ?></span>
                        <span class="stat-label">Last Upload</span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-value"><?= round($channel['avg_gap'] ?? 0, 1) ?></span>
                        <span class="stat-label">Avg Gap (days)</span>
                    </div>
                </div>
                
                <?php if ($channel['last_upload']): ?>
                    <div class="last-upload">
                        📅 <?= date('M j, Y g:i A', strtotime($channel['last_upload'])) ?>
                    </div>
                <?php endif; ?>
                
                <div class="channel-id">
                    <small>ID: <?= htmlspecialchars($channel['channel_id']) ?></small>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>
