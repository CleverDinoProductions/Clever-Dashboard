<?php
// Text log → SQLite importer (multi-network, with proper JOIN parsing)

$dbpath = 'E:\\thelounge\\logs\\DinoDude.sqlite3';
$db = new PDO("sqlite:$dbpath");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Path to your text logs (seedbox + current)
$log_base_path = 'H:\\thelounge\\logs\\DinoDude';

echo "Starting multi-network text log import...\n\n";

// Get default network UUID from DB (current network)
$stmt = $db->query("SELECT DISTINCT network FROM messages LIMIT 1");
$network_result = $stmt->fetch(PDO::FETCH_ASSOC);
$default_network_uuid = $network_result['network'] ?? '1fb1ab27-0de1-4a0b-949b-ea925220b436';

echo "Default network UUID: $default_network_uuid\n\n";

// Find all MAM network folders under DinoDude
$all_folders = glob($log_base_path . '\\*', GLOB_ONLYDIR);
$mam_folders = [];

foreach ($all_folders as $folder) {
    $folder_name = basename($folder);
    if (stripos($folder_name, 'myanonamouse') !== false ||
        stripos($folder_name, 'anonamouse') !== false) {
        $mam_folders[] = $folder;
    }
}

if (empty($mam_folders)) {
    die("Error: No MAM network folders found in $log_base_path\n");
}

echo "Found " . count($mam_folders) . " MAM network folder(s):\n";
foreach ($mam_folders as $folder) {
    echo "  - " . basename($folder) . "\n";
}
echo "\n";

$total_imported = 0;
$skipped = 0;
$total_files = 0;

