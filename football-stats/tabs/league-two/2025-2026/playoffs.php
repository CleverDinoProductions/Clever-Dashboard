<?php
// League Two Playoffs Tab — bracket format, two-legged semi-finals + final

$competitionCode = 'L2';
$leagueName = 'League Two';
$regularSeasonMw = 46;

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

// Playoffs are stored at MW47+ (intRound=0 from TheSportsDB is remapped to MW47+ during sync)
$stmt = $db->prepare("SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek > ? ORDER BY match_date ASC, id ASC");
$stmt->execute([$competitionCode, $selectedSeason, $regularSeasonMw]);
$playoffMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$seasonDisplay = $seasonLabels[$selectedSeason] ?? $selectedSeason;

// --- Group matches by normalised team pair ---
// Cancelled/postponed fixtures (e.g. rearranged legs) are flagged but kept for display
$teamPairs = [];
foreach ($playoffMatches as $m) {
    $teams = [$m['home_team'], $m['away_team']];
    sort($teams);
    $key = $teams[0] . '|||' . $teams[1];
    if (!isset($teamPairs[$key])) {
        $teamPairs[$key] = ['team_a' => $teams[0], 'team_b' => $teams[1], 'legs' => [], 'active_legs' => []];
    }
    $teamPairs[$key]['legs'][] = $m;
    // Active legs exclude cancelled/postponed entries
    $s = strtolower(trim($m['status'] ?? ''));
    if ($s !== 'postponed' && $s !== 'cancelled') {
        $teamPairs[$key]['active_legs'][] = $m;
    }
}

foreach ($teamPairs as &$pair) {
    usort($pair['legs'], fn($a, $b) => strcmp($a['match_date'], $b['match_date']));
    usort($pair['active_legs'], fn($a, $b) => strcmp($a['match_date'], $b['match_date']));
}
unset($pair);

// Remove pairs that have no active legs at all (fully cancelled with no replacement yet)
$teamPairs = array_filter($teamPairs, fn($p) => count($p['active_legs']) > 0);

// Sort pairs by first active leg date
uasort($teamPairs, fn($a, $b) => strcmp($a['active_legs'][0]['match_date'], $b['active_legs'][0]['match_date']));
$pairsArr = array_values($teamPairs);

// Pairs with 2+ active legs = semi-finals; pair with 1 active leg = final
$semiFinals = [];
$finalPair = null;
foreach ($pairsArr as $pair) {
    if (count($pair['active_legs']) >= 2) {
        $semiFinals[] = $pair;
    } else {
        $finalPair = $pair;
    }
}

// --- Helpers ---
function po_score($m) {
    if (!is_numeric($m['home_goals']) || !is_numeric($m['away_goals'])) return null;
    return [(int)$m['home_goals'], (int)$m['away_goals']];
}

function po_aggregate($pair) {
    $ag = ['team_a' => 0, 'team_b' => 0, 'legs_played' => 0];
    foreach ($pair['active_legs'] as $leg) {
        $sc = po_score($leg);
        if ($sc === null) continue;
        $ag['legs_played']++;
        if ($leg['home_team'] === $pair['team_a']) {
            $ag['team_a'] += $sc[0]; $ag['team_b'] += $sc[1];
        } else {
            $ag['team_b'] += $sc[0]; $ag['team_a'] += $sc[1];
        }
    }
    if ($ag['team_a'] > $ag['team_b']) $ag['winner'] = 'a';
    elseif ($ag['team_b'] > $ag['team_a']) $ag['winner'] = 'b';
    else $ag['winner'] = null;
    return $ag;
}

// Determine SF winners for "advances to final" labels
$sfWinners = [];
foreach ($semiFinals as $sf) {
    $agg = po_aggregate($sf);
    if ($agg['winner'] === 'a') $sfWinners[] = $sf['team_a'];
    elseif ($agg['winner'] === 'b') $sfWinners[] = $sf['team_b'];
    else $sfWinners[] = null;
}
?>

