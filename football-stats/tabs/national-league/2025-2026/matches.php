<?php
// Championship 2025/26 Matches Tab
// This page will display all matches for the season, both from the text import and from the API

require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../config.php';

$db = new PDO('sqlite:' . __DIR__ . '/../../../football-stats.sqlite3');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch all matches for this season (imported and API)
// Get all matchweeks for dropdown
$mw_stmt = $db->prepare("SELECT DISTINCT matchweek FROM matches WHERE competition_code = ? AND season_label = ? ORDER BY matchweek ASC");
$mw_stmt->execute(['NL', '2025-2026']);
$matchweeks = $mw_stmt->fetchAll(PDO::FETCH_COLUMN);

$selected_mw = isset($_GET['matchweek']) && $_GET['matchweek'] !== '' ? (int)$_GET['matchweek'] : '';

if ($selected_mw !== '') {
    $stmt = $db->prepare("SELECT * FROM matches WHERE competition_code = ? AND season_label = ? AND matchweek = ? ORDER BY match_date, id");
    $stmt->execute(['NL', '2025-2026', $selected_mw]);
} else {
    $stmt = $db->prepare("SELECT * FROM matches WHERE competition_code = ? AND season_label = ? ORDER BY matchweek, match_date, id");
    $stmt->execute(['NL', '2025-2026']);
}
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
// Find the first date for the selected matchweek (or first matchweek if none selected)
$first_date = '';
if (!empty($matches)) {
    $first_date = $matches[0]['match_date'];
}
?>
<div class="panel">
    <h2>National League 2025/26 – Matches<?php if ($selected_mw !== ''): ?> – MW<?= htmlspecialchars($selected_mw) ?><?php endif; ?><?php if ($first_date): ?> <span style="font-size:14px; color:#00ff88;">(<?= htmlspecialchars($first_date) ?>)</span><?php endif; ?></h2>
    <form method="get" style="margin-bottom: 18px;">
        <?php
        // Preserve tab/league/subtab params
        foreach (["tab", "league", "subtab"] as $param) {
            if (isset($_GET[$param])) {
                echo '<input type="hidden" name="' . htmlspecialchars($param) . '" value="' . htmlspecialchars($_GET[$param]) . '">';
            }
        }
        ?>
        <label for="matchweek-select" style="font-weight:600; color:#00ff88;">Matchweek:</label>
        <select id="matchweek-select" name="matchweek" onchange="this.form.submit()" style="margin-left:8px; padding:4px 10px; border-radius:6px;">
            <option value="">All</option>
            <?php foreach ($matchweeks as $mw): ?>
                <option value="<?= $mw ?>" <?= ($selected_mw == $mw) ? 'selected' : '' ?>>MW<?= $mw ?></option>
            <?php endforeach; ?>
        </select>
    </form>
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
