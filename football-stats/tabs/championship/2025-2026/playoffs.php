<?php
// Championship Playoffs Tab — shows only playoff matches (matchweek > 46)

$playoffMwThreshold = 46;
$competitionCode = 'ELC';
$leagueName = 'Championship';

$allSeasons_stmt = $db->prepare("SELECT DISTINCT season_label FROM matches WHERE competition_code = ? ORDER BY season_label DESC");
$allSeasons_stmt->execute([$competitionCode]);
$allSeasons = $allSeasons_stmt->fetchAll(PDO::FETCH_COLUMN);

$defaultSeason = !empty($allSeasons) ? $allSeasons[0] : '2025-2026';
$selectedSeason = isset($_GET['snapshot_season']) && $_GET['snapshot_season'] !== ''
    ? preg_replace('/[^0-9\-]/', '', (string)$_GET['snapshot_season'])
    : $defaultSeason;
if (!in_array($selectedSeason, $allSeasons, true)) {
    $selectedSeason = $defaultSeason;
}

$stmt = $db->prepare("SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek > ? ORDER BY matchweek ASC, match_date ASC, id ASC");
$stmt->execute([$competitionCode, $selectedSeason, $playoffMwThreshold]);
$playoffMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$seasonDisplay = $seasonLabels[$selectedSeason] ?? $selectedSeason;

// Group matches by matchweek to label rounds
$matchesByRound = [];
foreach ($playoffMatches as $m) {
    $matchesByRound[(int)$m['matchweek']][] = $m;
}
$roundMws = array_keys($matchesByRound);
$maxRoundMw = !empty($roundMws) ? max($roundMws) : null;
?>

<div class="panel" style="border-left: 4px solid #5865F2;">
    <h2>🎟️ <?= htmlspecialchars($leagueName) ?> Playoffs <?= htmlspecialchars($seasonDisplay) ?></h2>
    <p style="color: #aaa; margin: 10px 0 16px;">
        Playoff matches are not included in the regular season table. Regular season ends at matchweek <?= $playoffMwThreshold ?>.
    </p>

    <?php if (count($allSeasons) > 1): ?>
    <div style="margin-bottom: 16px;">
        <select onchange="location.href=this.value" style="background:#2f3136;color:#dcddde;border:1px solid #444;padding:6px 10px;border-radius:6px;font-size:13px;">
            <?php foreach ($allSeasons as $s):
                $params = array_merge($_GET, ['snapshot_season' => $s]);
                $url = '?' . http_build_query($params);
            ?>
            <option value="<?= htmlspecialchars($url) ?>" <?= $s === $selectedSeason ? 'selected' : '' ?>>
                <?= htmlspecialchars($seasonLabels[$s] ?? $s) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if (empty($playoffMatches)): ?>
    <div style="text-align:center;padding:50px 20px;color:#666;">
        <div style="font-size:48px;margin-bottom:16px;">🏆</div>
        <p style="font-size:18px;color:#888;">No playoff matches yet for <?= htmlspecialchars($seasonDisplay) ?></p>
        <p style="margin-top:10px;font-size:14px;color:#555;">Playoffs begin after the regular season (MW <?= $playoffMwThreshold ?>) concludes.</p>
    </div>
    <?php else: ?>

    <?php foreach ($matchesByRound as $mw => $roundMatches):
        $roundOffset = $mw - $playoffMwThreshold;
        $isFinal = ($mw === $maxRoundMw && count($roundMatches) === 1);
        if ($isFinal) {
            $roundLabel = 'Final';
            $roundIcon = '🏆';
            $borderColor = '#FFD700';
        } elseif ($roundOffset <= 2) {
            $roundLabel = 'Semi-Finals';
            $roundIcon = '⚔️';
            $borderColor = '#5865F2';
        } else {
            $roundLabel = 'Round ' . $roundOffset;
            $roundIcon = '🎟️';
            $borderColor = '#5865F2';
        }
    ?>
    <div style="margin-bottom:24px;">
        <h3 style="color:<?= $borderColor ?>;margin-bottom:12px;font-size:15px;display:flex;align-items:center;gap:8px;">
            <span><?= $roundIcon ?></span>
            <span><?= htmlspecialchars($roundLabel) ?></span>
            <span style="font-size:12px;color:#666;font-weight:normal;">(MW<?= $mw ?>)</span>
        </h3>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <thead>
                <tr style="background:#222;color:#aaa;">
                    <th style="padding:8px 12px;text-align:left;border-bottom:1px solid #333;">Date</th>
                    <th style="padding:8px 12px;text-align:right;border-bottom:1px solid #333;">Home</th>
                    <th style="padding:8px 12px;text-align:center;border-bottom:1px solid #333;">Score</th>
                    <th style="padding:8px 12px;text-align:left;border-bottom:1px solid #333;">Away</th>
                    <th style="padding:8px 12px;text-align:center;border-bottom:1px solid #333;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($roundMatches as $m):
                $played = is_numeric($m['home_goals']) && is_numeric($m['away_goals']);
                $score = $played ? ($m['home_goals'] . ' - ' . $m['away_goals']) : 'vs';
                $scoreColor = $played ? '#00ff88' : '#666';
                if ($played) {
                    if ($m['home_goals'] > $m['away_goals']) { $homeStyle = 'font-weight:bold;color:#dcddde;'; $awayStyle = 'color:#888;'; }
                    elseif ($m['away_goals'] > $m['home_goals']) { $homeStyle = 'color:#888;'; $awayStyle = 'font-weight:bold;color:#dcddde;'; }
                    else { $homeStyle = 'color:#dcddde;'; $awayStyle = 'color:#dcddde;'; }
                } else {
                    $homeStyle = $awayStyle = 'color:#dcddde;';
                }
            ?>
                <tr style="border-bottom:1px solid #2a2a2a;">
                    <td style="padding:10px 12px;color:#888;font-size:13px;"><?= htmlspecialchars($m['match_date']) ?></td>
                    <td style="padding:10px 12px;text-align:right;<?= $homeStyle ?>"><?= htmlspecialchars($m['home_team']) ?></td>
                    <td style="padding:10px 12px;text-align:center;font-weight:bold;font-size:15px;color:<?= $scoreColor ?>;letter-spacing:2px;"><?= $score ?></td>
                    <td style="padding:10px 12px;<?= $awayStyle ?>"><?= htmlspecialchars($m['away_team']) ?></td>
                    <td style="padding:10px 12px;text-align:center;font-size:12px;color:#666;"><?= $played ? 'FT' : 'Scheduled' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>
</div>
