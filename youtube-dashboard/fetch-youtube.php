<?php
/** CLI-only YouTube Atom feed importer. Uses the dashboard's shared configuration. */

function youtubeInitDb(string $path): SQLite3
{
    $db = new SQLite3($path);
    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('CREATE TABLE IF NOT EXISTS videos (video_id TEXT PRIMARY KEY, channel_name TEXT, channel_id TEXT, title TEXT, published_date TEXT, url TEXT, detected_location TEXT, first_seen TEXT)');
    $db->exec('CREATE TABLE IF NOT EXISTS channels (channel_id TEXT PRIMARY KEY, channel_name TEXT, last_checked TEXT, video_count INTEGER DEFAULT 0)');
    $db->exec('CREATE TABLE IF NOT EXISTS upload_gaps (id INTEGER PRIMARY KEY AUTOINCREMENT, channel_id TEXT, video_id TEXT, gap_days INTEGER, previous_video_date TEXT, current_video_date TEXT)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_published_date ON videos(published_date)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_channel_id ON videos(channel_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_gap_days ON upload_gaps(gap_days)');
    return $db;
}

function youtubeQuery(SQLite3 $db, string $sql, array $params = []): SQLite3Result
{
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, $value === null ? SQLITE3_NULL : (is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT));
    }
    return $stmt->execute();
}

function youtubeDetectLocation(string $title): ?string
{
    $locations = [
        'UK' => ['UK', 'England', 'British', 'London', 'Hull'],
        'Germany' => ['Germany', 'Berlin', 'Munich'],
        'Spain' => ['Spain', 'Barcelona', 'Madrid'],
        'France' => ['France', 'Paris'],
        'USA' => ['California', 'America', 'Home'],
    ];
    foreach ($locations as $country => $keywords) {
        foreach ($keywords as $keyword) {
            if (stripos($title, $keyword) !== false) {
                return $country;
            }
        }
    }
    return null;
}

function youtubeDownloadFeed(string $channelId): string
{
    $curl = curl_init('https://www.youtube.com/feeds/videos.xml?channel_id=' . rawurlencode($channelId));
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Clever-Dashboard YouTube RSS Fetcher/1.0',
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    ]);
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($body === false || $status !== 200) {
        throw new RuntimeException('RSS request failed (HTTP ' . $status . ')' . ($error ? ': ' . $error : ''));
    }
    return $body;
}

/** Parse and validate the whole feed before changing channel data. */
function youtubeParseFeed(string $body, string $channelId): array
{
    $previous = libxml_use_internal_errors(true);
    try {
        $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NONET);
        if ($xml === false || $xml->getName() !== 'feed' || preg_match('/<!DOCTYPE/i', $body)) {
            throw new RuntimeException('Invalid Atom feed');
        }
        $atom = $xml->children('http://www.w3.org/2005/Atom');
        $youtube = $xml->children('http://www.youtube.com/xml/schemas/2015');
        if ((string) $youtube->channelId !== $channelId) {
            throw new RuntimeException('Feed channel does not match the configured channel');
        }
        $entries = [];
        foreach ($atom->entry as $entry) {
            $yt = $entry->children('http://www.youtube.com/xml/schemas/2015');
            $id = (string) $yt->videoId;
            $title = (string) $entry->title;
            $published = (string) $entry->published;
            if (!preg_match('/^[A-Za-z0-9_-]{11}$/D', $id) || $title === '' || $published === '') {
                throw new RuntimeException('Feed entry is missing valid video metadata');
            }
            $date = new DateTimeImmutable($published);
            $entries[] = [
                'id' => $id, 'title' => $title,
                'published' => $date->setTimezone(new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM),
                'timestamp' => $date->getTimestamp(),
            ];
        }
        // RSS arrives newest first; import oldest first so new videos can find their predecessors.
        usort($entries, static fn(array $a, array $b): int => $a['timestamp'] <=> $b['timestamp']);
        return $entries;
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
}

function youtubeImportFeed(SQLite3 $db, string $name, string $channelId, string $body): int
{
    $entries = youtubeParseFeed($body, $channelId);
    $new = 0;
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $db->exec('BEGIN IMMEDIATE');
    try {
        foreach ($entries as $entry) {
            if (youtubeQuery($db, 'SELECT 1 FROM videos WHERE video_id = :id', [':id' => $entry['id']])->fetchArray()) {
                continue;
            }
            $previous = youtubeQuery($db, 'SELECT published_date FROM videos WHERE channel_id = :channel AND julianday(published_date) < julianday(:published) ORDER BY julianday(published_date) DESC LIMIT 1', [':channel' => $channelId, ':published' => $entry['published']])->fetchArray(SQLITE3_ASSOC);
            youtubeQuery($db, 'INSERT INTO videos (video_id, channel_name, channel_id, title, published_date, url, detected_location, first_seen) VALUES (:id, :name, :channel, :title, :published, :url, :location, :seen)', [
                ':id' => $entry['id'], ':name' => $name, ':channel' => $channelId, ':title' => $entry['title'],
                ':published' => $entry['published'], ':url' => 'https://www.youtube.com/watch?v=' . $entry['id'],
                ':location' => youtubeDetectLocation($entry['title']), ':seen' => $now,
            ]);
            if ($previous) {
                $gap = (int) floor(($entry['timestamp'] - (new DateTimeImmutable($previous['published_date']))->getTimestamp()) / 86400);
                if ($gap > 0) {
                    youtubeQuery($db, 'INSERT INTO upload_gaps (channel_id, video_id, gap_days, previous_video_date, current_video_date) VALUES (:channel, :id, :gap, :previous, :current)', [':channel' => $channelId, ':id' => $entry['id'], ':gap' => $gap, ':previous' => $previous['published_date'], ':current' => $entry['published']]);
                }
            }
            $new++;
        }
        youtubeQuery($db, 'INSERT INTO channels (channel_id, channel_name, last_checked, video_count) VALUES (:channel, :name, :checked, (SELECT COUNT(*) FROM videos WHERE channel_id = :channel)) ON CONFLICT(channel_id) DO UPDATE SET channel_name = excluded.channel_name, last_checked = excluded.last_checked, video_count = excluded.video_count', [':channel' => $channelId, ':name' => $name, ':checked' => $now]);
        $db->exec('COMMIT');
    } catch (Throwable $error) {
        $db->exec('ROLLBACK');
        throw $error;
    }
    return $new;
}

function youtubeFetcherMain(): int
{
    require __DIR__ . '/config.php';
    foreach (['sqlite3', 'simplexml', 'curl'] as $extension) {
        if (!extension_loaded($extension)) {
            fwrite(STDERR, "Required PHP extension missing: $extension\n");
            return 1;
        }
    }
    $db = youtubeInitDb(DB_PATH);
    $total = 0;
    $failed = false;
    foreach ($CHANNELS as $name => $channelId) {
        echo "\n$name\n";
        try {
            $new = youtubeImportFeed($db, $name, $channelId, youtubeDownloadFeed($channelId));
            $total += $new;
            echo "  $new new video(s)\n";
        } catch (Throwable $error) {
            $failed = true;
            fwrite(STDERR, '  Failed: ' . $error->getMessage() . "\n");
        }
    }
    $db->close();
    echo "\nFound $total new video(s)\n";
    return $failed ? 1 : 0;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    if (PHP_SAPI !== 'cli') {
        http_response_code(403);
        exit('Run this fetcher from the command line.');
    }
    try {
        exit(youtubeFetcherMain());
    } catch (Throwable $error) {
        fwrite(STDERR, 'Fetcher failed: ' . $error->getMessage() . "\n");
        exit(1);
    }
}