<div class="panel" style="border-left: 4px solid #5865F2;">
    <h2>🎟️ <?= htmlspecialchars($leagueName) ?> Playoffs <?= htmlspecialchars($seasonDisplay) ?></h2>
    <p style="color:#aaa;margin:6px 0 16px;font-size:13px;">
        Playoff matches not included in regular season table. Regular season ends at matchweek <?= $regularSeasonMw ?>.
    </p>

    <?php if (count($allSeasons) > 1): ?>
    <div style="margin-bottom:16px;">
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
        <p style="margin-top:10px;font-size:14px;color:#555;">Playoffs begin after the regular season concludes (MW <?= $regularSeasonMw ?>).</p>
    </div>
    <?php else: ?>

    <style>
        .po-bracket { display:flex; gap:0; align-items:stretch; overflow-x:auto; }
        .po-col { display:flex; flex-direction:column; min-width:260px; }
        .po-col-sf { gap:8px; }
        .po-col-connector { min-width:40px; display:flex; flex-direction:column; }
        .po-col-final { min-width:260px; justify-content:center; }

        .po-round-title {
            font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px;
            color:#aaa; padding:6px 0 10px; text-align:center;
        }

        .po-match {
            background:#23272a; border-radius:6px; padding:10px 12px;
            border-left:3px solid #5865F2;
        }
        .po-match.final-match { border-left-color:#FFD700; background:linear-gradient(135deg,#23272a 0%,#2c2f33 100%); }
        .po-match.agg-card  { border-left-color:#43b581; background:#1e2124; }

        .po-match-header {
            font-size:10px; color:#666; font-weight:600; text-transform:uppercase;
            letter-spacing:.5px; margin-bottom:8px; display:flex; justify-content:space-between;
        }
        .po-team {
            display:flex; justify-content:space-between; align-items:center;
            padding:5px 8px; border-radius:4px; font-size:13px; color:#dcddde;
            background:#2c2f33; margin-bottom:3px;
        }
        .po-team:last-of-type { margin-bottom:0; }
        .po-team.winner { background:linear-gradient(90deg,#1d428a,#2a5298); color:#fff; font-weight:700; }
        .po-team.winner-gold { background:linear-gradient(90deg,#b8860b,#ffd700); color:#000; font-weight:700; }
        .po-score { font-weight:700; font-size:14px; min-width:28px; text-align:right; }
        .po-score.live { color:#00ff88; }

        .po-pens { font-size:11px; color:#aaa; text-align:center; margin-top:6px; font-style:italic; }

        .po-agg-section { display:flex; flex-direction:column; gap:8px; flex:1; justify-content:space-around; }

        /* connector lines */
        .po-conn-half {
            flex:1; border-right:2px solid #444; border-radius:0 4px 4px 0;
        }
        .po-conn-half.top { border-bottom:2px solid #444; border-top:0; border-right:2px solid #444; }
        .po-conn-half.bot { border-top:2px solid #444; border-bottom:0; border-right:2px solid #444; }
        .po-conn-arrow { width:40px; display:flex; align-items:center; justify-content:center; color:#444; font-size:14px; }

        .po-sf-group { display:flex; flex-direction:column; gap:4px; flex:1; }
        .po-sf-label { font-size:10px; color:#666; text-transform:uppercase; letter-spacing:.5px; padding:4px 0 6px; font-weight:600; }

        @media (max-width: 700px) {
            .po-bracket { flex-direction:column; }
            .po-col-connector, .po-conn-half { display:none; }
        }
    </style>

    <?php if (!empty($semiFinals)): ?>

    <?php
    // Build a unified 4-team bracket: SFs → Final
    // Layout: [SF Legs] [connector] [Aggregates] [connector] [Final]
    ?>

    <div style="margin-bottom:8px;">
        <div class="po-round-title" style="display:flex;gap:0;">
            <div style="min-width:260px;text-align:center;">Semi-Finals</div>
            <div style="min-width:40px;"></div>
            <div style="min-width:260px;text-align:center;">Aggregate</div>
            <div style="min-width:40px;"></div>
            <div style="min-width:260px;text-align:center;">Final</div>
        </div>
    </div>

    <div class="po-bracket">

        <!-- Column 1: SF legs -->
        <div class="po-col po-col-sf">

            <?php foreach ($semiFinals as $sfIdx => $sf): ?>

            <?php if ($sfIdx > 0): ?>
            <div style="height:20px;"></div>
            <?php endif; ?>

            <div class="po-sf-group">
                <div class="po-sf-label">Semi-Final <?= $sfIdx + 1 ?></div>

                <?php foreach ([0, 1] as $legNum):
                    $leg = $sf['active_legs'][$legNum] ?? null;
                    $sc = $leg ? po_score($leg) : null;
                    $played = $sc !== null;
                    if ($played) {
                        $hWin = $sc[0] > $sc[1];
                        $aWin = $sc[1] > $sc[0];
                    }
                ?>
                <div class="po-match">
                    <div class="po-match-header">
                        <span>Leg <?= $legNum + 1 ?></span>
                        <span><?= $leg ? htmlspecialchars($leg['match_date']) : '–' ?></span>
                    </div>
                    <?php if ($leg): ?>
                    <div class="po-team <?= $played && $hWin ? 'winner' : '' ?>">
                        <span><?= htmlspecialchars($leg['home_team']) ?></span>
                        <span class="po-score"><?= $played ? $sc[0] : '–' ?></span>
                    </div>
                    <div class="po-team <?= $played && $aWin ? 'winner' : '' ?>">
                        <span><?= htmlspecialchars($leg['away_team']) ?></span>
                        <span class="po-score"><?= $played ? $sc[1] : '–' ?></span>
                    </div>
                    <?php else: ?>
                    <div class="po-team"><span style="color:#555;">TBD</span><span class="po-score" style="color:#555;">–</span></div>
                    <div class="po-team"><span style="color:#555;">TBD</span><span class="po-score" style="color:#555;">–</span></div>
                    <?php endif; ?>
                </div>

                <?php endforeach; ?>
            </div>

            <?php endforeach; ?>

        </div><!-- /SF legs col -->

        <!-- Connector: SF legs → Agg -->
        <div class="po-col po-col-connector">
            <?php foreach ($semiFinals as $sfIdx => $sf): ?>
            <?php if ($sfIdx > 0): ?><div style="height:20px;"></div><?php endif; ?>
            <div style="flex:1;display:flex;flex-direction:column;">
                <div class="po-conn-half top"></div>
                <div class="po-conn-arrow">›</div>
                <div class="po-conn-half bot"></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Column 2: Aggregates -->
        <div class="po-col" style="gap:8px;">

            <?php foreach ($semiFinals as $sfIdx => $sf):
                $agg = po_aggregate($sf);
                $aWinner = $agg['winner'] === 'a';
                $bWinner = $agg['winner'] === 'b';
                $totalActiveLegs = count($sf['active_legs']);
            ?>

            <?php if ($sfIdx > 0): ?>
            <div style="height:20px;"></div>
            <?php endif; ?>

            <div class="po-sf-group" style="justify-content:center;">
                <div class="po-sf-label">Aggregate</div>
                <div class="po-match agg-card" style="margin:auto 0;">
                    <div class="po-match-header">
                        <span>Over <?= $totalActiveLegs ?> Leg<?= $totalActiveLegs !== 1 ? 's' : '' ?></span>
                        <span><?= $agg['legs_played'] ?>/<?= $totalActiveLegs ?> played</span>
                    </div>
                    <div class="po-team <?= $aWinner ? 'winner' : '' ?>">
                        <span><?= htmlspecialchars($sf['team_a']) ?></span>
                        <span class="po-score"><?= $agg['legs_played'] > 0 ? $agg['team_a'] : '–' ?></span>
                    </div>
                    <div class="po-team <?= $bWinner ? 'winner' : '' ?>">
                        <span><?= htmlspecialchars($sf['team_b']) ?></span>
                        <span class="po-score"><?= $agg['legs_played'] > 0 ? $agg['team_b'] : '–' ?></span>
                    </div>
                    <?php if ($agg['winner'] !== null): ?>
                    <div class="po-pens">✅ <?= htmlspecialchars($agg['winner'] === 'a' ? $sf['team_a'] : $sf['team_b']) ?> advance</div>
                    <?php elseif ($agg['legs_played'] >= $totalActiveLegs && $totalActiveLegs >= 2): ?>
                    <div class="po-pens">⚖️ Tied — ET / Pens may apply</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php endforeach; ?>

        </div><!-- /agg col -->

        <!-- Connector: Agg → Final -->
        <?php if ($finalPair !== null): ?>
        <div class="po-col po-col-connector" style="justify-content:center;">
            <div class="po-conn-half top"></div>
            <div class="po-conn-arrow">›</div>
            <div class="po-conn-half bot"></div>
        </div>
        <?php endif; ?>

        <!-- Column 3: Final -->
        <?php if ($finalPair !== null):
            $fLeg = $finalPair['active_legs'][0];
            $fSc = po_score($fLeg);
            $fPlayed = $fSc !== null;
            $fHasPens = isset($fLeg['home_pens']) && is_numeric($fLeg['home_pens']) && is_numeric($fLeg['away_pens']);
            if ($fPlayed) {
                if ($fHasPens) {
                    $fWinnerIsHome = (int)$fLeg['home_pens'] > (int)$fLeg['away_pens'];
                    $fWinnerIsAway = (int)$fLeg['away_pens'] > (int)$fLeg['home_pens'];
                } else {
                    $fWinnerIsHome = $fSc[0] > $fSc[1];
                    $fWinnerIsAway = $fSc[1] > $fSc[0];
                }
            }
        ?>
        <div class="po-col po-col-final">
            <div class="po-sf-label" style="text-align:center;">🏆 Final</div>
            <div class="po-match final-match">
                <div class="po-match-header">
                    <span>Final</span>
                    <span><?= htmlspecialchars($fLeg['match_date']) ?></span>
                </div>
                <div class="po-team <?= $fPlayed && $fWinnerIsHome ? 'winner-gold' : '' ?>">
                    <span><?= htmlspecialchars($fLeg['home_team']) ?></span>
                    <span class="po-score <?= !$fPlayed ? '' : 'live' ?>"><?= $fPlayed ? $fSc[0] : '–' ?></span>
                </div>
                <div class="po-team <?= $fPlayed && $fWinnerIsAway ? 'winner-gold' : '' ?>">
                    <span><?= htmlspecialchars($fLeg['away_team']) ?></span>
                    <span class="po-score <?= !$fPlayed ? '' : 'live' ?>"><?= $fPlayed ? $fSc[1] : '–' ?></span>
                </div>
                <?php if ($fHasPens): ?>
                <div class="po-pens">Pens: <?= (int)$fLeg['home_pens'] ?> – <?= (int)$fLeg['away_pens'] ?></div>
                <?php elseif ($fPlayed && $fSc[0] === $fSc[1]): ?>
                <div class="po-pens">⚖️ AET / Pens may apply</div>
                <?php elseif ($fPlayed): ?>
                <div class="po-pens" style="color:#FFD700;">
                    🏆 <?= htmlspecialchars($fWinnerIsHome ? $fLeg['home_team'] : $fLeg['away_team']) ?> promoted!
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php elseif (!empty($sfWinners) && count(array_filter($sfWinners)) === 2): ?>
        <!-- Final not yet in DB but both SF winners known — show placeholder -->
        <div class="po-col po-col-final">
            <div class="po-sf-label" style="text-align:center;">🏆 Final</div>
            <div class="po-match final-match">
                <div class="po-match-header"><span>Final</span><span>TBD</span></div>
                <div class="po-team"><span><?= htmlspecialchars($sfWinners[0]) ?></span><span class="po-score" style="color:#555;">–</span></div>
                <div class="po-team"><span><?= htmlspecialchars($sfWinners[1]) ?></span><span class="po-score" style="color:#555;">–</span></div>
                <div class="po-pens" style="color:#888;">Final date to be confirmed</div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /po-bracket -->

    <?php elseif ($finalPair !== null): ?>
    <!-- Only a final, no SF data (e.g. historical data stored differently) -->
    <?php
        $fLeg = $finalPair['active_legs'][0];
        $fSc = po_score($fLeg);
        $fPlayed = $fSc !== null;
        $fHasPens = isset($fLeg['home_pens']) && is_numeric($fLeg['home_pens']) && is_numeric($fLeg['away_pens']);
        if ($fPlayed) {
            $fWinnerIsHome = $fHasPens ? (int)$fLeg['home_pens'] > (int)$fLeg['away_pens'] : $fSc[0] > $fSc[1];
            $fWinnerIsAway = $fHasPens ? (int)$fLeg['away_pens'] > (int)$fLeg['home_pens'] : $fSc[1] > $fSc[0];
        }
    ?>
    <div style="max-width:360px;margin:0 auto;">
        <div class="po-sf-label" style="text-align:center;">🏆 Final</div>
        <div class="po-match final-match">
            <div class="po-match-header"><span>Final</span><span><?= htmlspecialchars($fLeg['match_date']) ?></span></div>
            <div class="po-team <?= $fPlayed && $fWinnerIsHome ? 'winner-gold' : '' ?>">
                <span><?= htmlspecialchars($fLeg['home_team']) ?></span>
                <span class="po-score"><?= $fPlayed ? $fSc[0] : '–' ?></span>
            </div>
            <div class="po-team <?= $fPlayed && $fWinnerIsAway ? 'winner-gold' : '' ?>">
                <span><?= htmlspecialchars($fLeg['away_team']) ?></span>
                <span class="po-score"><?= $fPlayed ? $fSc[1] : '–' ?></span>
            </div>
            <?php if ($fHasPens): ?>
            <div class="po-pens">Pens: <?= (int)$fLeg['home_pens'] ?> – <?= (int)$fLeg['away_pens'] ?></div>
            <?php elseif ($fPlayed && $fSc[0] === $fSc[1]): ?>
            <div class="po-pens">⚖️ AET / Pens may apply</div>
            <?php elseif ($fPlayed): ?>
            <div class="po-pens" style="color:#FFD700;">🏆 <?= htmlspecialchars($fWinnerIsHome ? $fLeg['home_team'] : $fLeg['away_team']) ?> promoted!</div>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>
    <div style="text-align:center;padding:30px;color:#555;font-size:14px;">
        ⚠️ Playoff data structure unrecognised — check data source.
    </div>
    <?php endif; ?>

    <?php endif; // end non-empty playoffs ?>
</div>
