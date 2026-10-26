<?php
/** Shared YouTube RSS and API importer; direct execution is CLI-only. */
require_once dirname(__DIR__) . '/lib/youtube-settings.php';

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

function youtubeValidateChannelId(string $channelId): void
{
    if (!preg_match('/^UC[A-Za-z0-9_-]{22}$/D', $channelId)) {
        throw new RuntimeException('Invalid YouTube channel ID');
    }
}

function youtubeDownloadFeed(string $channelId): string
{
    youtubeValidateChannelId($channelId);
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
    youtubeValidateChannelId($channelId);
    $previous = libxml_use_internal_errors(true);
    try {
        $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NONET);
        if ($xml === false || $xml->getName() !== 'feed' || preg_match('/<!DOCTYPE/i', $body)) {
            throw new RuntimeException('Invalid Atom feed');
        }
        $atom = $xml->children('http://www.w3.org/2005/Atom');
        $youtube = $xml->children('http://www.youtube.com/xml/schemas/2015');
        // YouTube's feed-level channelId can omit UC; entries use the full ID.
        $feedChannelId = (string) $youtube->channelId;
        if ($feedChannelId !== $channelId && $feedChannelId !== substr($channelId, 2)) {
            throw new RuntimeException('Feed channel does not match the configured channel');
        }
        $entries = [];
        foreach ($atom->entry as $entry) {
            $yt = $entry->children('http://www.youtube.com/xml/schemas/2015');
            if ((string) $yt->channelId !== $channelId) {
                throw new RuntimeException('Entry channel does not match the configured channel');
            }
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
    return youtubeImportEntries($db, $name, $channelId, $entries);
}

function youtubeImportEntries(SQLite3 $db, string $name, string $channelId, array $entries): int
{
    usort($entries, static fn(array $a, array $b): int => $a['timestamp'] <=> $b['timestamp']);
    $new = 0;
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $db->exec('BEGIN IMMEDIATE');
    try {
        foreach ($entries as $entry) {
            if (youtubeQuery($db, 'SELECT 1 FROM videos WHERE video_id = :id', [':id' => $entry['id']])->fetchArray()) {
                continue;
            }
            youtubeQuery($db, 'INSERT INTO videos (video_id, channel_name, channel_id, title, published_date, url, detected_location, first_seen) VALUES (:id, :name, :channel, :title, :published, :url, :location, :seen)', [
                ':id' => $entry['id'], ':name' => $name, ':channel' => $channelId, ':title' => $entry['title'],
                ':published' => $entry['published'], ':url' => 'https://www.youtube.com/watch?v=' . $entry['id'],
                ':location' => youtubeDetectLocation($entry['title']), ':seen' => $now,
            ]);
            $new++;
        }
        if ($new > 0) {
            youtubeQuery($db, 'DELETE FROM upload_gaps WHERE channel_id = :channel', [':channel' => $channelId]);
            $rows = youtubeQuery($db, 'SELECT video_id, published_date FROM videos WHERE channel_id = :channel ORDER BY julianday(published_date), video_id', [':channel' => $channelId]);
            $previousDate = null;
            while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
                if ($previousDate !== null) {
                    $gap = (int)floor(((new DateTimeImmutable($row['published_date']))->getTimestamp() - (new DateTimeImmutable($previousDate))->getTimestamp()) / 86400);
                    if ($gap > 0) youtubeQuery($db, 'INSERT INTO upload_gaps (channel_id, video_id, gap_days, previous_video_date, current_video_date) VALUES (:channel, :id, :gap, :previous, :current)', [':channel' => $channelId, ':id' => $row['video_id'], ':gap' => $gap, ':previous' => $previousDate, ':current' => $row['published_date']]);
                }
                $previousDate = $row['published_date'];
            }
        }
        youtubeQuery($db, 'INSERT INTO channels (channel_id, channel_name, last_checked, video_count) VALUES (:channel, :name, :checked, (SELECT COUNT(*) FROM videos WHERE channel_id = :channel)) ON CONFLICT(channel_id) DO UPDATE SET channel_name = excluded.channel_name, last_checked = excluded.last_checked, video_count = excluded.video_count', [':channel' => $channelId, ':name' => $name, ':checked' => $now]);
        $db->exec('COMMIT');
    } catch (Throwable $error) {
        $db->exec('ROLLBACK');
        throw $error;
    }
    return $new;
}

function youtubeApiRequest(string $resource, array $params): array
{
    $key = getenv('YOUTUBE_API_KEY');
    if (!$key) throw new RuntimeException('Set YOUTUBE_API_KEY on the server to enable API backfilling.');
    $curl = curl_init('https://www.googleapis.com/youtube/v3/' . $resource . '?' . http_build_query($params));
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_HTTPHEADER => ['X-Goog-Api-Key: ' . $key]]);
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    // Do not echo provider responses or request URLs containing credentials.
    if ($body === false || $status !== 200) throw new RuntimeException('YouTube API request failed (HTTP ' . $status . '). Check API access and quota.');
    $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) throw new RuntimeException('Invalid YouTube API response.');
    return $data;
}

