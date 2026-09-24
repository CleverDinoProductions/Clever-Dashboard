<?php
/**
 * Shared, side-by-side season comparison used by every English league.
 *
 * The including tab must provide $comparisonLeague with code, name,
 * live_table, total_games and halfway_games keys.
 */
require_once __DIR__ . '/table-view.php';

$comparisonLeague = $comparisonLeague ?? [];
$competitionCode = (string)($comparisonLeague['code'] ?? '');
$leagueName = (string)($comparisonLeague['name'] ?? $competitionCode);
$liveTableName = (string)($comparisonLeague['live_table'] ?? '');
$totalGames = (int)($comparisonLeague['total_games'] ?? football_stats_get_final_matchweek($competitionCode));
$halfwayGames = (int)($comparisonLeague['halfway_games'] ?? (int)ceil($totalGames / 2));

$seasonStmt = $db->prepare(
    'SELECT DISTINCT season_label FROM ('
    . 'SELECT season_label FROM league_table_snapshots WHERE competition_code = ? '
    . 'UNION SELECT season_label FROM league_table_snapshots_by_date WHERE competition_code = ? '
    . 'UNION SELECT season_label FROM matches WHERE competition_code = ?'
    . ') ORDER BY season_label DESC'
);
$seasonStmt->execute([$competitionCode, $competitionCode, $competitionCode]);
$availableSeasons = $seasonStmt->fetchAll(PDO::FETCH_COLUMN);
$defaultLeftSeason = $availableSeasons[1] ?? $availableSeasons[0] ?? ($currentMainTab ?? '2025-2026');
$defaultRightSeason = $availableSeasons[0] ?? ($currentMainTab ?? '2025-2026');

$validCalcModes = ['by_matchweek', 'by_date', 'by_match', 'by_match_before', 'custom_matches'];
$calcMode = in_array($_GET['compare_calc_mode'] ?? '', $validCalcModes, true)
    ? $_GET['compare_calc_mode']
    : 'by_matchweek';
$tableStyle = ($_GET['compare_table_style'] ?? '') === 'deep' ? 'deep' : 'regular';
$tableFilter = in_array($_GET['compare_table_filter'] ?? '', ['first_half', 'second_half', 'home', 'away'], true)
    ? $_GET['compare_table_filter']
    : 'all';

/** Execute the normal table calculation engine with namespaced comparison inputs. */
$buildComparisonSide = static function ($side, $defaultSeason) use ($db, $competitionCode, $liveTableName, $calcMode, $tableFilter, $halfwayGames, $totalGames) {
    $season = preg_replace('/[^0-9\-]/', '', (string)($_GET['compare_season_' . $side] ?? $defaultSeason));
    $savedGet = $_GET;
    $_GET['calc_mode'] = $calcMode;
    $_GET['snapshot_season'] = $season;
    $_GET['table_view'] = 'snapshot';

    $pointKeys = [
        'matchweek' => 'compare_matchweek_' . $side,
        'snapshot_date' => 'compare_date_' . $side,
        'match_id' => 'compare_match_' . $side,
    ];
    foreach ($pointKeys as $normalKey => $comparisonKey) {
        unset($_GET[$normalKey]);
        if (isset($savedGet[$comparisonKey]) && $savedGet[$comparisonKey] !== '') {
            $_GET[$normalKey] = $savedGet[$comparisonKey];
        }
    }
    // A season change can submit the point selected for the previous season.
    // Let the standard engine choose its newest point instead of falling back
    // to the current live table when that matchweek is not valid here.
    if ($calcMode === 'by_matchweek' && isset($_GET['matchweek'])) {
        $validPointStmt = $db->prepare(
            'SELECT 1 FROM league_table_snapshots WHERE competition_code = ? '
            . 'AND season_label = ? AND matchweek = ? LIMIT 1'
        );
        $validPointStmt->execute([$competitionCode, $season, (int)$_GET['matchweek']]);
        if (!$validPointStmt->fetchColumn()) unset($_GET['matchweek']);
    }

    $view = football_stats_get_table_view_combined($db, $competitionCode, $liveTableName, $season);
    $_GET = $savedGet;
    $standings = $view['standings'];
    $effectiveGames = $totalGames;
    if ($tableFilter !== 'all') {
        $limit = isset($view['active_matchweek']) ? min($totalGames, (int)$view['active_matchweek']) : $totalGames;
        $filtered = football_stats_compute_filtered_standings($db, $competitionCode, $season, $tableFilter, $halfwayGames, $liveTableName, $limit);
        if ($filtered) $standings = $filtered;
        $effectiveGames = $tableFilter === 'first_half' ? $halfwayGames
            : ($tableFilter === 'second_half' ? $totalGames - $halfwayGames : (int)($totalGames / 2));
    }
    $deductions = football_stats_get_points_deductions($db, $competitionCode, $season);
    if ($deductions) $standings = football_stats_apply_points_deductions($standings, $deductions);
    $view['standings'] = $standings;
    $view['effective_games'] = $effectiveGames;
    $view['comparison_season'] = $season;
    return $view;
};