foreach ($mam_folders as $mam_folder) {
    echo "========================================\n";
    echo "Processing folder: " . basename($mam_folder) . "\n";
    echo "========================================\n\n";

    // Try to extract a UUID-ish tail from folder name, else use default
    $folder_name = basename($mam_folder);
    if (preg_match('/([a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]+)$/i', $folder_name, $matches)) {
        $network_uuid = $matches[1];
        echo "Detected network UUID from folder: $network_uuid\n";
    } else {
        $network_uuid = $default_network_uuid;
        echo "Using default network UUID: $network_uuid\n";
    }
    echo "\n";

    // All .log files in this network folder
    $log_files = glob($mam_folder . '\\*.log');

    if (count($log_files) == 0) {
        echo "No .log files found in this folder, skipping...\n\n";
        continue;
    }

    echo "Found " . count($log_files) . " log files\n\n";
    $total_files += count($log_files);

    foreach ($log_files as $filepath) {
        $filename = basename($filepath);
        $channel = str_replace('.log', '', $filename);

        // Only channel logs start with '#'
        if (!str_starts_with($channel, '#')) {
            echo "Skipping non-channel file: $filename\n";
            continue;
        }

        echo "Processing: $channel ... ";

        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $imported_this_file = 0;

        foreach ($lines as $line) {
            // [2025-11-26T21:30:50.640Z] <...>
            if (!preg_match('/^\[([^\]]+)\]\s+(.+)$/', $line, $matches)) {
                continue;
            }

            $timestamp_str = $matches[1];
            $content = $matches[2];

            // Strip BOM/control garbage
            $content = trim($content, "\xEF\xBB\xBF\x00..\x1F");

            // Parse ISO 8601 timestamp
            $dt = DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $timestamp_str);
            if (!$dt) {
                $dt = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $timestamp_str);
            }
            if (!$dt) {
                continue;
            }

            $timestamp_ms = $dt->getTimestamp() * 1000;

            // Defaults
            $type = 'message';
            $nick = null;
            $text = null;
            $mode = null;

            // 1) System lines starting with ***
            if (preg_match('/^\*\*\*\s+(.+)$/', $content, $sys_matches)) {
                $sys_content = $sys_matches[1];

                // *** Nick (ident@host) joined
                if (preg_match('/^(\S+)\s+\([^)]+\)\s+joined/', $sys_content, $join_matches)) {
                    $type = 'join';
                    $nick = $join_matches[1];
                    $text = "$nick has joined";
                }
                // *** Nick (ident@host) left / has left
                elseif (preg_match('/^(\S+)(?:\s+\([^)]+\))?\s+(?:has\s+)?left/', $sys_content, $part_matches)) {
                    $type = 'part';
                    $nick = $part_matches[1];
                    $text = "$nick has left";
                }
                // *** Nick (ident@host) quit / has quit
                elseif (preg_match('/^(\S+)(?:\s+\([^)]+\))?\s+(?:has\s+)?quit/', $sys_content, $quit_matches)) {
                    $type = 'quit';
                    $nick = $quit_matches[1];
                    $text = "$nick has quit";
                }
                // *** Nick sets mode ...
                elseif (preg_match('/^(\S+)\s+sets mode/', $sys_content, $mode_matches)) {
                    $type = 'mode';
                    $nick = $mode_matches[1];
                    $text = $sys_content;
                }
                // *** Nick removes VOICE from ...
                elseif (preg_match('/^(\S+)\s+removes/', $sys_content, $mode_matches)) {
                    $type = 'mode';
                    $nick = $mode_matches[1];
                    $text = $sys_content;
                }
                // *** Nick is now known as NewNick
                elseif (preg_match('/^(\S+)\s+is now known as\s+(\S+)/', $sys_content, $nick_matches)) {
                    $type = 'nick';
                    $nick = $nick_matches[1];
                    $text = "is now known as {$nick_matches[2]}";
                }
                else {
                    $type = 'system';
                    $text = $sys_content;
                }
            }

            // 2) Simple JOIN lines that slipped through (no *** prefix)
            //    e.g. "JOIN DinoDude" or "DinoDude JOIN"
            elseif (preg_match('/^JOIN\s+(\S+)/', $content, $m)) {
                $type = 'join';
                $nick = $m[1];
                $text = "$nick has joined";
            }
            elseif (preg_match('/^(\S+)\s+JOIN$/', $content, $m)) {
                $type = 'join';
                $nick = $m[1];
                $text = "$nick has joined";
            }

            // 3) Regular message: <@Nick> text (possibly with color code)
            elseif (preg_match('/^<(@|&|~|%|\+)?([^>]+)>\s+(?:\d+)?(.+)$/', $content, $msg_matches)) {
                $type = 'message';
                $mode = $msg_matches[1];
                $nick = $msg_matches[2];
                $text = $msg_matches[3];
            }

            // 4) Action message: * Nick does something
            elseif (preg_match('/^\*\s+(\S+)\s+(.+)$/', $content, $action_matches)) {
                $type = 'action';
                $nick = $action_matches[1];
                $text = $action_matches[2];
            }

            // 5) Unknown format → skip
            else {
                continue;
            }

            // Build JSON msg object
            $msg_obj = [
                'from' => [
                    'nick' => $nick,
                    'mode' => $mode,
                ],
                'text' => $text,
            ];
            $msg_json = json_encode($msg_obj);

            // Duplicate check (time + channel + type)
            $check_stmt = $db->prepare("
                SELECT COUNT(*) FROM messages
                WHERE time = :time
                  AND channel = :channel
                  AND type = :type
            ");
            $check_stmt->execute([
                'time'    => $timestamp_ms,
                'channel' => $channel,
                'type'    => $type,
            ]);

            if ($check_stmt->fetchColumn() > 0) {
                $skipped++;
                continue;
            }

            // Insert row
            try {
                $stmt2 = $db->prepare("
                    INSERT INTO messages (network, channel, time, type, msg)
                    VALUES (:network, :channel, :time, :type, :msg)
                ");
                $stmt2->execute([
                    'network' => $network_uuid,
                    'channel' => $channel,
                    'time'    => $timestamp_ms,
                    'type'    => $type,
                    'msg'     => $msg_json,
                ]);
                $imported_this_file++;
                $total_imported++;
            } catch (Exception $e) {
                $skipped++;
            }
        }

        echo "✓ Imported: " . number_format($imported_this_file) . " messages\n";
    }

    echo "\n";
}

echo "=====================================\n";
echo "FINAL SUMMARY\n";
echo "=====================================\n";
echo "MAM folders processed: " . count($mam_folders) . "\n";
echo "Total log files: $total_files\n";
echo "Total messages imported: " . number_format($total_imported) . "\n";
echo "Skipped (duplicates/errors): " . number_format($skipped) . "\n";
echo "=====================================\n\n";

echo "Next steps:\n";
echo "1. Run: C:\\xampp\\php\\php.exe C:\\xampp\\htdocs\\backfill-history.php\n";
echo "2. View dashboard: http://localhost/mam-dashboard/\n";
?>
