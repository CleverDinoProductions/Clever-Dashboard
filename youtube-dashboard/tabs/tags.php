<?php
require_once __DIR__ . '/../includes/tags.php';
$locations = [];
$locationRows = query('SELECT DISTINCT detected_location FROM videos WHERE detected_location IS NOT NULL');
while ($row = $locationRows->fetchArray(SQLITE3_ASSOC)) $locations[] = $row['detected_location'];
$tagCounts = []; $tagVideos = [];
$rows = query('SELECT * FROM dashboard_videos ORDER BY julianday(published_date) DESC');
$selectedTag = dashboard_input('tag');
while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
    $labels = dashboard_tags($row['title'], $locations);
    foreach ($labels as $label) $tagCounts[$label] = ($tagCounts[$label] ?? 0) + 1;
    if ($labels && ($selectedTag === '' || in_array($selectedTag, $labels, true))) {
        $row['tags'] = $labels; $tagVideos[] = $row;
    }
}
arsort($tagCounts);
?>
<div class="content-area">
<h2>Tags &amp; topics</h2>
<p class="tab-description">Title hashtags and rule-based topics, excluding known location labels. These are derived labels, not YouTube metadata tags or verified subjects.</p>
<div class="tag-cloud"><a href="<?= dashboard_url(['tab' => 'tags']) ?>">All tags</a>
<?php foreach ($tagCounts as $label => $count): ?>
<a class="channel-badge" <?= $selectedTag === $label ? 'aria-current="true"' : '' ?> href="<?= dashboard_url(['tab' => 'tags', 'tag' => $label]) ?>"><?= dashboard_escape($label) ?> (<?= $count ?>)</a>
<?php endforeach; ?></div>
<p><?= count($tagVideos) ?> matching tagged videos. Showing up to 100 newest.</p>
<?php if (!$tagVideos): ?><p class="empty-state">No matching tags. Tags appear when titles contain hashtags or recognized topic words.</p><?php endif; ?>
<div class="video-timeline">
<?php foreach (array_slice($tagVideos, 0, 100) as $video): ?>
<article class="video-card"><span class="channel-badge"><?= dashboard_escape($video['channel_name']) ?></span>
<h3 class="video-title"><a href="<?= dashboard_escape($video['url']) ?>" target="_blank" rel="noopener"><?= dashboard_escape($video['title']) ?></a></h3>
<p><?= dashboard_escape($video['published_date']) ?> UTC · <?= dashboard_escape(implode(', ', $video['tags'])) ?></p></article>
<?php endforeach; ?></div></div>
