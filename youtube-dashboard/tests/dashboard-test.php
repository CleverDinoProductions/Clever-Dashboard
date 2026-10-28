<?php
// Offline fixture coverage: php youtube-dashboard/tests/dashboard-test.php [channel-id]
date_default_timezone_set('UTC');
define('CRITICAL_GAP_DAYS', 14);
define('WARNING_GAP_DAYS', 7);
$db = new SQLite3(':memory:');
$db->exec('CREATE TABLE videos (video_id TEXT PRIMARY KEY, channel_id TEXT, channel_name TEXT, title TEXT, published_date TEXT, detected_location TEXT, url TEXT)');
$db->exec('CREATE TABLE channels (channel_id TEXT PRIMARY KEY, channel_name TEXT, last_checked TEXT)');
$db->exec('CREATE TABLE upload_gaps (video_id TEXT, channel_id TEXT, gap_days INTEGER, previous_video_date TEXT, current_video_date TEXT)');
$db->exec("INSERT INTO channels VALUES ('A', 'Same name', datetime('now')), ('B', 'Same name', NULL), ('empty', 'No uploads', NULL)");
$db->exec("INSERT INTO videos VALUES ('a', 'A', 'Same name', 'Walking #food #London', datetime('now'), 'UK', 'https://youtube.com/watch?v=a'), ('b', 'B', 'Same name', 'FOREIGN_TITLE #review', datetime('now'), 'France', 'https://youtube.com/watch?v=b'), ('old', 'A', 'Same name', 'OLD_TITLE', '2001-01-01', NULL, 'https://youtube.com/watch?v=old')");
$db->exec("INSERT INTO upload_gaps VALUES ('a', 'A', 10, '2001-01-01', datetime('now')), ('b', 'B', 20, '2001-01-01', datetime('now'))");
function query($sql, $params = []) {
    global $db;
    $stmt = $db->prepare($sql);
    if (!$stmt) throw new RuntimeException($db->lastErrorMsg());
    foreach ($params as $key => $value) $stmt->bindValue($key, $value);
    return $stmt->execute();
}
function getValue($sql, $params = []) { return query($sql, $params)->fetchArray(SQLITE3_NUM)[0] ?? null; }
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
set_error_handler(static function ($severity, $message, $file, $line) { throw new RuntimeException("$message at $file:$line"); });
$_GET = ['channel' => $argv[1] ?? 'A', 'period' => '7', 'q' => 'Same'];
require __DIR__ . '/../includes/filters.php';
$expected = $_GET['channel'] === 'A' ? 1 : 0;
check((int) getValue('SELECT COUNT(*) FROM dashboard_videos') === $expected, 'Video scope');
check((int) getValue('SELECT COUNT(*) FROM dashboard_upload_gaps') === $expected, 'Gap scope');
foreach (['timeline', 'channels', 'analytics', 'gaps', 'locations', 'frequency', 'trends', 'search', 'tags', 'calendar', 'health'] as $tab) {
    ob_start();
    require __DIR__ . '/../tabs/' . $tab . '.php';
    $html = ob_get_clean();
    check(!str_contains($html, 'FOREIGN_TITLE'), "$tab leaked another channel");
    check(!str_contains($html, 'OLD_TITLE'), "$tab ignored date range");
    check(str_contains($html, 'content-area'), "$tab failed to render");
}
check(dashboard_tags('Walking #food #London', ['UK']) === ['food', 'walking'], 'Tag deduplication, topics and location exclusion');
ob_start(); dashboard_filters(); $html = ob_get_clean();
check(str_contains($html, 'value="A"') && str_contains($html, 'value="B"'), 'Distinct same-name channel options');
check(str_contains(dashboard_url(['tab' => 'tags']), 'channel='), 'Filter navigation');
$tab = 'timeline';
ob_start(); require __DIR__ . '/../includes/header.php'; require __DIR__ . '/../includes/footer.php'; $shell = ob_get_clean();
check(str_contains($shell, 'tab=tags') && str_contains($shell, 'tab=calendar') && str_contains($shell, 'tab=health'), 'New tabs must be navigable');
check(str_contains($shell, 'channel=' . $_GET['channel']), 'Sidebar must preserve channel');
if ($_GET['channel'] === 'A') {
    for ($i = 0; $i < 51; $i++) {
        $db->exec("INSERT INTO videos VALUES ('page$i', 'A', 'Same name', 'Page test', datetime('now'), NULL, 'https://youtube.com')");
    }
    $tab = 'timeline';
    ob_start(); include __DIR__ . '/../tabs/timeline.php'; $html = ob_get_clean();
    check(str_contains($html, 'Older uploads'), 'Pagination must expose older uploads');
    $_GET['page'] = '2';
    ob_start(); include __DIR__ . '/../tabs/timeline.php'; $html = ob_get_clean();
    check(str_contains($html, 'Newer uploads'), 'Pagination must expose newer uploads');
}
echo "Dashboard fixture checks passed for " . $_GET['channel'] . "\n";
