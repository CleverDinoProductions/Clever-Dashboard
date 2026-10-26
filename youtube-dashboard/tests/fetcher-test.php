<?php
require __DIR__ . '/../fetch-youtube.php';

const TEST_CHANNEL = 'UCaaaaaaaaaaaaaaaaaaaaaa';

function check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function feed(array $entries): string {
    $xml = '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:yt="http://www.youtube.com/xml/schemas/2015"><yt:channelId>' . TEST_CHANNEL . '</yt:channelId>';
    foreach ($entries as [$id, $title, $published]) {
        $xml .= '<entry><yt:channelId>' . TEST_CHANNEL . '</yt:channelId><yt:videoId>' . $id . '</yt:videoId><title>' . htmlspecialchars($title, ENT_XML1) . '</title><published>' . $published . '</published></entry>';
    }
    return $xml . '</feed>';
}
$db = youtubeInitDb(':memory:');
$xml = feed([
    ['newvideo001', 'Paris & friends', '2026-10-20T10:00:00Z'],
    ['oldvideo001', 'At home', '2026-10-10T10:00:00Z'],
]);
check(youtubeImportFeed($db, 'Test', TEST_CHANNEL, $xml) === 2, 'Initial import count');
check($db->querySingle('SELECT gap_days FROM upload_gaps') === 10, 'Reverse-ordered feed gets correct gap');
check($db->querySingle("SELECT detected_location FROM videos WHERE video_id = 'newvideo001'") === 'France', 'Location detection');
check($db->querySingle("SELECT title FROM videos WHERE video_id = 'newvideo001'") === 'Paris & friends', 'XML entity decoding');
check(youtubeImportFeed($db, 'Test', TEST_CHANNEL, $xml) === 0, 'Rerun is idempotent');
check($db->querySingle('SELECT COUNT(*) FROM upload_gaps') === 1, 'Rerun does not duplicate gaps');
check($db->querySingle('SELECT video_count FROM channels') === 2, 'Channel count');
$db->exec("UPDATE channels SET last_checked = 'previous-success'");
foreach (['<html>Error</html>', '<feed', str_replace(TEST_CHANNEL, 'wrong-channel', $xml), feed([['bad-id', 'Bad entry', '2026-10-20T00:00:00Z']])] as $bad) {
    $rejected = false;
    try { youtubeImportFeed($db, 'Test', TEST_CHANNEL, $bad); }
    catch (Throwable $error) { $rejected = true; }
    check($rejected, 'Invalid feed rejected');
    check($db->querySingle('SELECT last_checked FROM channels') === 'previous-success', 'Failed feed preserves freshness');
    check($db->querySingle('SELECT COUNT(*) FROM videos') === 2, 'Failed feed preserves videos');
}

// Invalid configured IDs must fail before any request or database change.
foreach (['', 'test-channel', TEST_CHANNEL . "\n", strtolower(TEST_CHANNEL), substr(TEST_CHANNEL, 1)] as $invalid) {
    foreach (['youtubeDownloadFeed', 'youtubeParseFeed'] as $operation) {
        $rejected = false;
        try {
            if ($operation === 'youtubeDownloadFeed') $operation($invalid);
            else $operation(feed([]), $invalid);
        } catch (RuntimeException $error) {
            $rejected = $error->getMessage() === 'Invalid YouTube channel ID';
        }
        check($rejected, 'Invalid channel ID rejected before fetching or parsing');
    }
}
$valid = feed([['Abc_12-xyZ9', 'Valid', '2026-10-20T00:00:00Z']]);
$entryMismatch = str_replace('<entry><yt:channelId>' . TEST_CHANNEL,
    '<entry><yt:channelId>UCbbbbbbbbbbbbbbbbbbbbbb', $valid);
$missingChannel = str_replace('<entry><yt:channelId>' . TEST_CHANNEL . '</yt:channelId>', '<entry>', $valid);
foreach ([$entryMismatch, $missingChannel,
    str_replace('Abc_12-xyZ9', 'Abc_12-xyZ9' . "\n", $valid),
    str_replace('Abc_12-xyZ9', 'Abc!12-xyZ9', $valid)] as $bad) {
    $rejected = false;
    try { youtubeImportFeed($db, 'Test', TEST_CHANNEL, $bad); }
    catch (Throwable $error) { $rejected = true; }
    check($rejected, 'Invalid entry IDs rejected');
    check($db->querySingle('SELECT COUNT(*) FROM videos') === 2, 'Invalid IDs preserve videos');
    check($db->querySingle('SELECT last_checked FROM channels') === 'previous-success', 'Invalid IDs preserve freshness');
}
// YouTube IDs are case sensitive, and can include hyphens and underscores.
$caseDb = youtubeInitDb(':memory:');
$caseFeed = feed([
    ['Abc_12-xyZ9', 'Upper', '2026-10-20T00:00:00Z'],
    ['abc_12-xyZ9', 'Lower', '2026-10-20T00:00:00Z'],
]);
check(youtubeImportFeed($caseDb, 'Test', TEST_CHANNEL, $caseFeed) === 2, 'Case-distinct IDs imported');
check(youtubeImportFeed($caseDb, 'Test', TEST_CHANNEL, $caseFeed) === 0, 'Case-distinct IDs deduplicated on rerun');
$caseDb->close();

check(youtubeImportFeed($db, 'Test', TEST_CHANNEL, feed([])) === 0, 'Valid empty feed');
// Force a write failure after the video insertion to verify transaction rollback.
$db->exec("CREATE TRIGGER fail_gap BEFORE INSERT ON upload_gaps BEGIN SELECT RAISE(ABORT, 'test failure'); END");
$rejected = false;
try { youtubeImportFeed($db, 'Test', TEST_CHANNEL, feed([['latervid001', 'Later upload', '2026-10-25T00:00:00Z']])); }
catch (Throwable $error) { $rejected = true; }
check($rejected && $db->querySingle('SELECT COUNT(*) FROM videos') === 2, 'Write failure rolls back inserted video');
$db->close();
echo "PASS: import, gaps, idempotency, XML, empty/error feeds and rollback\n";
