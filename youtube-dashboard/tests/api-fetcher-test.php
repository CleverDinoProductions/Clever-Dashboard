<?php
require_once __DIR__ . '/fetcher-test.php';
require_once dirname(__DIR__, 2) . '/lib/youtube-settings.php';

$range = ['start_date' => '2026-10-10', 'end_date' => '2026-10-20'];
foreach ([['start_date' => '2026-02-30', 'end_date' => '2026-03-01'],
    ['start_date' => '2026-10-21', 'end_date' => '2026-10-20'],
    ['start_date' => [], 'end_date' => '2026-10-20']] as $invalid) {
    $rejected = false;
    try { clever_validate_youtube_fetch_settings($invalid); }
    catch (RuntimeException $error) { $rejected = true; }
    check($rejected, 'Invalid ranges rejected');
}
function apiVideo(string $id, string $date): array {
    return ['snippet' => ['channelId' => TEST_CHANNEL, 'title' => 'API upload'],
        'contentDetails' => ['videoId' => $id, 'videoPublishedAt' => $date]];
}
$calls = [];
$request = static function ($resource, $params) use (&$calls): array {
    $calls[] = [$resource, $params];
    if ($resource === 'channels') return ['items' => [['contentDetails' => ['relatedPlaylists' => ['uploads' => 'uploads-test']]]]];
    if (!isset($params['pageToken'])) return ['items' => [
        apiVideo('newvideo001', '2026-10-20T23:59:59Z'),
        apiVideo('outside0001', '2026-10-21T00:00:00Z'),
        apiVideo('outside0002', '2026-10-09T23:59:59Z'),
        ['snippet' => ['title' => 'Private video'], 'contentDetails' => ['videoId' => 'private0001']],
    ], 'nextPageToken' => 'page2'];
    check($params['pageToken'] === 'page2', 'Pagination token forwarded');
    return ['items' => [apiVideo('oldvideo001', '2026-10-10T00:00:00Z'),
        apiVideo('middlevi001', '2026-10-15T00:00:00Z')]];
};
$entries = youtubeApiEntries(TEST_CHANNEL, $range, $request);
check(count($entries) === 3 && count($calls) === 3, 'All pages fetched; inclusive date range; private uploads skipped');
$apiDb = youtubeInitDb(':memory:');
check(youtubeImportFeed($apiDb, 'Test', TEST_CHANNEL, feed([
    ['newvideo001', 'Recent RSS', '2026-10-20T23:59:59Z'],
    ['oldvideo001', 'Older RSS', '2026-10-10T00:00:00Z'],
])) === 2, 'RSS baseline');
check(youtubeImportEntries($apiDb, 'Test', TEST_CHANNEL, $entries) === 1, 'Only missing API video imported');
check($apiDb->querySingle("SELECT gap_days FROM upload_gaps WHERE video_id = 'newvideo001'") === 5, 'Backfill corrects existing successor gap');
check(youtubeImportEntries($apiDb, 'Test', TEST_CHANNEL, $entries) === 0, 'API rerun deduplicated');
check($apiDb->querySingle('SELECT video_count FROM channels') === 3, 'Combined channel count');
$rejected = false;
try {
    youtubeApiEntries(TEST_CHANNEL, $range, static function ($resource, $params) use ($request) {
        if (isset($params['pageToken'])) throw new RuntimeException('quota failure');
        return $request($resource, $params);
    });
} catch (RuntimeException $error) { $rejected = true; }
check($rejected && $apiDb->querySingle('SELECT COUNT(*) FROM videos') === 3, 'Later page failure preserves stored data');
$rejected = false;
try {
    youtubeApiEntries(TEST_CHANNEL, $range, static function ($resource, $params) use ($request) {
        $page = $request($resource, $params);
        if ($resource === 'playlistItems') $page['nextPageToken'] = 'page2';
        return $page;
    });
} catch (RuntimeException $error) { $rejected = true; }
check($rejected, 'Repeated page token rejected');
$apiDb->close();
echo "PASS: API pagination, ranges, deduplication, gap repair and failure handling\n";