$leftView = $buildComparisonSide('left', $defaultLeftSeason);
$rightView = $buildComparisonSide('right', $defaultRightSeason);

/** Fetch choices for a side without coupling its selected point to the other side. */
$getPointOptions = static function ($season) use ($db, $competitionCode, $calcMode, $totalGames) {
    if ($calcMode === 'by_date') {
        $stmt = $db->prepare('SELECT DISTINCT snapshot_date AS value, snapshot_date AS label FROM league_table_snapshots_by_date WHERE competition_code = ? AND season_label = ? ORDER BY snapshot_date DESC');
    } elseif (in_array($calcMode, ['by_match', 'by_match_before'], true)) {
        $stmt = $db->prepare("SELECT id AS value, ('MW' || matchweek || ': ' || home_team || ' v ' || away_team) AS label FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek BETWEEN 1 AND ? ORDER BY COALESCE(NULLIF(match_timestamp, ''), match_date), id");
        $stmt->execute([$competitionCode, $season, $totalGames]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->prepare("SELECT DISTINCT matchweek AS value, ('Matchweek ' || matchweek) AS label FROM league_table_snapshots WHERE competition_code = ? AND season_label = ? AND matchweek BETWEEN 1 AND ? ORDER BY matchweek DESC");
        $stmt->execute([$competitionCode, $season, $totalGames]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $stmt->execute([$competitionCode, $season]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
};

$pointField = $calcMode === 'by_date' ? 'date' : (in_array($calcMode, ['by_match', 'by_match_before'], true) ? 'match' : 'matchweek');
$leftOptions = $getPointOptions($leftView['comparison_season']);
$rightOptions = $getPointOptions($rightView['comparison_season']);
$selectedPoint = static function ($side, array $view) use ($pointField) {
    $requestKey = 'compare_' . $pointField . '_' . $side;
    if (isset($_GET[$requestKey])) return (string)$_GET[$requestKey];
    if ($pointField === 'date') return (string)($view['active_date'] ?? '');
    if ($pointField === 'match') return (string)($view['selected_match_id'] ?? '');
    return (string)($view['active_matchweek'] ?? '');
};
?>

<style>
.season-compare-controls{display:flex;flex-wrap:wrap;gap:14px;align-items:end;margin:18px 0;padding:16px;background:#202225;border-radius:8px}.season-compare-controls label{display:block;color:#b9bbbe;font-size:12px;margin-bottom:5px}.season-compare-controls select{min-width:150px}.season-compare-grid{display:grid;grid-template-columns:repeat(2,minmax(520px,1fr));gap:22px;overflow-x:auto}.season-compare-card{min-width:0}.season-compare-scroll{overflow:auto;max-height:72vh}.season-compare-table{width:100%;border-collapse:collapse;font-size:13px}.season-compare-table th{position:sticky;top:0;background:#222;color:#00ff88;z-index:2}.season-compare-table th,.season-compare-table td{padding:8px;text-align:center;border-bottom:1px solid #333;white-space:nowrap}.season-compare-table .team{text-align:left}.season-compare-empty{padding:30px;text-align:center;color:#b9bbbe}@media(max-width:1100px){.season-compare-grid{grid-template-columns:1fr}}
</style>

<div class="panel">
    <h2><?= htmlspecialchars($leagueName, ENT_QUOTES, 'UTF-8') ?> – Compare Seasons</h2>
    <p>Compare two seasons side by side using the same calculation and table modes as the Regular and Deep Dive tables.</p>
    <form method="get" class="season-compare-controls">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($currentMainTab ?? '2025-2026', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="league" value="<?= htmlspecialchars($currentLeague ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="subtab" value="compare-seasons">
        <div><label for="compare-calc">Calculation mode</label><select id="compare-calc" name="compare_calc_mode">
            <option value="by_matchweek" <?= $calcMode === 'by_matchweek' ? 'selected' : '' ?>>By matchweek</option>
            <option value="by_date" <?= $calcMode === 'by_date' ? 'selected' : '' ?>>By date</option>
            <option value="by_match" <?= $calcMode === 'by_match' ? 'selected' : '' ?>>After a match</option>
            <option value="by_match_before" <?= $calcMode === 'by_match_before' ? 'selected' : '' ?>>Before a match</option>
            <option value="custom_matches" <?= $calcMode === 'custom_matches' ? 'selected' : '' ?>>All completed matches</option>
        </select></div>
        <div><label for="compare-style">Table mode</label><select id="compare-style" name="compare_table_style"><option value="regular">Regular Table</option><option value="deep" <?= $tableStyle === 'deep' ? 'selected' : '' ?>>Deep Dive Table</option></select></div>
        <div><label for="compare-filter">Results included</label><select id="compare-filter" name="compare_table_filter">
            <?php foreach (['all'=>'Full season','first_half'=>'First half','second_half'=>'Second half','home'=>'Home only','away'=>'Away only'] as $value=>$label): ?><option value="<?= $value ?>" <?= $tableFilter === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
        </select></div>
        <?php foreach (['left'=>$leftView,'right'=>$rightView] as $side=>$view): ?>
            <div><label for="season-<?= $side ?>"><?= ucfirst($side) ?> season</label><select id="season-<?= $side ?>" name="compare_season_<?= $side ?>">
                <?php foreach ($availableSeasons as $season): ?><option value="<?= htmlspecialchars($season, ENT_QUOTES, 'UTF-8') ?>" <?= $view['comparison_season'] === $season ? 'selected' : '' ?>><?= htmlspecialchars($season, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
            </select></div>
        <?php endforeach; ?>
        <?php if ($calcMode !== 'custom_matches'): foreach (['left'=>[$leftView,$leftOptions],'right'=>[$rightView,$rightOptions]] as $side=>$data): ?>
            <div><label for="point-<?= $side ?>"><?= ucfirst($side) ?> point</label><select id="point-<?= $side ?>" name="compare_<?= $pointField ?>_<?= $side ?>">
                <?php $activePoint=$selectedPoint($side,$data[0]); foreach ($data[1] as $option): ?><option value="<?= htmlspecialchars((string)$option['value'], ENT_QUOTES, 'UTF-8') ?>" <?= $activePoint === (string)$option['value'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$option['label'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
            </select></div>
        <?php endforeach; endif; ?>
        <button type="submit">Compare seasons</button>
    </form>

    <div class="season-compare-grid">
        <?php foreach (['left'=>$leftView,'right'=>$rightView] as $side=>$view): ?>
        <section class="season-compare-card" aria-labelledby="<?= $side ?>-season-heading">
            <h3 id="<?= $side ?>-season-heading"><?= htmlspecialchars($view['comparison_season'], ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="season-compare-scroll">
            <?php if (!$view['standings']): ?><div class="season-compare-empty">No standings are available for this selection.</div><?php else: ?>
            <table class="season-compare-table"><thead><tr><th>Pos</th><th class="team">Team</th><th>P</th><th>Pts</th><?php if ($tableStyle === 'regular'): ?><th>W</th><th>D</th><th>L</th><th>GF</th><th>GA</th><th>GD</th><?php else: ?><th>PPG</th><th>GR</th><th>Buffer</th><th>Projected</th><th>Max</th><th>PPG to 40</th><?php endif; ?></tr></thead><tbody>
            <?php foreach ($view['standings'] as $team): $played=(int)$team['played'];$points=(int)$team['points'];$remaining=max(0,(int)$view['effective_games']-$played);$ppg=$played ? $points/$played : 0; ?>
                <tr><td><?= (int)$team['position'] ?></td><td class="team"><?= htmlspecialchars($team['team_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= $played ?></td><td><strong><?= $points ?></strong></td>
                <?php if ($tableStyle === 'regular'): ?><td><?= (int)$team['won'] ?></td><td><?= (int)$team['drawn'] ?></td><td><?= (int)$team['lost'] ?></td><td><?= (int)$team['gf'] ?></td><td><?= (int)$team['ga'] ?></td><td><?= (int)$team['gd'] ?></td>
                <?php else: ?><td><?= number_format($ppg,2) ?></td><td><?= $remaining ?></td><td><?= sprintf('%+d',$points-$played) ?></td><td><?= (int)round($points+($ppg*$remaining)) ?></td><td><?= $points+($remaining*3) ?></td><td><?= $remaining ? number_format(max(0,40-$points)/$remaining,2) : '—' ?></td><?php endif; ?></tr>
            <?php endforeach; ?></tbody></table>
            <?php endif; ?>
            </div>
            <?php football_stats_render_points_deductions(football_stats_get_points_deductions($db, $competitionCode, $view['comparison_season'])); ?>
        </section>
        <?php endforeach; ?>
    </div>
</div>
