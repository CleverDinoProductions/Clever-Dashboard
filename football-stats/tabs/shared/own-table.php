<?php
require_once __DIR__ . '/../../includes/table-view.php';
require_once __DIR__ . '/../../includes/own-table.php';
require_once dirname(__DIR__, 3) . '/lib/english-pyramid.php';
$competition = clever_regional_league_codes()[$currentLeague] ?? ['premier-league'=>'PL','championship'=>'ELC','league-one'=>'L1','league-two'=>'L2','national-league'=>'NL','division-one'=>'D1'][$currentLeague];
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$clubs = football_stats_get_match_roster($db, $competition, $currentMainTab);
$requested = $_GET['own_clubs'] ?? [];
$selected = is_array($requested) ? array_values(array_intersect($clubs, array_filter($requested, 'is_string'))) : [];
$from = football_stats_own_table_date($_GET['own_from'] ?? '');
$to = football_stats_own_table_date($_GET['own_to'] ?? '');
$error = $from !== '' && $to !== '' && $from > $to ? 'The start date must be on or before the end date.' : '';
$stmt = $db->prepare('SELECT * FROM matches WHERE competition_code=? AND season_label=? AND matchweek>=1 AND matchweek<=? ORDER BY match_date, id');
$stmt->execute([$competition, $currentMainTab, football_stats_get_final_matchweek($competition, $currentMainTab, $db)]);
$points = football_stats_get_result_points($competition, $currentMainTab, $db);
$data = football_stats_own_table($error ? [] : $stmt->fetchAll(PDO::FETCH_ASSOC), $selected, $from, $to, $points);
$columns = ['club'=>'Club','played'=>'Pl','won'=>'W','drawn'=>'D','lost'=>'L','gf'=>'GF','ga'=>'GA','gd'=>'GD','points'=>'Pts','ppg'=>'Points per match'];
$sort = is_string($_GET['own_sort'] ?? null) && isset($columns[$_GET['own_sort']]) ? $_GET['own_sort'] : 'points';
$direction = ($_GET['own_direction'] ?? '') === 'asc' ? 'asc' : 'desc';
$rank = array_flip(array_column($data['rows'], 'club'));
usort($data['rows'], static function($a,$b) use($sort,$direction,$rank) {
    $comparison = $sort === 'club' ? strcasecmp($a[$sort],$b[$sort]) : $a[$sort] <=> $b[$sort];
    return ($direction === 'asc' ? $comparison : -$comparison) ?: ($rank[$a['club']] <=> $rank[$b['club']]);
});
?>
<section class="panel own-table">
<h2>Own Table</h2>
<p>Choose clubs to build your own mini league. Only completed regular-season matches <strong>between the selected clubs</strong> count within the chosen dates.</p>
<form method="get">
<input type="hidden" name="tab" value="<?= $h($currentMainTab) ?>"><input type="hidden" name="league" value="<?= $h($currentLeague) ?>"><input type="hidden" name="subtab" value="own-table">
<div class="own-controls"><label>From <input type="date" name="own_from" value="<?= $h($from) ?>"></label><label>Through <input type="date" name="own_to" value="<?= $h($to) ?>"></label><span>Leave dates blank for the full season. Both dates are inclusive.</span></div>
<?php if($error): ?><p role="alert"><?= $h($error) ?></p><?php endif; ?>
<fieldset><legend>Club selection</legend>
<div class="own-actions"><button type="button" data-own-select="all">Select all</button><button type="button" data-own-select="none">Clear selection</button><span id="own-selection-count" aria-live="polite"><?= count($selected) ?> clubs selected</span></div>
<div class="own-clubs"><?php foreach($clubs as $club): ?><label><input type="checkbox" name="own_clubs[]" value="<?= $h($club) ?>" <?= in_array($club,$selected,true)?'checked':'' ?>> <?= $h($club) ?></label><?php endforeach; ?></div>
<?php if(!$clubs): ?><p>No clubs have been imported for this league and season.</p><?php endif; ?>
</fieldset>
<button type="submit" class="own-build">Build table</button> <a href="<?= $h(build_tab_url($currentMainTab,$currentLeague,'own-table')) ?>">Reset</a>
</form>
<?php if(count($selected)<2): ?><p>Select at least two clubs, then choose Build table.</p><?php elseif(!$error): ?>
<p><?= count($data['matches']) ?> matches counted · <?= count($selected) ?> clubs · Win <?= $points['win'] ?> / Draw <?= $points['draw'] ?> / Loss <?= $points['loss'] ?> points. No whole-season deductions are applied.</p>
<div class="own-scroll"><table><caption>Standings from selected head-to-head results</caption><thead><tr><th scope="col">#</th><?php foreach($columns as $key=>$label): $params=['tab'=>$currentMainTab,'league'=>$currentLeague,'subtab'=>'own-table','own_clubs'=>$selected,'own_from'=>$from,'own_to'=>$to,'own_sort'=>$key,'own_direction'=>$sort===$key && $direction==='desc'?'asc':'desc']; ?><th scope="col" <?= $sort===$key?'aria-sort="'.($direction==='asc'?'ascending':'descending').'"':'' ?>><a href="?<?= $h(http_build_query($params)) ?>"><?= $label ?> <?= $sort===$key?($direction==='asc'?'↑':'↓'):'↕' ?></a></th><?php endforeach; ?></tr></thead><tbody>
<?php foreach($data['rows'] as $row): ?><tr><td><?= $rank[$row['club']]+1 ?></td><?php foreach($columns as $key=>$label): ?><<?= $key==='club'?'th scope="row"':'td' ?>><?= $key==='ppg'?($row['played']?number_format($row[$key],2):'—'):$h($row[$key]) ?></<?= $key==='club'?'th':'td' ?>><?php endforeach; ?></tr><?php endforeach; ?>
</tbody></table></div>
<p>Ranks use points, goal difference, then goals scored, with club name breaking remaining ties. Clubs with no counted matches stay visible.</p>
<?php if(!$data['matches']): ?><p>No completed matches between these clubs match your date range.</p><?php else: ?><details><summary>View counted matches (<?= count($data['matches']) ?>)</summary><div class="own-scroll"><table><thead><tr><th>Date</th><th>Home</th><th>Score</th><th>Away</th></tr></thead><tbody><?php foreach($data['matches'] as $match): ?><tr><td><?= $h($match['match_date'] ?: 'Unknown') ?></td><td><?= $h($match['home_team']) ?></td><td><?= (int)$match['home_goals'] ?> : <?= (int)$match['away_goals'] ?></td><td><?= $h($match['away_team']) ?></td></tr><?php endforeach; ?></tbody></table></div></details><?php endif; ?>
<?php endif; ?>
</section>
<link rel="stylesheet" href="assets/own-table.css">
<script src="assets/own-table.js" defer></script>
