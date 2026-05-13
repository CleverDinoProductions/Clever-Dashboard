<?php
// League Two Matches Tab

require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../config.php';

$db = new PDO('sqlite:' . __DIR__ . '/../../../football-stats.sqlite3');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get available seasons for this competition
$seasons_stmt = $db->prepare("SELECT DISTINCT season_label FROM matches WHERE competition_code = ? ORDER BY season_label DESC");
$seasons_stmt->execute(['L2']);
$availableSeasons = $seasons_stmt->fetchAll(PDO::FETCH_COLUMN);

// Determine selected season (default to most recent)
$defaultSeason = !empty($availableSeasons) ? $availableSeasons[0] : '2025-2026';
$selectedSeason = isset($_GET['snapshot_season']) && $_GET['snapshot_season'] !== ''
    ? preg_replace('/[^0-9\-]/', '', (string)$_GET['snapshot_season'])
    : $defaultSeason;
if (!in_array($selectedSeason, $availableSeasons, true)) {
    $selectedSeason = $defaultSeason;
}

// Get matchweeks for the selected season
$mw_stmt = $db->prepare("SELECT DISTINCT matchweek FROM matches WHERE competition_code = ? AND season_label = ? ORDER BY matchweek ASC");
$mw_stmt->execute(['L2', $selectedSeason]);
$matchweeks = $mw_stmt->fetchAll(PDO::FETCH_COLUMN);

$selected_mw = isset($_GET['matchweek']) && $_GET['matchweek'] !== '' ? (int)$_GET['matchweek'] : '';

if ($selected_mw !== '') {
    $stmt = $db->prepare("SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek = ? ORDER BY match_date, id");
    $stmt->execute(['L2', $selectedSeason, $selected_mw]);
} else {
    $stmt = $db->prepare("SELECT * FROM matches WHERE competition_code = ? AND season_label = ? ORDER BY matchweek, match_date, id");
    $stmt->execute(['L2', $selectedSeason]);
}
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$first_date = !empty($matches) ? $matches[0]['match_date'] : '';
$seasonDisplay = $seasonLabels[$selectedSeason] ?? $selectedSeason;
?>
<div class="panel">
    <h2>League Two <?= htmlspecialchars($seasonDisplay) ?> – Matches<?php if ($selected_mw !== ''): ?> – MW<?= htmlspecialchars($selected_mw) ?><?php endif; ?><?php if ($first_date): ?> <span style="font-size:14px; color:#00ff88;">(<?= htmlspecialchars($first_date) ?>)</span><?php endif; ?></h2>
    <?php football_stats_render_matches_controls($availableSeasons, $matchweeks, $selectedSeason, $selected_mw, $currentMainTab, $currentLeague, $currentSubTab); ?>
    <table class="matches-table" style="width:100%; font-size:13px;">
        <thead>
            <tr style="background:#222; color:#00ff88;">
                <th>Matchweek</th>
                <th>Date</th>
                <th>Home</th>
                <th>Score</th>
                <th>Away</th>
                <th>Source</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($matches as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['matchweek']) ?></td>
                <td><?= htmlspecialchars($m['match_date']) ?></td>
                <td><?= htmlspecialchars($m['home_team']) ?></td>
                <td style="text-align:center; font-weight:bold;">
                    <?= is_numeric($m['home_goals']) && is_numeric($m['away_goals']) ? $m['home_goals'] . ' - ' . $m['away_goals'] : '-' ?>
                </td>
                <td><?= htmlspecialchars($m['away_team']) ?></td>
                <td><?= htmlspecialchars($m['source']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
