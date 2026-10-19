<?php
require_once dirname(__DIR__) . '/lib/auth.php';
require_once __DIR__ . '/includes/nations-league.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method Not Allowed']); exit;
}
$user = clever_current_user();
if (!$user || empty($user['can_update_data'])) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Data operator access is required.']); exit;
}
clever_verify_csrf();
$edition = $_POST['tournament'] ?? '';
if (!is_string($edition) || !isset(football_stats_nations_league_editions()[$edition])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Select a valid Nations League tournament.']); exit;
}
$cmd = 'php ' . escapeshellarg(__DIR__.'/fetch-worldfootball.php') . ' --nations-league-only --tournament=' . escapeshellarg($edition) . ' > /dev/null 2>&1 &';
exec($cmd, $output, $resultCode);
echo json_encode(['success'=>$resultCode === 0, 'message'=>$resultCode === 0 ? 'Nations League update started. Reload after it finishes to see refreshed data.' : 'Failed to start update.']);
