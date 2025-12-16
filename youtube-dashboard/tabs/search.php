<?php
$search_query = isset($_GET['q']) ? $_GET['q'] : '';
$results = null;

if ($search_query) {
    $results = query("
        SELECT 
            v.title,
            v.channel_name,
            v.published_date,
            v.detected_location,
            v.url,
            g.gap_days
        FROM videos v
        LEFT JOIN upload_gaps g ON v.video_id = g.video_id
        WHERE v.title LIKE :query OR v.channel_name LIKE :query
        ORDER BY v.published_date DESC
        LIMIT 100
    ", [':query' => '%' . $search_query . '%']);
}
?>

<div class="content-area">
    <h2>🔍 Search Videos</h2>
    <p class="tab-description">Search across all tracked videos by title or channel name</p>
    
    <form method="GET" action="" style="margin: 30px 0;">
        <input type="hidden" name="tab" value="search">
        <div style="display: flex; gap: 10px; max-width: 600px;">
            <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" 
                   placeholder="Search video titles or channel names..." 
                   style="flex: 1; padding: 12px; border-radius: 6px; border: 2px solid #667eea; font-size: 16px;">
            <button type="submit" style="padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">
                Search
            </button>
        </div>
    </form>
    
    <?php if ($search_query && $results): ?>
        <h3>Search Results for "<?= htmlspecialchars($search_query) ?>"</h3>
        
        <div class="video-timeline">
            <?php 
            $count = 0;
            while ($video = $results->fetchArray(SQLITE3_ASSOC)): 
                $count++;
            ?>
                <article class="video-card">
                    <div class="video-header">
                        <span class="channel-badge"><?= htmlspecialchars($video['channel_name']) ?></span>
                        <time><?= date('M j, Y g:i A', strtotime($video['published_date'])) ?></time>
                    </div>
                    <h3 class="video-title">
                        <a href="<?= htmlspecialchars($video['url']) ?>" target="_blank">
                            <?= htmlspecialchars($video['title']) ?>
                        </a>
                    </h3>
                    <?php if ($video['detected_location']): ?>
                        <span class="location-badge">📍 <?= htmlspecialchars($video['detected_location']) ?></span>
                    <?php endif; ?>
                </article>
            <?php endwhile; ?>
        </div>
        
        <?php if ($count == 0): ?>
            <p style="text-align: center; padding: 40px; color: #666;">No results found for "<?= htmlspecialchars($search_query) ?>"</p>
        <?php else: ?>
            <p style="text-align: center; padding: 20px; color: #666;">Found <?= $count ?> result(s)</p>
        <?php endif; ?>
        
    <?php elseif ($search_query): ?>
        <p style="text-align: center; padding: 40px; color: #666;">No results found</p>
    <?php endif; ?>
</div>
