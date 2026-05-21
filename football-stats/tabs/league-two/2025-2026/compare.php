<?php
// League Two 2025/26 Compare Tab
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../config.php';

$db = new PDO('sqlite:' . __DIR__ . '/../../../football-stats.sqlite3');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$season = '2025-2026';
$competition_code = 'L2';
$live_table = 'league_table_L2';

// Get all available matchweeks (including 0 for pre-season)
$mw_stmt = $db->prepare("SELECT DISTINCT matchweek FROM league_table_snapshots WHERE competition_code = ? AND season_label = ? ORDER BY matchweek ASC");
$mw_stmt->execute([$competition_code, $season]);
$matchweeks = $mw_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get selected matchweeks (or default to last and pre-season)
$mw1 = isset($_GET['mw1']) ? (int)$_GET['mw1'] : (count($matchweeks) > 0 ? (int)$matchweeks[0] : 0);
$mw2 = isset($_GET['mw2']) ? (int)$_GET['mw2'] : (count($matchweeks) > 0 ? (int)end($matchweeks) : 0);
$view1 = isset($_GET['view1']) ? $_GET['view1'] : 'snapshot';
$view2 = isset($_GET['view2']) ? $_GET['view2'] : 'live';

function get_table($db, $competition_code, $season, $view, $mw, $live_table) {
    if ($view === 'live') {
        $stmt = $db->query("SELECT * FROM $live_table ORDER BY position ASC");
        $label = 'Live Table';
    } else {
        $stmt = $db->prepare("SELECT * FROM league_table_snapshots WHERE competition_code = ? AND season_label = ? AND matchweek = ? ORDER BY position ASC");
        $stmt->execute([$competition_code, $season, $mw]);
        $label = "Snapshot MW$mw";
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return [$label, $rows];
}

list($label1, $table1) = get_table($db, $competition_code, $season, $view1, $mw1, $live_table);
list($label2, $table2) = get_table($db, $competition_code, $season, $view2, $mw2, $live_table);
?>
<div class="panel">
    <h2>League Two 2025/26 – Compare Tables</h2>
    <form method="get" style="margin-bottom: 18px; display:flex; gap:24px; align-items:center;">
        <?php
        // Preserve tab/league/subtab params
        foreach (["tab", "league", "subtab"] as $param) {
            $val = $_GET[$param] ?? null;
            if ($val !== null) {
                echo '<input type="hidden" name="' . htmlspecialchars($param) . '" value="' . htmlspecialchars($val) . '">';
            } else {
                // Set defaults if not present
                if ($param === 'tab') echo '<input type="hidden" name="tab" value="2025-2026">';
                if ($param === 'league') echo '<input type="hidden" name="league" value="league-two">';
                if ($param === 'subtab') echo '<input type="hidden" name="subtab" value="compare">';
            }
        }
        ?>
        <div>
            <label>Left Table:</label>
            <select name="view1">
                <option value="snapshot" <?= $view1==='snapshot'?'selected':'' ?>>Snapshot</option>
                <option value="live" <?= $view1==='live'?'selected':'' ?>>Live</option>
            </select>
            <select name="mw1">
                <?php foreach ($matchweeks as $mw): ?>
                    <option value="<?= $mw ?>" <?= ($mw1==$mw)?'selected':'' ?>>MW<?= $mw ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Right Table:</label>
            <select name="view2">
                <option value="snapshot" <?= $view2==='snapshot'?'selected':'' ?>>Snapshot</option>
                <option value="live" <?= $view2==='live'?'selected':'' ?>>Live</option>
            </select>
            <select name="mw2">
                <?php foreach ($matchweeks as $mw): ?>
                    <option value="<?= $mw ?>" <?= ($mw2==$mw)?'selected':'' ?>>MW<?= $mw ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Compare</button>
    </form>
    <div style="display:flex; gap:32px; flex-wrap:wrap;">
        <div style="flex:1; min-width:320px;">
            <h3><?= htmlspecialchars($label1) ?></h3>
            <table class="matches-table" style="width:100%; font-size:13px;">
                <thead>
                    <tr style="background:#222; color:#00ff88;">
                        <th>Pos</th><th>Team</th><th>P</th><th>W</th><th>D</th><th>L</th><th>GF</th><th>GA</th><th>GD</th><th>Pts</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($table1 as $row): ?>
                    <tr>
                        <td><?= $row['position'] ?></td>
                        <td><?= htmlspecialchars($row['team_name']) ?></td>
                        <td><?= $row['played'] ?></td>
                        <td><?= $row['won'] ?></td>
                        <td><?= $row['drawn'] ?></td>
                        <td><?= $row['lost'] ?></td>
                        <td><?= $row['gf'] ?></td>
                        <td><?= $row['ga'] ?></td>
                        <td><?= $row['gd'] ?></td>
                        <td><?= $row['points'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="flex:1; min-width:320px;">
            <h3><?= htmlspecialchars($label2) ?></h3>
            <table class="matches-table" style="width:100%; font-size:13px;">
                <thead>
                    <tr style="background:#222; color:#00ff88;">
                        <th>Pos</th><th>Team</th><th>P</th><th>W</th><th>D</th><th>L</th><th>GF</th><th>GA</th><th>GD</th><th>Pts</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($table2 as $row): ?>
                    <tr>
                        <td><?= $row['position'] ?></td>
                        <td><?= htmlspecialchars($row['team_name']) ?></td>
                        <td><?= $row['played'] ?></td>
                        <td><?= $row['won'] ?></td>
                        <td><?= $row['drawn'] ?></td>
                        <td><?= $row['lost'] ?></td>
                        <td><?= $row['gf'] ?></td>
                        <td><?= $row['ga'] ?></td>
                        <td><?= $row['gd'] ?></td>
                        <td><?= $row['points'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
