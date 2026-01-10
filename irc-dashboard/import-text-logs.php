<?php
// Import missing data from ALL channel text logs into SQLite

$dbpath = 'E:\\thelounge\\logs\\DinoDude.sqlite3';

try {
    $db = new PDO("sqlite:$dbpath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$network_uuid = 'myanonamouse--4914-9827-2b9e97529b74';
$log_dir = "E:\\thelounge\\logs\\DinoDude\\{$network_uuid}";

// Gap period - database was locked from 09:38:39 onwards
$gap_start = strtotime('2025-12-06 09:38:39 UTC') * 1000;
$now = time() * 1000;

// Get all .log files in the directory
$log_files = glob($log_dir . '/*.log');

if (empty($log_files)) {
    die("No log files found in: $log_dir\n");
}

echo "Found " . count($log_files) . " channel logs to process\n\n";

$total_imported = 0;
$total_skipped = 0;
$total_errors = 0;

foreach ($log_files as $log_file) {
    $channel = basename($log_file, '.log');
    
    echo "Processing: $channel\n";
    
    $imported = 0;
    $skipped = 0;
    $errors = 0;
    
    $handle = fopen($log_file, 'r');
    if (!$handle) {
        echo "  ✗ Could not open file\n\n";
        continue;
    }
    
    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Parse regular messages: [timestamp] <nick> text
        $is_message = preg_match('/^\[(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z)\]\s+<([^>]+)>\s+(.+)$/', $line, $matches);
        
        // Parse system messages: [timestamp] *** text
        $is_system = false;
        if (!$is_message) {
            $is_system = preg_match('/^\[(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z)\]\s+\*\*\*\s+(.+)$/', $line, $matches);
        }
        
        if (!$is_message && !$is_system) {
            continue;
        }
        
        $timestamp_str = $matches[1];
        
        // Convert to milliseconds
        $dt = new DateTime($timestamp_str, new DateTimeZone('UTC'));
        $time_ms = (int)($dt->getTimestamp() * 1000);
        
        // Only import from gap period
        if ($time_ms <= $gap_start || $time_ms > $now) {
            $skipped++;
            continue;
        }
        
        // Check if already exists
        $check = $db->prepare("SELECT id FROM messages WHERE time = ? AND channel = ? LIMIT 1");
        $check->execute([$time_ms, $channel]);
        if ($check->fetch()) {
            $skipped++;
            continue;
        }
        
        if ($is_message) {
            // Regular message
            $nick_raw = $matches[2];
            $text_raw = $matches[3];
            
            // Clean nick
            $nick = preg_replace('/^[&@+%~]/', '', $nick_raw);
            
            // Strip mIRC codes
            $text = preg_replace('/\x03\d{1,2}(,\d{1,2})?/', '', $text_raw);
            $text = str_replace(["\x02", "\x0F", "\x16", "\x1F"], '', $text);
            $text = trim($text, " \t\n\r\0\x0B\xEF\xBB\xBF");
            
            // Determine type
            $type = 'message';
            if (preg_match('/sets mode/', $text)) {
                $type = 'mode';
            }
            
            $msg = [
                'from' => ['nick' => $nick, 'mode' => ''],
                'text' => $text,
                'self' => false
            ];
            
        } else {
            // System message (join/quit/part/kick/nick)
            $system_text = $matches[2];
            
            // Parse different system message types
            if (preg_match('/^(\S+)\s+\(.*\)\s+(joined|quit|left)/', $system_text, $sm)) {
                $nick = $sm[1];
                $action = $sm[2];
                
                if ($action == 'joined') {
                    $type = 'join';
                } elseif ($action == 'quit') {
                    $type = 'quit';
                } else {
                    $type = 'part';
                }
                
                $msg = [
                    'from' => ['nick' => $nick, 'mode' => ''],
                    'text' => $system_text,
                    'self' => false
                ];
            } elseif (preg_match('/^(\S+)\s+changed nick to\s+(\S+)/', $system_text, $sm)) {
                $type = 'nick';
                $msg = [
                    'from' => ['nick' => $sm[1], 'mode' => ''],
                    'new_nick' => $sm[2],
                    'text' => $system_text,
                    'self' => false
                ];
            } else {
                // Unknown system message - skip
                continue;
            }
        }
        
        try {
            $stmt = $db->prepare("
                INSERT INTO messages (network, channel, time, type, msg)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $network_uuid,
                $channel,
                $time_ms,
                $type,
                json_encode($msg, JSON_UNESCAPED_UNICODE)
            ]);
            
            $imported++;
            
        } catch (Exception $e) {
            $errors++;
        }
    }
    
    fclose($handle);
    
    echo "  ✓ Imported: $imported | Skipped: $skipped | Errors: $errors\n\n";
    
    $total_imported += $imported;
    $total_skipped += $skipped;
    $total_errors += $errors;
}

echo "\n=== All Channels Import Complete ===\n";
echo "Total imported: $total_imported messages\n";
echo "Total skipped: $total_skipped\n";
echo "Total errors: $total_errors\n\n";

if ($total_imported > 0) {
    echo "✅ All channel gaps filled! $total_imported messages recovered.\n";
    echo "Your dashboard now has complete data across all channels!\n";
} else {
    echo "ℹ️ No new messages imported.\n";
}
?>
