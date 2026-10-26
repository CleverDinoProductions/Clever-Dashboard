<?php
// Panel component: access is restricted to administrators by admin.php.
if (!isset($isAccountAdmin) || !$isAccountAdmin) {
    http_response_code(403);
    exit('Administrator access is required.');
}
$channelText = '';
try {
    foreach (clever_youtube_channels() as $name => $id) $channelText .= $name . ' | ' . $id . "\n";
} catch (Throwable $exception) {
    echo '<div class="error">' . $h($exception->getMessage()) . '</div>';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_string($_POST['channels'] ?? null)) $channelText = $_POST['channels'];
?>
<section class="panel">
    <h2>YouTube channels</h2>
    <p>Enter one channel per line as <strong>Channel name | Channel ID</strong>. Use the channel ID starting with UC, rather than a handle or URL. Remove a line to stop fetching that channel. Leave empty to stop all channel fetching.</p>
    <p class="muted">Changes apply on the next fetcher run. Previously imported videos remain in the dashboard.</p>
    <form method="post" class="stack">
        <input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>">
        <input type="hidden" name="action" value="save_youtube_channels">
        <label>Channels<textarea name="channels" rows="10" spellcheck="false"><?= $h($channelText) ?></textarea></label>
        <button>Save YouTube channels</button>
    </form>
</section>

<?php
try { $fetchSettings = clever_youtube_fetch_settings(); }
catch (Throwable $exception) {
    $fetchSettings = ['start_date' => '', 'end_date' => ''];
    echo '<div class="error">' . $h($exception->getMessage()) . '</div>';
}
foreach (['start_date', 'end_date'] as $key) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_string($_POST[$key] ?? null)) $fetchSettings[$key] = $_POST[$key];
}
?>
<section class="panel">
    <h2>Fetch missing YouTube videos</h2>
    <p>Backfill public uploads missing from RSS using the YouTube Data API. Both dates are inclusive (UTC). Already imported videos are skipped. RSS still fetches recent uploads outside this range.</p>
    <p class="muted"><?= getenv('YOUTUBE_API_KEY') ? 'YouTube API key is configured.' : 'Set YOUTUBE_API_KEY in the PHP server environment to enable API fetching.' ?> Large channel histories can take several minutes and consume API quota.</p>
    <form method="post" class="stack">
        <input type="hidden" name="csrf_token" value="<?= clever_csrf_token() ?>">
        <label>Start date<input type="date" name="start_date" value="<?= $h($fetchSettings['start_date']) ?>" required></label>
        <label>End date<input type="date" name="end_date" value="<?= $h($fetchSettings['end_date']) ?>" required></label>
        <button name="action" value="save_youtube_fetch_settings">Save date range</button>
        <button name="action" value="run_youtube_fetch" <?= getenv('YOUTUBE_API_KEY') ? '' : 'disabled' ?>>Save and run fetch now</button>
    </form>
</section>
