<?php
$log_base_path = 'E:\\thelounge\\logs\\DinoDude';

// Find MAM folder
$network_folders = glob($log_base_path . '\\*');
$mam_folder = null;

foreach ($network_folders as $folder) {
    $folder_name = basename($folder);
    if (stripos($folder_name, 'myanonamouse') !== false || 
        stripos($folder_name, '4914-9827') !== false) {
        $mam_folder = $folder;
        break;
    }
}

if (!$mam_folder) {
    die("Could not find MAM folder\n");
}

echo "MAM Folder: $mam_folder\n\n";

// Get #an-q.log for testing
$test_file = $mam_folder . '\\#an-q.log';

if (!file_exists($test_file)) {
    die("File not found: $test_file\n");
}

echo "Reading: $test_file\n";
echo "File size: " . filesize($test_file) . " bytes\n\n";

// Read first 20 lines
$lines = file($test_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
echo "Total lines: " . count($lines) . "\n\n";

echo "=== FIRST 20 LINES ===\n";
foreach (array_slice($lines, 0, 20) as $idx => $line) {
    echo "Line " . ($idx + 1) . ": " . $line . "\n";
}

echo "\n=== LAST 20 LINES ===\n";
foreach (array_slice($lines, -20) as $idx => $line) {
    echo "Line: " . $line . "\n";
}

// Try to parse a few lines
echo "\n=== PARSING TEST ===\n";
foreach (array_slice($lines, 0, 5) as $line) {
    echo "\nOriginal: $line\n";
    
    // Try basic timestamp match
    if (preg_match('/^\[([\d\-\s:]+)\]\s+(.+)$/', $line, $matches)) {
        echo "  ✓ Timestamp matched: {$matches[1]}\n";
        echo "  ✓ Content: {$matches[2]}\n";
        
        // Try to parse date
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $matches[1]);
        if ($dt) {
            echo "  ✓ Date parsed: " . $dt->format('Y-m-d H:i:s') . "\n";
        } else {
            echo "  ✗ Date parsing FAILED\n";
            echo "  ✗ Trying alternate formats...\n";
            
            // Try with timezone
            $dt2 = DateTime::createFromFormat('Y-m-d H:i:s O', $matches[1]);
            if ($dt2) {
                echo "  ✓ Date with timezone parsed: " . $dt2->format('Y-m-d H:i:s') . "\n";
            }
        }
    } else {
        echo "  ✗ Timestamp regex FAILED\n";
    }
}
?>
