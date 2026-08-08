<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

// ============================================================
// BOOTSTRAP — create lookup tables if missing (wrapped in try/catch
// in case the DB is mounted read-only, e.g. WSL permission issues)
// ============================================================

try {
    $world_cup_db->exec("CREATE TABLE IF NOT EXISTS wc_teams (
        team_name  TEXT PRIMARY KEY,
        pot        INTEGER NOT NULL DEFAULT 2,
        is_host    INTEGER NOT NULL DEFAULT 0,
        flag_emoji TEXT    NOT NULL DEFAULT '🏴',
        group_name TEXT
    )");

    $world_cup_db->exec("INSERT OR IGNORE INTO wc_teams
        (team_name, pot, is_host, flag_emoji, group_name) VALUES
        ('Spain',        1, 0, '🇪🇸', 'Group H'),
        ('France',       1, 0, '🇫🇷', 'Group I'),
        ('England',      1, 0, '🏴󠁧󠁢󠁥󠁮󠁧󠁿', 'Group L'),
        ('Argentina',    1, 0, '🇦🇷', 'Group J'),
        ('Brazil',       1, 0, '🇧🇷', 'Group C'),
        ('Portugal',     1, 0, '🇵🇹', 'Group K'),
        ('Netherlands',  1, 0, '🇳🇱', 'Group F'),
        ('Belgium',      1, 0, '🇧🇪', 'Group G'),
        ('Germany',      1, 0, '🇩🇪', 'Group E'),
        ('United States',          2, 1, '🇺🇸', 'Group D'),
        ('Mexico',       2, 1, '🇲🇽', 'Group A'),
        ('Canada',       2, 1, '🇨🇦', 'Group B')
    ");

    $world_cup_db->exec("CREATE TABLE IF NOT EXISTS wc_bracket_sides (
        group_name   TEXT PRIMARY KEY,
        bracket_side TEXT NOT NULL
    )");

    $world_cup_db->exec("INSERT OR IGNORE INTO wc_bracket_sides
        (group_name, bracket_side) VALUES
        ('Group A', 'golden'), ('Group B', 'golden'), ('Group C', 'golden'),
        ('Group D', 'death'),  ('Group E', 'death'),  ('Group F', 'death'),
        ('Group G', 'death'),  ('Group H', 'death'),  ('Group I', 'death'),
        ('Group J', 'golden'), ('Group K', 'golden'), ('Group L', 'golden')
    ");
} catch (PDOException $e) {
    // DB read-only or locked — non-fatal, page still renders with existing data
    error_log("knockout.php bootstrap write failed (non-fatal): " . $e->getMessage());
}

// ============================================================
// 1. FETCH KNOCKOUT MATCHES
// ============================================================

$knockout_rows = $world_cup_db->query(
    "SELECT * FROM wc_knockout ORDER BY match_id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$bracket_data = [];
foreach ($knockout_rows as $match) {
    $bracket_data[$match['match_id']] = $match;
}

// Pull match IDs per stage in chronological order so the template can map
// them onto bracket positions without hardcoding API-assigned IDs.
$r32_ids   = $world_cup_db->query("SELECT match_id FROM wc_knockout WHERE stage = 'LAST_32'        ORDER BY match_date ASC, match_id ASC")->fetchAll(PDO::FETCH_COLUMN);
$r16_ids   = $world_cup_db->query("SELECT match_id FROM wc_knockout WHERE stage = 'LAST_16'        ORDER BY match_date ASC, match_id ASC")->fetchAll(PDO::FETCH_COLUMN);
$qf_ids    = $world_cup_db->query("SELECT match_id FROM wc_knockout WHERE stage = 'QUARTER_FINALS' ORDER BY match_date ASC, match_id ASC")->fetchAll(PDO::FETCH_COLUMN);
$sf_ids    = $world_cup_db->query("SELECT match_id FROM wc_knockout WHERE stage = 'SEMI_FINALS'    ORDER BY match_date ASC, match_id ASC")->fetchAll(PDO::FETCH_COLUMN);
$tp_ids    = $world_cup_db->query("SELECT match_id FROM wc_knockout WHERE stage = 'THIRD_PLACE'    ORDER BY match_date ASC, match_id ASC")->fetchAll(PDO::FETCH_COLUMN);
$final_ids = $world_cup_db->query("SELECT match_id FROM wc_knockout WHERE stage = 'FINAL'          ORDER BY match_date ASC, match_id ASC")->fetchAll(PDO::FETCH_COLUMN);

// Safe accessor — returns null if the round hasn't been assigned an ID yet
function idAt(array $ids, int $index): ?int
{
    return $ids[$index] ?? null;
}

// ============================================================
// 2. BRACKET ANALYSIS DATA — reflects who is actually still alive
// ============================================================

// Step 1: build a set of every team that's been knocked out.
// A team is eliminated if it appears in a FINISHED knockout match
// (excluding Third Place and the Final, which don't eliminate anyone
// from "still competing for the trophy path" in the sense we track here)
// and didn't have the higher score.
$eliminated = [];

foreach ($bracket_data as $m) {
    if ($m['status'] !== 'FINISHED') {
        continue;
    }
    if ($m['home_score'] === null || $m['away_score'] === null) {
        continue;
    }
    if ($m['home_team'] === 'TBD' || $m['away_team'] === 'TBD') {
        continue;
    }
    if (in_array($m['stage'], ['THIRD_PLACE', 'FINAL'], true)) {
        continue;
    }

    if ($m['home_score'] > $m['away_score']) {
        $eliminated[$m['away_team']] = true;
    } elseif ($m['away_score'] > $m['home_score']) {
        $eliminated[$m['home_team']] = true;
    }
    // A tie after 90 min shouldn't appear as FINISHED in knockout data
    // (penalties resolve it), but if it does, we don't eliminate either
    // team — safer to under-report than wrongly mark someone out.
}

// Step 2: pull pot1/host teams per bracket side and mark survival status
$bracket_sides = $world_cup_db->query("
    SELECT bs.group_name, bs.bracket_side,
           wt.team_name, wt.flag_emoji, wt.pot, wt.is_host
    FROM   wc_bracket_sides bs
    LEFT JOIN wc_teams wt ON wt.group_name = bs.group_name
    WHERE  wt.pot = 1 OR wt.is_host = 1
    ORDER  BY bs.bracket_side, bs.group_name
")->fetchAll(PDO::FETCH_ASSOC);

$deathTeams  = [];
$goldenTeams = [];

foreach ($bracket_sides as $row) {
    $isOut = isset($eliminated[$row['team_name']]);

    $label = htmlspecialchars($row['flag_emoji'] . ' ' . $row['team_name'])
           . ' (' . htmlspecialchars($row['group_name']) . ')'
           . ($row['pot'] == 1    ? ' - Pot 1' : '')
           . ($row['is_host'] == 1 ? ' - Host'  : '');

    if ($isOut) {
        $label = '<s style="opacity:0.5;">' . $label . '</s> <span style="color:#f04747;font-size:11px;">❌ Eliminated</span>';
    } else {
        $label .= ' <span style="color:#43b581;font-size:11px;">✓ Still in</span>';
    }

    if ($row['bracket_side'] === 'death')  { $deathTeams[]  = $label; }
    if ($row['bracket_side'] === 'golden') { $goldenTeams[] = $label; }
}

$deathTotal   = count(array_filter($bracket_sides, fn($r) => $r['bracket_side'] === 'death'));
$goldenTotal  = count(array_filter($bracket_sides, fn($r) => $r['bracket_side'] === 'golden'));
$deathAlive   = count(array_filter($bracket_sides, fn($r) => $r['bracket_side'] === 'death'  && !isset($eliminated[$r['team_name']])));
$goldenAlive  = count(array_filter($bracket_sides, fn($r) => $r['bracket_side'] === 'golden' && !isset($eliminated[$r['team_name']])));

// ============================================================
// 3. RENDER HELPERS
// ============================================================

function renderMatch(?int $matchId, string $defaultHome, string $defaultAway, array $data): array
{
    if ($matchId !== null && isset($data[$matchId])) {
        $m      = $data[$matchId];
        $home   = ($m['home_team'] && $m['home_team'] !== 'TBD') ? $m['home_team'] : $defaultHome;
        $away   = ($m['away_team'] && $m['away_team'] !== 'TBD') ? $m['away_team'] : $defaultAway;
        $hScore = $m['home_score'];
        $aScore = $m['away_score'];
        $status = $m['status'];

        $hClass = $aClass = '';
        if ($status === 'FINISHED' && $hScore !== null && $aScore !== null) {
            if ($hScore > $aScore)     { $hClass = 'winner'; }
            elseif ($aScore > $hScore) { $aClass = 'winner'; }
        }

        $isLive = in_array($status, ['IN_PLAY', 'PAUSED', 'EXTRA_TIME', 'PENALTY_SHOOTOUT'], true);

        return [
            'home'    => htmlspecialchars($home),
            'away'    => htmlspecialchars($away),
            'h_score' => $hScore ?? '',
            'a_score' => $aScore ?? '',
            'h_class' => $hClass,
            'a_class' => $aClass,
            'live'    => $isLive,
            'status'  => $status,
        ];
    }

    return [
        'home'    => htmlspecialchars($defaultHome),
        'away'    => htmlspecialchars($defaultAway),
        'h_score' => '',
        'a_score' => '',
        'h_class' => '',
        'a_class' => '',
        'live'    => false,
        'status'  => '',
    ];
}

function matchCard(?int $matchId, string $defaultHome, string $defaultAway, array $data,
                   string $stageClass, string $label, string $date, string $note = ''): string
{
    $m    = renderMatch($matchId, $defaultHome, $defaultAway, $data);
    $live = $m['live'] ? ' live' : '';

    $noteHtml   = $note ? '<div class="match-note">' . htmlspecialchars($note) . '</div>' : '';
    $hScoreHtml = $m['h_score'] !== '' ? '<span>' . (int)$m['h_score'] . '</span>' : '<span></span>';
    $aScoreHtml = $m['a_score'] !== '' ? '<span>' . (int)$m['a_score'] . '</span>' : '<span></span>';
    $liveTag    = $m['live']
        ? '<span style="color:#f04747;font-weight:700;animation:pulse-live 2s infinite;">● LIVE</span>'
        : '<span>' . htmlspecialchars($date) . '</span>';

    return <<<HTML
        <div class="bracket-match {$stageClass}{$live}">
            <div class="match-header">
                <span>{$label}</span>
                {$liveTag}
            </div>
            <div class="match-team {$m['h_class']}">
                <span>{$m['home']}</span>{$hScoreHtml}
            </div>
            <div class="match-team {$m['a_class']}">
                <span>{$m['away']}</span>{$aScoreHtml}
            </div>
            {$noteHtml}
        </div>
    HTML;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>World Cup 2026 Bracket</title>
    <style>
        body {
            background-color: #202225;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .panel {
            background: #2f3136;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #5865F2;
        }

        h2, h3 { margin-top: 0; color: #fff; }

        .update-info { color: #aaa; font-size: 0.9em; margin-bottom: 15px; }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-right: 5px;
            margin-bottom: 5px;
            color: #fff;
            background: #40444b;
        }
        .badge-champions { background: #FFD700; color: #000; }
        .badge-europa    { background: #00ff88; color: #000; }
        .badge-team      { background: #23272a; color: #dcddde; border: 1px solid #40444b; }

        .bracket-container {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding: 20px;
            background: #23272a;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .bracket-round {
            min-width: 280px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .round-title {
            text-align: center;
            color: #5865F2;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 10px;
            padding: 10px;
            background: #2c2f33;
            border-radius: 6px;
            border-bottom: 3px solid #5865F2;
        }

        .bracket-match {
            background: #2c2f33;
            border-radius: 6px;
            padding: 10px;
            border-left: 3px solid #5865F2;
            transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .bracket-match:hover {
            background: #36393e;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .bracket-match.r16         { border-left-color: #00ff88; }
        .bracket-match.qf          { border-left-color: #faa61a; }
        .bracket-match.sf          { border-left-color: #f04747; }
        .bracket-match.final       { border-left-color: #FFCD00; background: linear-gradient(135deg, #2c2f33 0%, #3a3d42 100%); }
        .bracket-match.third-place { border-left-color: #cd7f32; }

        @keyframes pulse-live {
            0%,100% { border-left-color: #f04747; box-shadow: 0 0 5px rgba(240,71,71,0.5); }
            50%      { border-left-color: #ff8888; box-shadow: 0 0 12px rgba(240,71,71,0.8); }
        }
        .bracket-match.live { animation: pulse-live 2s infinite; }

        .match-header {
            font-size: 11px;
            color: #99aab5;
            margin-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .match-team {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 8px;
            background: #23272a;
            margin-bottom: 4px;
            border-radius: 4px;
            color: #dcddde;
            font-size: 13px;
            font-weight: 500;
        }
        .match-team:last-of-type { margin-bottom: 0; }

        .match-team.winner {
            background: linear-gradient(90deg, #1d428a 0%, #2a5298 100%);
            color: #fff;
            font-weight: 700;
            border-left: 3px solid #FFCD00;
        }
        .final .match-team.winner {
            background: linear-gradient(90deg, #FFCD00 0%, #ffed4a 100%);
            color: #000;
            border-left: none;
        }

        .match-note {
            margin-top: 8px;
            font-size: 11px;
            color: #99aab5;
            font-style: italic;
            text-align: center;
        }

        .bracket-spacer       { height: 20px;  flex-shrink: 0; }
        .bracket-spacer-large { height: 40px;  flex-shrink: 0; }
        .bracket-spacer-xl    { height: 80px;  flex-shrink: 0; }
        .bracket-spacer-mega  { height: 160px; flex-shrink: 0; }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .text-danger  { color: #f04747; }
        .text-success { color: #43b581; }

        ul { padding-left: 20px; margin: 5px 0; font-size: 0.9em; color: #dcddde; }
        li { margin-bottom: 4px; }
    </style>
</head>
<body>

<div class="panel">
    <h2>⚔️ Knockout Stage Bracket</h2>
    <p class="update-info">Tournament bracket — live data from football-data.org</p>
    <div class="bracket-info">
        <span class="badge badge-champions">🏆 Pot 1 Elite</span>
        <span class="badge badge-europa">🌟 Host Nations</span>
        <span class="badge badge-team">Round of 32: Jun 28 – Jul 3</span>
        <span class="badge badge-team">Round of 16: Jul 4–7</span>
        <span class="badge badge-team">Quarterfinals: Jul 9–11</span>
        <span class="badge badge-team">Semifinals: Jul 14–15</span>
        <span class="badge badge-team">Final: Jul 19</span>
    </div>
</div>

<div class="bracket-container">

    <!-- ── Round of 32 ─────────────────────────────────────────────── -->
    <div class="bracket-round">
        <h3 class="round-title">Round of 32</h3>

        <?php foreach ($r32_ids as $i => $mid): ?>
            <?php
                $m = $bracket_data[$mid] ?? null;
                $date = $m ? date('M j', strtotime($m['match_date'])) : '';
            ?>
            <?= matchCard($mid, 'TBD', 'TBD', $bracket_data, 'r32', "Match $mid", $date) ?>
            <?php if (in_array($i, [3, 7, 11], true)): ?>
                <div class="bracket-spacer<?= $i === 7 ? '-large' : '' ?>"></div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty($r32_ids)): ?>
            <p style="color:#99aab5;font-size:13px;">No Round of 32 matches found yet — run the fetch script.</p>
        <?php endif; ?>
    </div>

    <!-- ── Round of 16 ─────────────────────────────────────────────── -->
    <div class="bracket-round">
        <h3 class="round-title">Round of 16</h3>

        <?php foreach ($r16_ids as $i => $mid): ?>
            <?php
                $m = $bracket_data[$mid] ?? null;
                $date = $m ? date('M j', strtotime($m['match_date'])) : '';
            ?>
            <?= matchCard($mid, 'TBD', 'TBD', $bracket_data, 'r16', "Match $mid", $date) ?>
            <?php if (in_array($i, [1, 3, 5], true)): ?>
                <div class="bracket-spacer<?= $i === 3 ? '-large' : '' ?>"></div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty($r16_ids)): ?>
            <p style="color:#99aab5;font-size:13px;">Awaiting Round of 32 results.</p>
        <?php endif; ?>
    </div>

    <!-- ── Quarterfinals ───────────────────────────────────────────── -->
    <div class="bracket-round">
        <h3 class="round-title">Quarterfinals</h3>

        <?php foreach ($qf_ids as $i => $mid): ?>
            <?php
                $m = $bracket_data[$mid] ?? null;
                $date = $m ? date('M j', strtotime($m['match_date'])) : '';
            ?>
            <?= matchCard($mid, 'TBD', 'TBD', $bracket_data, 'qf', "Match $mid", $date) ?>
            <?php if ($i === 0): ?><div class="bracket-spacer"></div><?php endif; ?>
            <?php if ($i === 1): ?><div class="bracket-spacer-xl"></div><?php endif; ?>
            <?php if ($i === 2): ?><div class="bracket-spacer"></div><?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty($qf_ids)): ?>
            <p style="color:#99aab5;font-size:13px;">Awaiting Round of 16 results.</p>
        <?php endif; ?>
    </div>

    <!-- ── Semifinals ──────────────────────────────────────────────── -->
    <div class="bracket-round">
        <h3 class="round-title">Semifinals</h3>

        <?php foreach ($sf_ids as $i => $mid): ?>
            <?php
                $m = $bracket_data[$mid] ?? null;
                $date = $m ? date('M j', strtotime($m['match_date'])) : '';
                $note = $i === 0 ? '🔥 Side of Death Winner' : '✨ Golden Path Winner';
            ?>
            <?= matchCard($mid, 'TBD', 'TBD', $bracket_data, 'sf', "Match $mid", $date, $note) ?>
            <?php if ($i === 0): ?><div class="bracket-spacer-mega"></div><?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty($sf_ids)): ?>
            <p style="color:#99aab5;font-size:13px;">Awaiting Quarterfinal results.</p>
        <?php endif; ?>
    </div>

    <!-- ── Final & Third Place ─────────────────────────────────────── -->
    <div class="bracket-round">
        <h3 class="round-title">Final</h3>

        <?php $finalMid = idAt($final_ids, 0); ?>
        <?php $finalM   = $finalMid ? ($bracket_data[$finalMid] ?? null) : null; ?>
        <?php $finalLabel = $finalM
            ? '🏆 Match ' . $finalMid . ' — ' . date('F j', strtotime($finalM['match_date'])) . ', ' . ($finalM['venue'] ?? 'TBD')
            : '🏆 Final'; ?>
        <?= matchCard($finalMid, 'Winner SF 1', 'Winner SF 2', $bracket_data, 'final', $finalLabel, '', 'FIFA World Cup 2026 Champion') ?>

        <div class="bracket-spacer"></div>

        <?php $tpMid = idAt($tp_ids, 0); ?>
        <?php $tpM   = $tpMid ? ($bracket_data[$tpMid] ?? null) : null; ?>
        <?php $tpLabel = $tpM
            ? '🥉 Match ' . $tpMid . ' — ' . date('F j', strtotime($tpM['match_date'])) . ', ' . ($tpM['venue'] ?? 'TBD')
            : '🥉 Third Place'; ?>
        <?= matchCard($tpMid, 'Loser SF 1', 'Loser SF 2', $bracket_data, 'third-place', $tpLabel, '', 'Third Place Playoff') ?>
    </div>

</div>

<!-- ── Bracket Analysis — now shows who's actually still alive ──────── -->
<div class="panel">
    <h2>📊 Bracket Structure Analysis</h2>
    <p class="update-info">Pot 1 / host teams on each side of the draw, with live elimination status</p>
    <div class="grid">
        <div class="panel" style="background: #23272a; margin-bottom: 0;">
            <h3 style="color: #f04747;">🔥 Side of Death (Semi-Final 1)</h3>
            <p><strong>Groups D, E, F, G, H, I</strong></p>
            <ul>
                <?php foreach ($deathTeams as $t): ?>
                    <li><?= $t ?></li>
                <?php endforeach; ?>
                <?php if (empty($deathTeams)): ?>
                    <li><em>No elite/host teams found — check wc_teams data</em></li>
                <?php endif; ?>
            </ul>
            <p class="text-danger"><strong><?= $deathAlive ?> of <?= $deathTotal ?> Pot 1 / host teams still standing</strong></p>
        </div>

        <div class="panel" style="background: #23272a; margin-bottom: 0;">
            <h3 style="color: #43b581;">✨ Golden Path (Semi-Final 2)</h3>
            <p><strong>Groups A, B, C, J, K, L</strong></p>
            <ul>
                <?php foreach ($goldenTeams as $t): ?>
                    <li><?= $t ?></li>
                <?php endforeach; ?>
                <?php if (empty($goldenTeams)): ?>
                    <li><em>No elite/host teams found — check wc_teams data</em></li>
                <?php endif; ?>
            </ul>
            <p class="text-success"><strong><?= $goldenAlive ?> of <?= $goldenTotal ?> Pot 1 / host teams still standing</strong></p>
        </div>
    </div>
</div>

</body>
</html>