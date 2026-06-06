<?php
// TEAM CLASSIC TRACKER (League One) - Data queries
require_once dirname(__DIR__, 3) . '/includes/team-tracker-helpers.php';

$tableView = football_stats_get_table_view_combined($db, 'L1', 'league_table_L1', $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$standings = $tableView['standings'];

$team = football_stats_get_tracker_team($standings);

if (!$team) {
    echo '<div class="panel" style="border:2px solid #f04747">';
    echo '<h2 style="color:#f04747">No Team Data Found</h2>';
    echo '<p>Please check that Championship data has been loaded.</p>';
    $teams = array_column($standings, 'team_name');
    echo '<pre>' . implode("\n", $teams) . '</pre>';
    echo '</div>';
    return;
}

$teamName      = $team['team_name'];
$teamColors    = football_stats_get_team_colors($teamName);
$teamPrimary   = $teamColors['primary'];
$teamSecondary = $teamColors['secondary'];

// Unpack stats
$teamPosition = (int)$team['position'];
$teamPlayed   = (int)$team['played'];
$teamWon      = (int)$team['won'];
$teamDrawn    = (int)$team['drawn'];
$teamLost     = (int)$team['lost'];
$teamGF       = (int)$team['gf'];
$teamGA       = (int)$team['ga'];
$teamGD       = (int)$team['gd'];
$teamPoints   = (int)$team['points'];

// SAFETY CALCULATIONS – flat 38-point target
$safetyTarget   = 46;                              // points needed for safety
$gamesRemaining = 46 - $teamPlayed;
$pointsToSafety = max(0, $safetyTarget - $teamPoints);
$ppgNeeded      = $gamesRemaining > 0
    ? round($pointsToSafety / $gamesRemaining, 2)
    : 0.0;

// Current PPG and projected final points
$currentPPG      = $teamPlayed > 0 ? $teamPoints / $teamPlayed : 0;
$projectedPoints = round($currentPPG * 38, 1);

$bottomSix = array_values(array_filter($standings, function ($teamRow) {
    return (int) $teamRow['position'] >= 15;
}));

$eighteenth = null;
foreach ($standings as $teamRow) {
    if ((int) $teamRow['position'] === 18) {
        $eighteenth = $teamRow;
        break;
    }
}
$gapTo18th  = $teamPoints - (int) ($eighteenth['points'] ?? 0);

// Simple survival status
if ($teamPosition < 17 && $teamPoints >= $safetyTarget) {
    $survivalStatus = 'SAFE';
    $statusColor    = '#43b581';
    $statusIcon     = '✔';
} elseif ($teamPosition <= 17) {
    $survivalStatus = 'ON TRACK';
    $statusColor    = '#faa61a';
    $statusIcon     = '●';
} else {
    $survivalStatus = 'DANGER ZONE';
    $statusColor    = '#f04747';
    $statusIcon     = '⚠';
}
?>

<!-- TEAM CLASSIC SURVIVAL TRACKER VIEW -->

<!-- Hero Section -->
<div class="panel" style="border:3px solid <?= $teamSecondary ?>;
                           background:linear-gradient(135deg,<?= $teamPrimary ?> 0,#2e3136 100%)">
    <div style="text-align:center">
        <h1 style="color:#FFFFFF;font-size:48px;margin:0"><?= htmlspecialchars($teamName) ?></h1>
        <h2 style="color:<?= $teamSecondary ?>;font-size:32px;margin:10px 0">
            Survival Tracker
        </h2>
        <?php football_stats_render_team_selector($standings, $teamName); ?>
        <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', 'league-one', $currentSubTab ?? 'team-tracker-3'); ?>
        <div style="background:<?= $statusColor ?>;
                    display:inline-block;
                    padding:15px 40px;
                    border-radius:8px;
                    margin-top:10px">
            <span style="font-size:28px;font-weight:bold;color:white">
                <?= $statusIcon ?> <?= $survivalStatus ?>
            </span>
        </div>
    </div>
</div>

<!-- Key Stats Grid -->
<div class="grid">

    <!-- Current Position -->
    <div class="panel" style="background:#40444b;border-left:4px solid <?= $statusColor ?>;">
        <h3 style="color:<?= $teamSecondary ?>;font-size:16px;margin-bottom:10px">Current Position</h3>
        <div style="font-size:64px;font-weight:bold;color:<?= $statusColor ?>;text-align:center">
            <?= $teamPosition ?>th
        </div>
        <p style="text-align:center;color:#888;font-size:14px">
            <?= abs($gapTo18th) ?> points
            <?= $gapTo18th >= 0 ? 'above' : 'below' ?> relegation zone
        </p>
    </div>

    <!-- Current Points -->
    <div class="panel" style="background:#40444b;border-left:4px solid #5865F2;">
        <h3 style="color:<?= $teamSecondary ?>;font-size:16px;margin-bottom:10px">Current Points</h3>
        <div style="font-size:64px;font-weight:bold;color:#5865F2;text-align:center">
            <?= $teamPoints ?>
        </div>
        <p style="text-align:center;color:#888;font-size:14px">
            From <?= $teamPlayed ?> games played
        </p>
    </div>

    <!-- Points to Safety -->
    <div class="panel" style="background:#40444b;border-left:4px solid #faa61a;">
        <h3 style="color:<?= $teamSecondary ?>;font-size:16px;margin-bottom:10px">Points to Safety</h3>
        <div style="font-size:64px;font-weight:bold;color:#faa61a;text-align:center">
            <?= $pointsToSafety ?>
        </div>
        <p style="text-align:center;color:#888;font-size:14px">
            Target <?= $safetyTarget ?> points
        </p>
    </div>

    <!-- Games Remaining -->
    <div class="panel" style="background:#40444b;border-left:4px solid #43b581;">
        <h3 style="color:<?= $teamSecondary ?>;font-size:16px;margin-bottom:10px">Games Remaining</h3>
        <div style="font-size:64px;font-weight:bold;color:#43b581;text-align:center">
            <?= $gamesRemaining ?>
        </div>
        <p style="text-align:center;color:#888;font-size:14px">
            PPG needed <?= $ppgNeeded ?>
        </p>
    </div>

</div>

<!-- Progress to Safety -->
<div class="panel">
    <h2>Progress to Safety <?= $safetyTarget ?> Points</h2>
    <div style="background:#40444b;border-radius:8px;height:40px;position:relative;overflow:hidden">
        <?php $progress = $safetyTarget > 0 ? min(100, $teamPoints / $safetyTarget * 100) : 0; ?>
        <div style="background:linear-gradient(90deg,#43b581,<?= $teamSecondary ?>);
                    width:<?= $progress ?>%;
                    height:100%;
                    transition:width 0.5s ease"></div>
        <div style="position:absolute;top:50%;left:50%;
                    transform:translate(-50%,-50%);
                    font-weight:bold;color:white;font-size:18px">
            <?= $teamPoints ?> / <?= $safetyTarget ?> points (<?= round($progress,1) ?>%)
        </div>
    </div>
    <p style="color:#888;font-size:13px;margin-top:10px;text-align:center">
        <?php
        if ($pointsToSafety <= 0) {
            echo 'Safety secured! Survival confirmed!';
        } else {
            echo 'Need ' . $pointsToSafety . ' more points from ' . $gamesRemaining . ' games.';
        }
        ?>
    </p>
</div>

<!-- Season Projection -->
<div class="panel" style="background:#2e3136;">
    <h2>Season Projection</h2>
    <div style="background:#40444b;padding:20px;border-radius:8px;margin-top:15px">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <h3 style="color:#888;font-size:14px;margin:0">Current PPG</h3>
                <div style="font-size:36px;font-weight:bold;color:#5865F2">
                    <?= number_format($currentPPG, 2) ?>
                </div>
            </div>
            <div>
                <h3 style="color:#888;font-size:14px;margin:0">Projected Final Points</h3>
                <div style="font-size:36px;font-weight:bold;
                            color:<?= $projectedPoints >= $safetyTarget ? '#43b581' : '#f04747' ?>">
                    <?= $projectedPoints ?>
                </div>
            </div>
        </div>
        <p style="color:#888;font-size:13px;margin-top:15px;text-align:center">
            <?= $projectedPoints >= $safetyTarget
                ? 'On pace for survival! Keep this form.'
                : 'Need to improve PPG to reach the 38‑point safety line.' ?>
        </p>
    </div>
</div>

<!-- League Position Battle -->
<?php
$_ctx_teams3 = array_values(array_filter($standings, function ($teamRow) use ($teamPosition) {
    $pos = (int)$teamRow['position'];
    return $pos >= max(1, $teamPosition - 3) && $pos <= $teamPosition + 3;
}));
?>
<div class="panel">
    <h2>📍 League Position Battle</h2>
    <p style="color:#888;font-size:13px;margin-bottom:15px">
        <?= htmlspecialchars($teamName) ?> and the teams around them
    </p>
    <table>
        <tr>
            <th>Pos</th>
            <th>Team</th>
            <th>P</th>
            <th>GD</th>
            <th>Pts</th>
            <th>Points Gap</th>
            <th>Projection</th>
        </tr>
        <?php foreach ($_ctx_teams3 as $_ctx_row3): ?>
            <?php
            $_ctx_sel3  = ($_ctx_row3['team_name'] === $teamName);
            $_ctx_gap3  = (int)$_ctx_row3['points'] - $teamPoints;
            $_ctx_ppg3  = $_ctx_row3['played'] > 0 ? $_ctx_row3['points'] / $_ctx_row3['played'] : 0;
            $_ctx_proj3 = round($_ctx_ppg3 * 46, 1);
            ?>
            <tr style="<?= $_ctx_sel3 ? 'background:' . $teamPrimary . ';border:2px solid ' . $teamSecondary . ';' : '' ?>">
                <td><strong><?= (int)$_ctx_row3['position'] ?></strong></td>
                <td style="<?= $_ctx_sel3 ? 'color:' . $teamSecondary . ';font-weight:bold;' : '' ?>">
                    <?= $_ctx_sel3 ? '⭐ ' : '' ?><?= htmlspecialchars($_ctx_row3['team_name']) ?>
                </td>
                <td><?= (int)$_ctx_row3['played'] ?></td>
                <td style="color:<?= (int)$_ctx_row3['gd'] >= 0 ? '#43b581' : '#f04747' ?>">
                    <?= (int)$_ctx_row3['gd'] >= 0 ? '+' . (int)$_ctx_row3['gd'] : (int)$_ctx_row3['gd'] ?>
                </td>
                <td><strong><?= (int)$_ctx_row3['points'] ?></strong></td>
                <td style="color:<?= $_ctx_gap3 <= 0 ? '#43b581' : '#f04747' ?>">
                    <?= $_ctx_gap3 === 0 ? '–' : ($_ctx_gap3 < 0 ? abs($_ctx_gap3) . ' behind' : '+' . $_ctx_gap3 . ' ahead') ?>
                </td>
                <td style="color:<?= $_ctx_proj3 >= 46 ? '#43b581' : '#f04747' ?>">
                    <?= $_ctx_proj3 ?> pts
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Relegation Battle Comparison (Bottom 6) -->
<div class="panel">
    <h2>Relegation Battle – Bottom 6 Comparison</h2>
    <p style="color:#888;font-size:13px;margin-bottom:15px">
        Teams fighting for survival
    </p>
    <table>
        <tr>
            <th>Pos</th>
            <th>Team</th>
            <th>P</th>
            <th>GD</th>
            <th>Pts</th>
            <th>Points Gap</th>
        </tr>
        <?php foreach ($bottomSix as $teamRow): ?>
            <?php
            $isSelected = ($teamRow['team_name'] === $teamName);
            $gap        = (int)$teamRow['points'] - $teamPoints;
            ?>
            <tr style="<?= $isSelected ? 'background:' . $teamPrimary . ';border:2px solid ' . $teamSecondary . ';' : '' ?>">
                <td><strong><?= (int)$teamRow['position'] ?></strong></td>
                <td style="<?= $isSelected ? 'color:' . $teamSecondary . ';font-weight:bold;' : '' ?>">
                    <?= htmlspecialchars($teamRow['team_name']) ?>
                </td>
                <td><?= (int)$teamRow['played'] ?></td>
                <td style="color:<?= (int)$teamRow['gd'] >= 0 ? '#43b581' : '#f04747' ?>">
                    <?= (int)$teamRow['gd'] >= 0 ? '+' . (int)$teamRow['gd'] : (int)$teamRow['gd'] ?>
                </td>
                <td><strong><?= (int)$teamRow['points'] ?></strong></td>
                <td style="color:<?= $gap <= 0 ? '#43b581' : '#f04747' ?>">
                    <?= $gap === 0 ? 'level' : ($gap < 0 ? abs($gap) . ' behind' : $gap . ' ahead') ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Team Stats Summary -->
<div class="grid">

    <div class="panel">
        <h2>Attack</h2>
        <div style="text-align:center">
            <div style="font-size:48px;font-weight:bold;color:#43b581">
                <?= $teamGF ?>
            </div>
            <p style="color:#888;font-size:14px">Goals Scored</p>
            <p style="color:#5865F2;font-size:20px;font-weight:bold">
                <?= $teamPlayed > 0 ? number_format($teamGF / $teamPlayed, 2) : '0.00' ?> per game
            </p>
        </div>
    </div>

    <div class="panel">
        <h2>Defense</h2>
        <div style="text-align:center">
            <div style="font-size:48px;font-weight:bold;color:#f04747">
                <?= $teamGA ?>
            </div>
            <p style="color:#888;font-size:14px">Goals Conceded</p>
            <p style="color:#faa61a;font-size:20px;font-weight:bold">
                <?= $teamPlayed > 0 ? number_format($teamGA / $teamPlayed, 2) : '0.00' ?> per game
            </p>
        </div>
    </div>

    <div class="panel">
        <h2>Form</h2>
        <div style="text-align:center">
            <div style="font-size:36px;font-weight:bold;color:#5865F2">
                W<?= $teamWon ?> D<?= $teamDrawn ?> L<?= $teamLost ?>
            </div>
            <p style="color:#888;font-size:14px">Record This Season</p>
            <p style="color:#43b581;font-size:20px;font-weight:bold">
                <?= $teamPlayed > 0
                    ? round($teamWon / $teamPlayed * 100, 1)
                    : 0 ?>% win rate
            </p>
        </div>
    </div>

</div>

<!-- Motivational Message -->
<div class="panel" style="background:linear-gradient(135deg,<?= $teamPrimary ?>,<?= $teamSecondary ?>);
                          text-align:center;padding:30px">
    <h2 style="color:white;font-size:32px;margin:0">
        <?php
        $tn = strtoupper(htmlspecialchars($teamName));
        if ($teamPosition >= 18) {
            echo "IN THE FIGHT! KEEP GOING $tn!";
        } elseif ($pointsToSafety <= 0) {
            echo "SAFETY SECURED! GREAT WORK $tn!";
        } elseif ($pointsToSafety <= 5) {
            echo "ALMOST THERE! KEEP PUSHING $tn!";
        } elseif ($pointsToSafety <= 10) {
            echo "ON THE RIGHT TRACK! LET'S KEEP IT UP $tn!";
        } elseif ($pointsToSafety <= 15) {
            echo "NEED TO STEP UP, BUT KEEP BELIEVING $tn!";
        } else {
            echo "STRONG POSITION! STAY FOCUSED $tn!";
        }
        ?>
    </h2>
    <p style="color:white;font-size:18px;margin-top:15px">
        <?= $gamesRemaining ?> games to go.
        <?= $pointsToSafety ?> points needed.
        <?php
        if ($pointsToSafety <= 0) {
            echo "Enjoy the safety, but let's finish strong!";
        } elseif ($pointsToSafety <= 5) {
            echo "Every point counts now, let's go for it!";
        } elseif ($pointsToSafety <= 10) {
            echo "We can do this! Keep the faith!";
        } else {
            echo "It's a challenge, but we're behind you all the way!";
        }
        ?>
    </p>
</div>
