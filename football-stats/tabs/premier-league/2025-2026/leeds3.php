<?php
// LEEDS UNITED SURVIVAL TRACKER - Data queries

// Ensure $db exists from your main app (adjust path/filename as needed)
// require_once '../C-stats.php';  // uncomment and point to your DB bootstrap if needed

$tableView = football_stats_get_table_view_combined($db, 'PL', 'league_table_PL', $currentMainTab ?? '2025-2026');
$calcMode = $tableView['calc_mode'];
$standings = $tableView['standings'];
$leeds = null;
foreach ($standings as $teamRow) {
    if ($teamRow['team_name'] === 'Leeds United' || stripos($teamRow['team_name'], 'Leeds') !== false) {
        $leeds = $teamRow;
        break;
    }
}

// Safety check – bail out with a message if Leeds not found
if (!$leeds) {
    echo '<div class="panel" style="border:2px solid #f04747">';
    echo '<h2 style="color:#f04747">Leeds United Not Found in Database</h2>';
    echo '<p>Please check that Leeds United data has been loaded.</p>';

    // Show available teams to help debug
    $teams = array_column($standings, 'team_name');
    echo '<h3>Available teams in database</h3>';
    echo '<pre>' . implode("\n", $teams) . '</pre>';
    echo '</div>';
    return;
}

// Unpack stats
$leedsPosition = (int)$leeds['position'];
$leedsPlayed   = (int)$leeds['played'];
$leedsWon      = (int)$leeds['won'];
$leedsDrawn    = (int)$leeds['drawn'];
$leedsLost     = (int)$leeds['lost'];
$leedsGF       = (int)$leeds['gf'];
$leedsGA       = (int)$leeds['ga'];
$leedsGD       = (int)$leeds['gd'];
$leedsPoints   = (int)$leeds['points'];

// SAFETY CALCULATIONS – flat 38-point target
$safetyTarget   = 38;                              // points needed for safety
$gamesRemaining = 38 - $leedsPlayed;
$pointsToSafety = max(0, $safetyTarget - $leedsPoints);
$ppgNeeded      = $gamesRemaining > 0
    ? round($pointsToSafety / $gamesRemaining, 2)
    : 0.0;

// Current PPG and projected final points
$currentPPG      = $leedsPlayed > 0 ? $leedsPoints / $leedsPlayed : 0;
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
$gapTo18th  = $leedsPoints - (int) ($eighteenth['points'] ?? 0);

// Simple survival status
if ($leedsPosition < 17 && $leedsPoints >= $safetyTarget) {
    $survivalStatus = 'SAFE';
    $statusColor    = '#43b581';
    $statusIcon     = '✔';
} elseif ($leedsPosition <= 17) {
    $survivalStatus = 'ON TRACK';
    $statusColor    = '#faa61a';
    $statusIcon     = '●';
} else {
    $survivalStatus = 'DANGER ZONE';
    $statusColor    = '#f04747';
    $statusIcon     = '⚠';
}
?>

<!-- LEEDS UNITED SURVIVAL TRACKER VIEW -->

