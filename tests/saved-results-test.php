<?php
$path = sys_get_temp_dir() . '/clever-results-' . bin2hex(random_bytes(8)) . '.sqlite3';
putenv('CLEVER_ACCOUNTS_DB=' . $path);
require_once __DIR__ . '/../lib/saved-results.php';
function results_check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
try {
    $a = clever_create_user('results_a', 'results-a@example.com', 'test-password-123');
    $b = clever_create_user('results_b', 'results-b@example.com', 'test-password-123');
    $state = ['outcome_overrides'=>'12-h,13-d', 'excluded_results'=>'a14', 'custom_win_points'=>'4'];
    $first = clever_save_results($a, 'Run', '2025-2026', 'premier-league', $state);
    $second = clever_save_results($a, 'Run', '2025-2026', 'premier-league', ['outcome_overrides'=>'12-a']);
    results_check($first !== $second && count(clever_saved_results($a)) === 2, 'Each save must be separate, even with the same name.');
    $loaded = clever_load_results($a, $first, '2025-2026', 'premier-league', ['subtab'=>'team-tracker', 'outcome_overrides'=>'99-a', 'custom_loss_points'=>'2']);
    results_check($loaded['subtab'] === 'team-tracker' && $loaded['outcome_overrides'] === '12-h,13-d' && !isset($loaded['custom_loss_points']) && $loaded['calc_mode'] === 'custom_matches', 'Loading preserves the target tab and replaces old rules.');
    foreach ([[$b, '2025-2026', 'premier-league'], [$a, '2026-2027', 'premier-league'], [$a, '2025-2026', 'championship']] as [$user, $season, $league]) {
        try {
            clever_load_results($user, $first, $season, $league, []);
            throw new RuntimeException('An incompatible save was loaded.');
        } catch (InvalidArgumentException $expected) {}
    }
    clever_delete_results($b, $first);
    results_check(count(clever_saved_results($a)) === 2, 'Another user cannot delete a save.');
    clever_delete_results($a, $first);
    results_check(count(clever_saved_results($a)) === 1, 'Deleting one save keeps other sets.');
    results_check(!isset(clever_result_state(['csrf_token'=>'secret', 'outcome_overrides'=>'12-h'])['csrf_token']), 'Only result parameters may be persisted.');
    echo "Saved results tests passed.\n";
} finally {
    @unlink($path);
}
