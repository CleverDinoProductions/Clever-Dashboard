<?php
// Request-local read views. Importers continue using the original persistent tables.
function dashboard_input($key) {
    return is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : '';
}
function dashboard_escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
$selectedChannel = dashboard_input('channel');
$selectedPeriod = in_array(dashboard_input('period'), ['7', '30', '90'], true) ? dashboard_input('period') : '';
$db->createFunction('dashboard_channel', static function () use ($selectedChannel) { return $selectedChannel; }, 0);
$db->createFunction('dashboard_period', static function () use ($selectedPeriod) { return $selectedPeriod; }, 0);
$db->exec("CREATE TEMP VIEW dashboard_videos AS SELECT * FROM main.videos
    WHERE (dashboard_channel() = '' OR channel_id = dashboard_channel())
    AND (dashboard_period() = '' OR julianday(published_date) >= julianday('now', '-' || dashboard_period() || ' days'))");
$db->exec("CREATE TEMP VIEW dashboard_channels AS SELECT * FROM main.channels
    WHERE dashboard_channel() = '' OR channel_id = dashboard_channel()");
$db->exec("CREATE TEMP VIEW dashboard_upload_gaps AS SELECT g.* FROM main.upload_gaps g
    JOIN dashboard_videos v ON g.video_id = v.video_id");
function dashboard_url($changes = []) {
    global $selectedChannel, $selectedPeriod;
    return dashboard_escape('?' . http_build_query(array_merge([
        'channel' => $selectedChannel, 'period' => $selectedPeriod
    ], $changes)));
}
function dashboard_hidden_filters() {
    global $selectedChannel, $selectedPeriod;
    foreach (['channel' => $selectedChannel, 'period' => $selectedPeriod] as $key => $value) {
        echo '<input type="hidden" name="' . $key . '" value="' . dashboard_escape($value) . '">';
    }
}
function dashboard_filters() {
    global $tab, $selectedChannel, $selectedPeriod;
    $options = query('SELECT channel_id, MAX(channel_name) AS channel_name FROM (SELECT channel_id, channel_name FROM channels UNION SELECT channel_id, channel_name FROM videos) GROUP BY channel_id ORDER BY channel_name');
    ?>
    <form class="timeline-filters" method="get">
        <input type="hidden" name="tab" value="<?= dashboard_escape($tab) ?>">
        <?php foreach (['q', 'tag', 'month'] as $key): if (dashboard_input($key) !== ''): ?>
        <input type="hidden" name="<?= $key ?>" value="<?= dashboard_escape(dashboard_input($key)) ?>">
        <?php endif; endforeach; ?>
        <label>Channel<select name="channel"><option value="">All channels</option>
        <?php $known = false; while ($option = $options->fetchArray(SQLITE3_ASSOC)): $known = $known || $selectedChannel === $option['channel_id']; ?>
            <option value="<?= dashboard_escape($option['channel_id']) ?>" <?= $selectedChannel === $option['channel_id'] ? 'selected' : '' ?>><?= dashboard_escape($option['channel_name'] . ' · ' . $option['channel_id']) ?></option>
        <?php endwhile; if ($selectedChannel !== '' && !$known): ?>
            <option selected value="<?= dashboard_escape($selectedChannel) ?>">Unknown channel</option>
        <?php endif; ?></select></label>
        <label>Published<select name="period"><option value="">All time</option>
        <?php foreach (['7', '30', '90'] as $days): ?><option value="<?= $days ?>" <?= $selectedPeriod === $days ? 'selected' : '' ?>>Last <?= $days ?> days</option><?php endforeach; ?>
        </select></label>
        <button type="submit">Apply filters</button><a href="?tab=<?= dashboard_escape($tab) ?>">Reset</a>
        <p class="filter-note">All video totals use this selection. Channel checks are current; gaps may start before the selected period. Dates are UTC.</p>
    </form>
    <?php if (!(int) getValue('SELECT COUNT(*) FROM dashboard_videos')): ?>
    <p class="empty-state">No cached uploads match this selection. Try another channel or a wider date range.</p>
    <?php endif;
}