<!-- Hero Section -->
<div class="panel" style="border:3px solid #FFCD00;
                           background:linear-gradient(135deg,#1D428A 0,#2e3136 100%)">
    <div style="text-align:center">
        <h1 style="color:#FFFFFF;font-size:48px;margin:0">LEEDS UNITED</h1>
        <h2 style="color:#FFCD00;font-size:32px;margin:10px 0">
            Survival Tracker 2025/26
        </h2>
        <?php football_stats_render_combined_table_controls($tableView, $currentMainTab ?? '2025-2026', $currentLeague ?? 'premier-league', $currentSubTab ?? 'leeds-3'); ?>
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
        <h3 style="color:#FFCD00;font-size:16px;margin-bottom:10px">Current Position</h3>
        <div style="font-size:64px;font-weight:bold;color:<?= $statusColor ?>;text-align:center">
            <?= $leedsPosition ?>th
        </div>
        <p style="text-align:center;color:#888;font-size:14px">
            <?= abs($gapTo18th) ?> points
            <?= $gapTo18th >= 0 ? 'above' : 'below' ?> relegation zone
        </p>
    </div>

    <!-- Current Points -->
    <div class="panel" style="background:#40444b;border-left:4px solid #5865F2;">
        <h3 style="color:#FFCD00;font-size:16px;margin-bottom:10px">Current Points</h3>
        <div style="font-size:64px;font-weight:bold;color:#5865F2;text-align:center">
            <?= $leedsPoints ?>
        </div>
        <p style="text-align:center;color:#888;font-size:14px">
            From <?= $leedsPlayed ?> games played
        </p>
    </div>

    <!-- Points to Safety -->
    <div class="panel" style="background:#40444b;border-left:4px solid #faa61a;">
        <h3 style="color:#FFCD00;font-size:16px;margin-bottom:10px">Points to Safety</h3>
        <div style="font-size:64px;font-weight:bold;color:#faa61a;text-align:center">
            <?= $pointsToSafety ?>
        </div>
        <p style="text-align:center;color:#888;font-size:14px">
            Target <?= $safetyTarget ?> points
        </p>
    </div>

    <!-- Games Remaining -->
    <div class="panel" style="background:#40444b;border-left:4px solid #43b581;">
        <h3 style="color:#FFCD00;font-size:16px;margin-bottom:10px">Games Remaining</h3>
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
        <?php $progress = $safetyTarget > 0 ? min(100, $leedsPoints / $safetyTarget * 100) : 0; ?>
        <div style="background:linear-gradient(90deg,#43b581,#FFCD00);
                    width:<?= $progress ?>%;
                    height:100%;
                    transition:width 0.5s ease"></div>
        <div style="position:absolute;top:50%;left:50%;
                    transform:translate(-50%,-50%);
                    font-weight:bold;color:white;font-size:18px">
            <?= $leedsPoints ?> / <?= $safetyTarget ?> points (<?= round($progress,1) ?>%)
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
            <th>Gap to Leeds</th>
        </tr>
        <?php foreach ($bottomSix as $team): ?>
            <?php
            $isLeeds = ($team['team_name'] === 'Leeds United');
            $gap     = (int)$team['points'] - $leedsPoints;
            ?>
            <tr style="<?= $isLeeds ? 'background:#1D428A;border:2px solid #FFCD00;' : '' ?>">
                <td><strong><?= (int)$team['position'] ?></strong></td>
                <td style="<?= $isLeeds ? 'color:#FFCD00;font-weight:bold;' : '' ?>">
                    <?= htmlspecialchars($team['team_name']) ?>
                </td>
                <td><?= (int)$team['played'] ?></td>
                <td style="color:<?= (int)$team['gd'] >= 0 ? '#43b581' : '#f04747' ?>">
                    <?= (int)$team['gd'] >= 0 ? '+' . (int)$team['gd'] : (int)$team['gd'] ?>
                </td>
                <td><strong><?= (int)$team['points'] ?></strong></td>
                <td style="color:<?= $gap <= 0 ? '#43b581' : '#f04747' ?>">
                    <?= $gap === 0 ? 'level' : ($gap < 0 ? abs($gap) . ' behind Leeds' : $gap . ' ahead of Leeds') ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Leeds Stats Summary -->
<div class="grid">

    <div class="panel">
        <h2>Attack</h2>
        <div style="text-align:center">
            <div style="font-size:48px;font-weight:bold;color:#43b581">
                <?= $leedsGF ?>
            </div>
            <p style="color:#888;font-size:14px">Goals Scored</p>
            <p style="color:#5865F2;font-size:20px;font-weight:bold">
                <?= $leedsPlayed > 0 ? number_format($leedsGF / $leedsPlayed, 2) : '0.00' ?> per game
            </p>
        </div>
    </div>

    <div class="panel">
        <h2>Defense</h2>
        <div style="text-align:center">
            <div style="font-size:48px;font-weight:bold;color:#f04747">
                <?= $leedsGA ?>
            </div>
            <p style="color:#888;font-size:14px">Goals Conceded</p>
            <p style="color:#faa61a;font-size:20px;font-weight:bold">
                <?= $leedsPlayed > 0 ? number_format($leedsGA / $leedsPlayed, 2) : '0.00' ?> per game
            </p>
        </div>
    </div>

    <div class="panel">
        <h2>Form</h2>
        <div style="text-align:center">
            <div style="font-size:36px;font-weight:bold;color:#5865F2">
                W<?= $leedsWon ?> D<?= $leedsDrawn ?> L<?= $leedsLost ?>
            </div>
            <p style="color:#888;font-size:14px">Record This Season</p>
            <p style="color:#43b581;font-size:20px;font-weight:bold">
                <?= $leedsPlayed > 0
                    ? round($leedsWon / $leedsPlayed * 100, 1)
                    : 0 ?>% win rate
            </p>
        </div>
    </div>

</div>

<!-- Motivational Message -->
<div class="panel" style="background:linear-gradient(135deg,#1D428A,#FFCD00);
                          text-align:center;padding:30px">
    <h2 style="color:white;font-size:32px;margin:0">
        <?php
        if ($leedsPosition >= 18) {
            echo "WE'RE IN THE FIGHT! MARCHING ON TOGETHER!";
        } elseif ($pointsToSafety <= 0) {
            echo "SAFETY SECURED! CELEBRATE THE SURVIVAL!";
        } elseif ($pointsToSafety <= 5) {
            echo "ALMOST THERE! KEEP PUSHING LEEDS!";
        } elseif ($pointsToSafety <= 10) {
            echo "ON THE RIGHT TRACK! LET'S KEEP IT UP LEEDS!";
        } elseif ($pointsToSafety <= 15) {
            echo "NEED TO STEP UP, BUT WE BELIEVE IN YOU LEEDS!";
        } else {
            echo "STRONG POSITION! STAY FOCUSED LEEDS!";
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
