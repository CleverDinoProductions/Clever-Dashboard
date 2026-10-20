<?php
// Exercise the shared panel without a database or live standings.
function render_tracker_progress($played, $points, $total = 38, $scoring = null)
{
    $trackerScoring = $scoring;
    $games_played = $played;
    $trackerTotalGames = $total;
    $halfway_point = (int)($total / 2);
    $team = ['points' => $points];
    $full_season_safety_target = 38;
    $extended_season_safety_target = 40;
    $guaranteed_season_safety_target = 42;
    $mathematical_season_safety_target = 45;
    $top_half_season_safety_target = 50;
    $european_season_safety_target = 60;
    $title_season_safety_target = 70;
    ob_start();
    include __DIR__ . '/../includes/team-tracker-progress.php';
    return ob_get_clean();
}
function expect_progress($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
}
set_error_handler(function ($severity, $message) { throw new RuntimeException($message); });
$html = render_tracker_progress(0, 0);
expect_progress(strpos($html, 'Season not started') !== false, 'Unstarted season label');
expect_progress(strpos($html, 'No games played yet') !== false, 'No fabricated pace before first game');
$html = render_tracker_progress(19, 25);
expect_progress(strpos($html, 'Halfway reached') !== false, 'Exact halfway label');
expect_progress(strpos($html, '50.0') !== false, 'Projection at halfway');
expect_progress(strpos($html, 'On current pace') !== false, 'Achievable targets on pace');
expect_progress(strpos($html, 'Higher pace needed') !== false, 'Higher targets need improvement');
$html = render_tracker_progress(37, 37);
expect_progress(strpos($html, 'Out of reach') !== false, 'Impossible targets marked');
expect_progress(preg_match('/<th scope="row">38 pts<\/th><td>1<\/td><td>1.00<\/td>/', $html) === 1, 'Unreached target uses the remaining-game pace');
$html = render_tracker_progress(38, 40);
expect_progress(strpos($html, 'Season complete') !== false, 'Completed season label');
expect_progress(strpos($html, 'Target reached') !== false, 'Reached target');
expect_progress(strpos($html, 'Not reached') !== false, 'Missed end-of-season target');
$html = render_tracker_progress(50, -3, 46);
expect_progress(strpos($html, 'width:100%') !== false, 'Overrun progress clamped');
expect_progress(strpos($html, 'aria-valuenow="46"') !== false, 'Accessible progress clamped');
expect_progress(strpos($html, '<strong>-3</strong>') !== false, 'Negative points retained for deductions');
$html = render_tracker_progress(23, 30, 46);
expect_progress(strpos($html, 'Halfway reached') !== false, '46-game halfway supported');
expect_progress(strpos($html, 'Finish · 46') !== false, '46-game finish marker');
$html = render_tracker_progress(37, 37, 38, ['win' => 2, 'draw' => 1, 'loss' => 0]);
expect_progress(strpos($html, '<strong>39</strong>') !== false, 'Maximum points use configured scoring');
expect_progress(strpos($html, 'highest available result score (2 points per game)') !== false, 'Scoring explanation matches calculator');
$html = render_tracker_progress(1, -3);
expect_progress(strpos($html, '<strong>-3.0</strong>') !== false, 'Projection respects possible remaining results after deductions');
echo "Team tracker progress tests passed.\n";
