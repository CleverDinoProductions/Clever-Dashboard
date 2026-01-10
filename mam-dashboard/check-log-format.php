<?php
$network_uuid = 'myanonamouse--4914-9827-2b9e97529b74';
$log_file = "E:\\thelounge\\logs\\DinoDude\\{$network_uuid}\\#an-q.log";

echo "Checking log file format...\n\n";

// Show lines from the gap period (09:38 onwards)
$handle = fopen($log_file, 'r');
$shown = 0;
$target = 10; // Show 10 lines from gap period

while (($line = fgets($handle)) !== false && $shown < $target) {
    if (strpos($line, '2025-12-06T09:3') !== false || 
        strpos($line, '2025-12-06T1') !== false) { // 10:00+ or afternoon
        echo "Line: " . trim($line) . "\n\n";
        $shown++;
    }
}

fclose($handle);

echo "\n=== Sample from today (any time) ===\n";
$handle = fopen($log_file, 'r');
$shown = 0;
while (($line = fgets($handle)) !== false && $shown < 5) {
    if (strpos($line, '2025-12-06') !== false) {
        echo trim($line) . "\n";
        $shown++;
    }
}
fclose($handle);
?>
