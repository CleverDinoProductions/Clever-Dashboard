<?php
// Regional block cut lines follow the configured promotion and relegation zones.
$tableView = football_stats_get_table_view_combined($db, $regionalCode, $regionalTable, $currentMainTab);
$rules = football_stats_get_competition_rules($regionalCode, $tableView['active_season_label'], $db);
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$zones = array_column($rules['zones'], null, 'key');
$promotionEnd = (int)($zones['automatic-promotion']['to'] ?? 1);
$playoffEnd = (int)($zones['playoffs']['to'] ?? $promotionEnd);
$relegationStart = (int)($zones['relegation']['from'] ?? ($rules['team_count'] + 1));
$dangerStart = max($playoffEnd + 1, $relegationStart - 4);
$blocks = [
 1=>['name'=>'Automatic promotion','from'=>1,'to'=>$promotionEnd],
 2=>['name'=>'Playoff contenders','from'=>$promotionEnd+1,'to'=>$playoffEnd],
 3=>['name'=>'Mid-table','from'=>$playoffEnd+1,'to'=>$dangerStart-1],
 4=>['name'=>'Relegation battle','from'=>$dangerStart,'to'=>$relegationStart-1],
 5=>['name'=>'Relegation zone','from'=>$relegationStart,'to'=>(int)$rules['team_count']],
];
$selectedBlock = preg_match('/^blocks-([1-5])$/D', $currentSubTab, $match) ? (int)$match[1] : null;
$dynamic = $currentSubTab === 'blocks-dynamic';
?>
<section class="panel">
<h2><?= $h($regionalName) ?> · <?= $h($dynamic ? 'Live Blocks' : ($selectedBlock ? 'Block '.$selectedBlock : 'Blocks Overview')) ?></h2>
<?php football_stats_render_combined_table_controls($tableView, $currentMainTab, $currentLeague, $currentSubTab); ?>
<p>Blocks follow this season’s configured promotion, playoff and relegation places. Points projections use current points per game across <?= (int)$rules['total_games'] ?> games.</p>
<?php foreach ($blocks as $number=>$block): if ($selectedBlock !== null && $selectedBlock !== $number) continue;
$teams=array_values(array_filter($tableView['standings'], static fn($team)=>(int)$team['position'] >= $block['from'] && (int)$team['position'] <= $block['to'])); ?>
<h3><a href="<?= $h(build_tab_url($currentMainTab,$currentLeague,'blocks-'.$number,football_stats_get_current_table_view_params())) ?>">Block <?= $number ?>: <?= $h($block['name']) ?></a> (<?= $block['from'] ?>–<?= $block['to'] ?>)</h3>
<div style="overflow-x:auto"><table class="league-table"><thead><tr><th>Pos</th><th>Team</th><th>P</th><th>W</th><th>D</th><th>L</th><th>GF</th><th>GA</th><th>GD</th><th>Pts</th><th>PPG</th><?php if($dynamic): ?><th>Games left</th><th>Projected points</th><?php endif; ?></tr></thead><tbody>
<?php foreach($teams as $team): $played=(int)$team['played']; $ppg=$played?(float)$team['points']/$played:0; ?>
<tr><td><?= (int)$team['position'] ?></td><td><?= $h($team['team_name']) ?></td><?php foreach(['played','won','drawn','lost','gf','ga','gd','points'] as $stat): ?><td><?= (int)$team[$stat] ?></td><?php endforeach; ?><td><?= number_format($ppg,2) ?></td><?php if($dynamic): ?><td><?= max(0,(int)$rules['total_games']-$played) ?></td><td><?= $played?number_format($ppg*$rules['total_games'],1):'—' ?></td><?php endif; ?></tr>
<?php endforeach; ?>
<?php if(!$teams): ?><tr><td colspan="<?= $dynamic?13:11 ?>">No teams in this block for the selected view.</td></tr><?php endif; ?>
</tbody></table></div>
<?php endforeach; ?>
<?php football_stats_render_table_zone_legend($regionalCode,$tableView['active_season_label']); ?>
</section>
