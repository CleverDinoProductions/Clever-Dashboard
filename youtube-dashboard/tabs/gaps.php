<?php
$gaps = query("
    SELECT 
        g.gap_days,
        g.current_video_date,
        g.previous_video_date,
        v.title,
        v.channel_name,
        v.url
    FROM upload_gaps g
    JOIN videos v ON g.video_id = v.video_id
    ORDER BY g.gap_days DESC
    LIMIT 50
");
?>

<div class="content-area">
    <h2>⏱️ Upload Gap Analysis</h2>
    <p class="tab-description">Tracking upload gaps that could impact algorithm performance</p>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= getValue("SELECT COUNT(*) FROM upload_gaps WHERE gap_days > 14") ?></div>
            <div class="stat-label">Critical Gaps (&gt;14d)</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= getValue("SELECT COUNT(*) FROM upload_gaps WHERE gap_days > 7 AND gap_days <= 14") ?></div>
            <div class="stat-label">Warning Gaps (7-14d)</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= getValue("SELECT MAX(gap_days) FROM upload_gaps") ?></div>
            <div class="stat-label">Longest Gap (days)</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= round(getValue("SELECT AVG(gap_days) FROM upload_gaps") ?? 0, 1) ?></div>
            <div class="stat-label">Average Gap</div>
        </div>
    </div>
    
    <div class="gaps-list">
        <?php while ($gap = $gaps->fetchArray(SQLITE3_ASSOC)): ?>
            <?php
            $severity = $gap['gap_days'] > 14 ? 'severe' : 
                       ($gap['gap_days'] > 7 ? 'moderate' : 'minor');
            ?>
            
            <div class="gap-item <?= $severity ?>">
                <div class="gap-header">
                    <span class="gap-days"><?= $gap['gap_days'] ?> days</span>
                    <span class="channel-name"><?= htmlspecialchars($gap['channel_name']) ?></span>
                </div>
                
                <h4 class="gap-video-title">
                    <a href="<?= htmlspecialchars($gap['url']) ?>" target="_blank">
                        <?= htmlspecialchars($gap['title']) ?>
                    </a>
                </h4>
                
                <div class="gap-dates">
                    <span>Previous: <?= date('M j, Y', strtotime($gap['previous_video_date'])) ?></span>
                    <span>→</span>
                    <span>This: <?= date('M j, Y', strtotime($gap['current_video_date'])) ?></span>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>
