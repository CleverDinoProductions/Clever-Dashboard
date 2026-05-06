<?php
// update-data.php: Triggers the fetch-worldfootball.php script in the background and returns status.
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Run the fetch script in the background
$cmd = 'php fetch-englishfootball.php > /dev/null 2>&1 &';
exec($cmd, $output, $resultCode);

if ($resultCode === 0) {
    echo json_encode(['success' => true, 'message' => 'Update started in background.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to start update.']);
}
