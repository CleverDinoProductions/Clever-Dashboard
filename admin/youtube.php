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
