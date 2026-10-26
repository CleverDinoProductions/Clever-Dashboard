<?php
require __DIR__ . '/../fetch-youtube.php';

function check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function feed(array $entries): string {
    $xml = '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:yt="http://www.youtube.com/xml/schemas/2015"><yt:channelId>test-channel</yt:channelId>';
    foreach ($entries as [$id, $title, $published]) {
        $xml .= '<entry><yt:videoId>' . $id . '</yt:videoId><title>' . htmlspecialchars($title, ENT_XML1) . '</title><published>' . $published . '</published></entry>';
    }
    return $xml . '</feed>';
}
$db = youtubeInitDb(':memory:');
$xml = feed([
    ['newvideo001', 'Paris & friends', '2026-10-20T10:00:00Z'],
    ['oldvideo001', 'At home', '2026-10-10T10:00:00Z'],
]);
check(youtubeImportFeed($db, 'Test', 'test-channel', $xml) === 2, 'Initial import count');
check($db->querySingle('SELECT gap_days FROM upload_gaps') === 10, 'Reverse-ordered feed gets correct gap');
check($db->querySingle("SELECT detected_location FROM videos WHERE video_id = 'newvideo001'") === 'France', 'Location detection');
check($db->querySingle("SELECT title FROM videos WHERE video_id = 'newvideo001'") === 'Paris & friends', 'XML entity decoding');
check(youtubeImportFeed($db, 'Test', 'test-channel', $xml) === 0, 'Rerun is idempotent');
check($db->querySingle('SELECT COUNT(*) FROM upload_gaps') === 1, 'Rerun does not duplicate gaps');
check($db->querySingle('SELECT video_count FROM channels') === 2, 'Channel count');
$db->exec("UPDATE channels SET last_checked = 'previous-success'");
foreach (['<html>Error</html>', '<feed', str_replace('test-channel', 'wrong-channel', $xml), feed([['bad-id', 'Bad entry', '2026-10-20T00:00:00Z']])] as $bad) {
    $rejected = false;
    try { youtubeImportFeed($db, 'Test', 'test-channel', $bad); }
    catch (Throwable $error) { $rejected = true; }
    check($rejected, 'Invalid feed rejected');
    check($db->querySingle('SELECT last_checked FROM channels') === 'previous-success', 'Failed feed preserves freshness');
    check($db->querySingle('SELECT COUNT(*) FROM videos') === 2, 'Failed feed preserves videos');
}
check(youtubeImportFeed($db, 'Test', 'test-channel', feed([])) === 0, 'Valid empty feed');
// Force a write failure after the video insertion to verify transaction rollback.
$db->exec("CREATE TRIGGER fail_gap BEFORE INSERT ON upload_gaps BEGIN SELECT RAISE(ABORT, 'test failure'); END");
$rejected = false;
try { youtubeImportFeed($db, 'Test', 'test-channel', feed([['latervid001', 'Later upload', '2026-10-25T00:00:00Z']])); }
catch (Throwable $error) { $rejected = true; }
check($rejected && $db->querySingle('SELECT COUNT(*) FROM videos') === 2, 'Write failure rolls back inserted video');
$db->close();
echo "PASS: import, gaps, idempotency, XML, empty/error feeds and rollback\n";
