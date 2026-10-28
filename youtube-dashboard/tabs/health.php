<?php
$rows = query('SELECT c.*, MAX(v.published_date) AS latest FROM dashboard_channels c LEFT JOIN videos v ON c.channel_id = v.channel_id GROUP BY c.channel_id ORDER BY julianday(c.last_checked) ASC');
?>
<div class="content-area"><h2>Feed health</h2><p class="tab-description">Find channels with overdue checks or no cached uploads. Check status uses the last successful import, independently of the published-date filter. This does not diagnose YouTube availability.</p>
<div class="channel-grid">
<?php while ($row = $rows->fetchArray(SQLITE3_ASSOC)):
$checked = $row['last_checked'] ? strtotime($row['last_checked'] . ' UTC') : false;
$stale = !$checked || time() - $checked > 86400;
?>
<article class="channel-card"><h3><?= dashboard_escape($row['channel_name']) ?></h3>
<p><strong><?= $stale ? 'Check overdue' : 'Checked within 24 hours' ?></strong></p>
<p>Last successful check: <?= dashboard_escape($row['last_checked'] ?: 'Never') ?><?= $checked ? ' UTC' : '' ?></p>
<p>Latest cached upload: <?= dashboard_escape($row['latest'] ?: 'None') ?></p>
<a href="<?= dashboard_url(['tab' => 'timeline', 'channel' => $row['channel_id']]) ?>">View uploads</a></article>
<?php endwhile; ?></div><p>Run the scheduled importer to refresh checks. RSS contains recent uploads only; backfill history through the admin panel when configured.</p></div>
