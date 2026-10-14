<?php
require_once dirname(__DIR__, 3) . '/lib/english-pyramid.php';
$regional = clever_regional_competitions()[$currentLeague];
$regionalCode = $regional['code'];
$regionalName = $regional['label'];
$regionalTable = 'league_table_' . $regionalCode;
$exists = $db->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name=?");
$exists->execute([$regionalTable]);
// An empty live table may still have archived seasons worth browsing.
$hasData = false;
if ($exists->fetchColumn()) {
    $hasData = (bool)$db->query("SELECT 1 FROM $regionalTable LIMIT 1")->fetchColumn();
    foreach (['matches', 'league_table_snapshots', 'league_table_snapshots_by_date'] as $archiveTable) {
        $exists->execute([$archiveTable]);
        if (!$exists->fetchColumn()) continue;
        $archived = $db->prepare("SELECT 1 FROM $archiveTable WHERE competition_code=? LIMIT 1");
        $archived->execute([$regionalCode]);
        $hasData = $hasData || (bool)$archived->fetchColumn();
    }
}
if (!$hasData) {
    ?><section class="panel"><h2><?= htmlspecialchars($regionalName, ENT_QUOTES, 'UTF-8') ?> · Tier <?= $regional['tier'] ?></h2><p>No data has been imported for this division yet.</p></section><?php
    return;
}
$regionalFiles = ['table'=>'table.php', 'table-2'=>'table2.php', 'matches'=>'matches.php', 'compare-seasons'=>'compare-seasons.php', 'simulation'=>'simulation.php'];
if ($currentSubTab === 'cross-season') {
    require __DIR__ . '/cross-season.php';
} else {
    require __DIR__ . '/../regional/2025-2026/' . $regionalFiles[$currentSubTab];
}
