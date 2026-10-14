<?php
$h=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$season=$currentMainTab;
if (is_string($_GET['snapshot_season'] ?? null) && preg_match('/^\d{4}-\d{4}$/D',$_GET['snapshot_season'])) $season=$_GET['snapshot_season'];
$rules=football_stats_get_competition_rules($regionalCode,$season,$db);
$stmt=$db->prepare('SELECT * FROM matches WHERE competition_code=? AND season_label=? AND matchweek>? ORDER BY match_date, match_timestamp, id');
$stmt->execute([$regionalCode,$season,$rules['regular_matchweeks']]);
$playoffMatches=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<section class="panel"><h2><?= $h($regionalName) ?> · Playoffs <?= $h($seasonLabels[$season]??$season) ?></h2>
<p>Playoff fixtures follow the <?= (int)$rules['regular_matchweeks'] ?> regular-season matchweeks. All imported rounds, including eliminators, semi-finals and the final, appear below.</p>
<div style="overflow-x:auto"><table class="league-table"><thead><tr><th>Round</th><th>Kickoff</th><th>Home</th><th>Score</th><th>Away</th><th>Penalties</th><th>Status</th></tr></thead><tbody>
<?php foreach($playoffMatches as $fixture): ?>
<tr><td><?= (int)$fixture['matchweek']-(int)$rules['regular_matchweeks'] ?></td><td><?= $h(football_stats_format_kickoff($fixture['match_timestamp']??null,$fixture['match_date']??null)) ?></td><td><?= $h($fixture['home_team']) ?></td><td><?= is_numeric($fixture['home_goals'])&&is_numeric($fixture['away_goals'])?(int)$fixture['home_goals'].'–'.(int)$fixture['away_goals']:'—' ?></td><td><?= $h($fixture['away_team']) ?></td><td><?= is_numeric($fixture['home_pens']??null)&&is_numeric($fixture['away_pens']??null)?(int)$fixture['home_pens'].'–'.(int)$fixture['away_pens']:'—' ?></td><td><?= $h($fixture['status']??'') ?></td></tr>
<?php endforeach; ?>
<?php if(!$playoffMatches): ?><tr><td colspan="7">No playoff fixtures have been imported for this season.</td></tr><?php endif; ?>
</tbody></table></div></section>
