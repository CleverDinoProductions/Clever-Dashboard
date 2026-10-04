<?php
// update-data.php: Triggers the fetch-worldfootball.php script in the background and returns status.
require_once dirname(__DIR__) . '/lib/auth.php';
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}
$user = clever_current_user();
if (!$user || empty($user['can_update_data'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Data operator access is required.']);
    exit;
}
clever_verify_csrf();

// Run the fetch script in the background
$cmd = 'php ' . escapeshellarg(__DIR__ . '/fetch-worldfootball.php') . ' > /dev/null 2>&1 &';
exec($cmd, $output, $resultCode);

if ($resultCode === 0) {
    echo json_encode(['success' => true, 'message' => 'Update started in background.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to start update.']);
}
