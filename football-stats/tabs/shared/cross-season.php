<?php
require_once __DIR__ . '/../../includes/cross-season-analysis.php';
$competition = clever_regional_league_codes()[$currentLeague] ?? ['premier-league'=>'PL','championship'=>'ELC','league-one'=>'L1','league-two'=>'L2','national-league'=>'NL','division-one'=>'D1'][$currentLeague];
$h=static fn($v)=>htmlspecialchars((string)$v, ENT_QUOTES,'UTF-8');
$stmt=$db->prepare('SELECT DISTINCT season_label FROM matches WHERE competition_code=? ORDER BY season_label DESC'); $stmt->execute([$competition]); $available=$stmt->fetchAll(PDO::FETCH_COLUMN);
$requested=isset($_GET['analysis_seasons']) && is_array($_GET['analysis_seasons']) ? $_GET['analysis_seasons'] : ['2025-2026','2026-2027'];
$selected=array_values(array_intersect($available,array_filter($requested,'is_string')));
if (!$selected && $available) $selected=array_slice($available,0,2);
$teamStmt=$db->prepare('SELECT home_team AS team FROM matches WHERE competition_code=? UNION SELECT away_team AS team FROM matches WHERE competition_code=? ORDER BY team'); $teamStmt->execute([$competition,$competition]); $teams=$teamStmt->fetchAll(PDO::FETCH_COLUMN);
$team=is_string($_GET['analysis_team'] ?? null)?$_GET['analysis_team']:'';
if (!in_array($team,$teams,true)) { $team=$teams[0]??''; foreach ($teams as $candidate) if (football_stats_normalize_group_team($candidate)==='leeds united') $team=$candidate; }
$enum=static fn($key,$values,$default)=>is_string($_GET[$key]??null)&&in_array($_GET[$key],$values,true)?$_GET[$key]:$default;
$number=static fn($key,$default,$max)=>isset($_GET[$key])&&is_scalar($_GET[$key])?max(0,min($max,(int)$_GET[$key])):$default;
$labels=football_stats_match_group_labels(); $labels['current-top-half']='Top half before kickoff'; $labels['current-bottom-half']='Bottom half before kickoff';
$options=['opponent_group'=>$enum('analysis_opponent',array_keys($labels),'all'),'venue'=>$enum('analysis_venue',['all','home','away'],'all'),'result'=>$enum('analysis_result',['all','win','draw','loss'],'all'),'start'=>max(1,$number('analysis_start',1,100)),'end'=>max(1,$number('analysis_end',100,100)),'points'=>['win'=>$number('analysis_win',3,100),'draw'=>$number('analysis_draw',1,100),'loss'=>$number('analysis_loss',0,100)]];
$options['excluded']=isset($_GET['analysis_excluded'])&&is_array($_GET['analysis_excluded'])?array_map('intval',array_filter($_GET['analysis_excluded'],'is_scalar')):[];
$options['outcomes']=isset($_GET['analysis_outcomes'])&&is_array($_GET['analysis_outcomes'])?array_filter($_GET['analysis_outcomes'],static fn($v)=>is_string($v)&&in_array($v,['win','draw','loss'],true)):[];
$deduction=$number('analysis_deduction',0,1000);
$results=[]; $groups=clever_team_groups($dashboardSettings??[], $competition);
foreach ($selected as $season) {
 $stmt=$db->prepare('SELECT * FROM matches WHERE competition_code=? AND season_label=? AND matchweek>=1 AND matchweek<=?'); $stmt->execute([$competition,$season,football_stats_get_final_matchweek($competition,$season,$db)]);
 $fixtures=$stmt->fetchAll(PDO::FETCH_ASSOC); $results[$season]=football_stats_cross_season_analysis($fixtures,$team,$options,$groups);
}
?>
<section class="panel">
<h2>Cross-season rules lab</h2>
<p>Compare a team’s results across seasons with custom filters and scoring. Opponent halves use reconstructed standings immediately before kickoff, following actual play dates. Kickoff times use an estimated two-hour completion window to avoid counting overlapping matches. Same-time fixtures share one baseline; dates without kickoff times use the start of that day. Opening fixtures, unknown dates and ties across the halfway boundary remain unclassified.</p>
<form method="get" class="analysis-form">
<input type="hidden" name="tab" value="<?= $h($currentMainTab) ?>"><input type="hidden" name="league" value="<?= $h($currentLeague) ?>"><input type="hidden" name="subtab" value="cross-season">
<div class="analysis-controls">
<label>Team<select name="analysis_team"><?php foreach($teams as $name): ?><option <?= $team===$name?'selected':'' ?>><?= $h($name) ?></option><?php endforeach; ?></select></label>
<fieldset><legend>Seasons</legend><?php foreach($available as $season): ?><label><input type="checkbox" name="analysis_seasons[]" value="<?= $h($season) ?>" <?= in_array($season,$selected,true)?'checked':'' ?>><?= $h($seasonLabels[$season]??$season) ?></label><?php endforeach; ?><?php if(!$available): ?>No seasons available.<?php endif; ?></fieldset>
<label>Opponent group<select name="analysis_opponent"><?php foreach($labels as $key=>$label): ?><option value="<?= $h($key) ?>" <?= $options['opponent_group']===$key?'selected':'' ?>><?= $h($label) ?></option><?php endforeach; ?></select></label>
<label>Venue<select name="analysis_venue"><?php foreach(['all'=>'Home and away','home'=>'Home','away'=>'Away'] as $key=>$label): ?><option value="<?= $key ?>" <?= $options['venue']===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<label>Include results<select name="analysis_result"><?php foreach(['all'=>'All results','win'=>'Wins','draw'=>'Draws','loss'=>'Losses'] as $key=>$label): ?><option value="<?= $key ?>" <?= $options['result']===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<?php foreach(['start'=>'From matchweek','end'=>'Through matchweek'] as $key=>$label): ?><label><?= $label ?><input type="number" name="analysis_<?= $key ?>" min="1" max="100" value="<?= $options[$key] ?>"></label><?php endforeach; ?>
<?php foreach(['win','draw','loss'] as $key): ?><label><?= ucfirst($key) ?> points<input type="number" name="analysis_<?= $key ?>" min="0" max="100" value="<?= $options['points'][$key] ?>"></label><?php endforeach; ?>
<label>Deduct points per season<input type="number" name="analysis_deduction" min="0" max="1000" value="<?= $deduction ?>"></label>
</div>
<p>Classification always uses actual results and standard 3/1/0 scoring. Historical deductions are not reconstructed, as their effective dates are unavailable. Custom points, exclusions and simulated results affect your comparison totals only. Simulated draws use 0–0 and wins/losses use 1–0/0–1 for goal totals. Regular-season completed fixtures are included; future fixtures are excluded.</p>
<button type="submit">Compare seasons</button> <a href="<?= $h(build_tab_url($currentMainTab,$currentLeague,'cross-season')) ?>">Reset rules</a>
<?php if(!$results): ?><p>No season data is available for this league.</p><?php endif; ?>
<div class="analysis-scroll"><table class="analysis-table"><caption><?= $h($team) ?> · points by opponent half before kickoff</caption><thead><tr><th>Season</th><th>Opponent half</th><th>Played</th><th>W</th><th>D</th><th>L</th><th>GF</th><th>GA</th><th>Points</th><th>Points / game</th></tr></thead><tbody>
<?php foreach($results as $season=>$data): foreach(['top-half'=>'Top half','bottom-half'=>'Bottom half','unclassified'=>'Unclassified'] as $key=>$label): $r=$data['summary'][$key]??['played'=>0,'win'=>0,'draw'=>0,'loss'=>0,'gf'=>0,'ga'=>0,'points'=>0]; ?>
<tr><td><?= $h($seasonLabels[$season]??$season) ?></td><th scope="row"><?= $label ?></th><?php foreach(['played','win','draw','loss','gf','ga','points'] as $stat): ?><td><?= $r[$stat] ?></td><?php endforeach; ?><td><?= $r['played']?number_format($r['points']/$r['played'],2):'—' ?></td></tr>
<?php endforeach; $total=array_sum(array_column($data['summary'],'points')); ?><tr><th colspan="8"><?= $h($seasonLabels[$season]??$season) ?> · total after <?= $deduction ?> deduction</th><td><?= $total-$deduction ?></td><td>—</td></tr><?php endforeach; ?>
</tbody></table></div>
<?php foreach($results as $season=>$data): ?>
<details><summary><?= $h($seasonLabels[$season]??$season) ?> · inspect fixtures and adjust results</summary><p>Exclude a result or simulate a win/draw/loss, then compare again. Changing the team or seasons resets fixture adjustments.</p>
<div class="analysis-scroll"><table class="analysis-table"><thead><tr><th>Exclude</th><th>Kickoff</th><th>MW</th><th>Opponent</th><th>Venue</th><th>Pre-match rank</th><th>Half</th><th>Actual score</th><th>Result rule</th><th>Points</th></tr></thead><tbody>
<?php foreach($data['matches'] as $m): ?><tr><td><input type="checkbox" name="analysis_excluded[]" value="<?= (int)$m['id'] ?>" <?= $m['excluded']?'checked':'' ?> aria-label="Exclude <?= $h($m['opponent']) ?> match"></td><td><?= $h(football_stats_format_kickoff($m['match_timestamp']??null,$m['match_date']??null)) ?></td><td><?= (int)$m['matchweek'] ?></td><td><?= $h($m['opponent']) ?></td><td><?= $h($m['venue']) ?></td><td><?= $m['position']??'—' ?></td><td><?= $h($m['half']) ?></td><td><?= (int)$m['home_goals'] ?>–<?= (int)$m['away_goals'] ?> (home–away)</td><td><select name="analysis_outcomes[<?= (int)$m['id'] ?>]" aria-label="Result for <?= $h($m['opponent']) ?>"><option value="">Actual</option><?php foreach(['win','draw','loss'] as $result): ?><option value="<?= $result ?>" <?= ($options['outcomes'][$m['id']]??'')===$result?'selected':'' ?>><?= ucfirst($result) ?></option><?php endforeach; ?></select></td><td><?= $m['excluded']?'Excluded':$m['points'] ?></td></tr><?php endforeach; ?>
<?php if(!$data['matches']): ?><tr><td colspan="10">No completed fixtures match these rules.</td></tr><?php endif; ?>
</tbody></table></div></details>
<?php endforeach; ?>
<button type="submit">Recalculate comparison</button>
</form>
</section>
<style>.analysis-controls{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}.analysis-controls label{display:flex;flex-direction:column;gap:6px}.analysis-controls fieldset label{display:block}.analysis-form select,.analysis-form input[type=number]{padding:8px;background:#202225;color:#eee;border:1px solid #555;border-radius:6px;max-width:100%;box-sizing:border-box}.analysis-form button{padding:10px 16px;background:#5865f2;color:white;border:0;border-radius:6px;margin:10px 0;cursor:pointer}.analysis-scroll{overflow-x:auto}.analysis-table{width:100%;border-collapse:collapse;margin:16px 0;font-size:13px}.analysis-table th,.analysis-table td{padding:9px;text-align:left;border-bottom:1px solid #444}.analysis-table caption{text-align:left;padding:10px;font-weight:bold}.analysis-form details{padding:12px;border:1px solid #444;border-radius:8px;margin:12px 0}.analysis-form summary{cursor:pointer;font-weight:bold}</style>
<script>document.querySelectorAll('[name="analysis_team"], [name="analysis_seasons[]"]').forEach(function(input){input.addEventListener('change',function(){document.querySelectorAll('[name^="analysis_outcomes["]').forEach(function(s){s.value='';});document.querySelectorAll('[name="analysis_excluded[]"]').forEach(function(c){c.checked=false;});});});</script>