/** Walk every uploads page: playlist order is not a reliable date cutoff. */
function youtubeApiEntries(string $channelId, array $settings, ?callable $request = null): array
{
    youtubeValidateChannelId($channelId);
    $settings = clever_validate_youtube_fetch_settings($settings);
    $request = $request ?? 'youtubeApiRequest';
    $channel = $request('channels', ['part' => 'contentDetails', 'id' => $channelId]);
    $playlist = $channel['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ?? null;
    if (!is_string($playlist) || $playlist === '') throw new RuntimeException('Channel uploads playlist is unavailable.');
    $entries = [];
    $token = '';
    $seen = [];
    do {
        $params = ['part' => 'snippet,contentDetails', 'playlistId' => $playlist, 'maxResults' => 50];
        if ($token !== '') $params['pageToken'] = $token;
        $page = $request('playlistItems', $params);
        foreach ($page['items'] as $item) {
            $snippet = $item['snippet'] ?? [];
            $published = $item['contentDetails']['videoPublishedAt'] ?? null;
            // Deleted/private videos have no public publication timestamp.
            if ($published === null) continue;
            $id = $item['contentDetails']['videoId'] ?? '';
            if (!is_string($id) || !preg_match('/^[A-Za-z0-9_-]{11}$/D', $id)
                || ($snippet['channelId'] ?? '') !== $channelId || !is_string($snippet['title'] ?? null) || $snippet['title'] === ''
                || !is_string($published) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/D', $published)) {
                throw new RuntimeException('Invalid YouTube API video metadata.');
            }
            $date = (new DateTimeImmutable($published))->setTimezone(new DateTimeZone('UTC'));
            $day = $date->format('Y-m-d');
            if ($day < $settings['start_date'] || $day > $settings['end_date']) continue;
            $entries[$id] = ['id' => $id, 'title' => $snippet['title'], 'published' => $date->format(DateTimeInterface::ATOM), 'timestamp' => $date->getTimestamp()];
        }
        $token = $page['nextPageToken'] ?? '';
        if (!is_string($token) || isset($seen[$token])) throw new RuntimeException('Invalid YouTube API pagination.');
        if ($token !== '') $seen[$token] = true;
    } while ($token !== '');
    return array_values($entries);
}

/** Shared runner used by CLI and the authenticated, CSRF-protected admin action. */
function youtubeRunFetcher(bool $requireApi = false): array
{
    require_once __DIR__ . '/config.php';
    foreach (['sqlite3', 'simplexml', 'curl'] as $extension) {
        if (!extension_loaded($extension)) throw new RuntimeException('Required PHP extension missing: ' . $extension);
    }
    if ($requireApi && !getenv('YOUTUBE_API_KEY')) throw new RuntimeException('Set YOUTUBE_API_KEY on the server to enable API backfilling.');
    $settings = clever_youtube_fetch_settings();
    $lock = fopen(__DIR__ . '/.fetch.lock', 'c');
    if (!$lock) throw new RuntimeException('Unable to open fetch lock. Check directory permissions.');
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        fclose($lock);
        throw new RuntimeException('A YouTube fetch is already running.');
    }
    $db = null;
    $messages = [];
    $total = 0;
    $failed = false;
    try {
        $db = youtubeInitDb(DB_PATH);
        foreach (clever_youtube_channels() as $name => $channelId) {
            // RSS failure must not prevent API recovery, or vice versa.
            $sources = ['RSS' => static fn() => youtubeParseFeed(youtubeDownloadFeed($channelId), $channelId)];
            if (getenv('YOUTUBE_API_KEY')) $sources['API'] = static fn() => youtubeApiEntries($channelId, $settings);
            foreach ($sources as $source => $fetch) {
                try {
                    $new = youtubeImportEntries($db, $name, $channelId, $fetch());
                    $total += $new;
                    $messages[] = "$name ($source): $new new video(s)";
                } catch (Throwable $error) {
                    $failed = true;
                    $messages[] = "$name ($source): Failed: " . $error->getMessage();
                }
            }
        }
    } finally {
        if ($db) $db->close();
        flock($lock, LOCK_UN);
        fclose($lock);
    }
    $messages[] = "Found $total new video(s)";
    return ['failed' => $failed, 'messages' => $messages];
}

function youtubeFetcherMain(): int
{
    $result = youtubeRunFetcher();
    echo implode("\n", $result['messages']) . "\n";
    return $result['failed'] ? 1 : 0;
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
