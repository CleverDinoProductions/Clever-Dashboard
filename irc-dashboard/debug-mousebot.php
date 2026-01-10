<?php
require_once 'config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== MOUSEBOT MESSAGES FROM TODAY ===\n\n";

$stmt = $db->query("
    SELECT 
        datetime(time/1000, 'unixepoch') as timestamp,
        msg
    FROM messages 
    WHERE channel = '#an-q'
    AND json_extract(msg, '\$.from.nick') = 'MouseBot'
    AND date(time/1000, 'unixepoch') = date('now')
    ORDER BY time DESC 
    LIMIT 10
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $msg_data = json_decode($row['msg'], true);
    $text = $msg_data['text'] ?? 'NO TEXT';
    
    echo "[{$row['timestamp']}]\n";
    echo "Raw: " . $text . "\n";
    echo "Hex: " . bin2hex($text) . "\n";
    echo "---\n\n";
}
