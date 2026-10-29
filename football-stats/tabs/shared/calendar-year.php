<?php
require_once __DIR__ . '/../../includes/table-view.php';
require_once __DIR__ . '/../../includes/calendar-year.php';
require_once dirname(__DIR__, 3) . '/lib/english-pyramid.php';
$competition = clever_regional_league_codes()[$currentLeague] ?? ['premier-league'=>'PL','championship'=>'ELC','league-one'=>'L1','league-two'=>'L2','national-league'=>'NL','division-one'=>'D1'][$currentLeague];
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$currentYear = (int)(new DateTimeImmutable('now', new DateTimeZone('Europe/London')))->format('Y');
$stmt = $db->prepare("SELECT DISTINCT substr(match_date,1,4) FROM matches WHERE competition_code=? AND match_date IS NOT NULL ORDER BY 1 DESC");
$stmt->execute([$competition]);
$years = [$currentYear];
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $value) if (is_string($value) && preg_match('/^[1-9][0-9]{3}$/D', $value)) $years[] = (int)$value;
$years = array_values(array_unique($years)); rsort($years);
$requestedYear = is_scalar($_GET['calendar_year'] ?? null) ? filter_var($_GET['calendar_year'], FILTER_VALIDATE_INT) : false;
$year = in_array($requestedYear, $years, true) ? $requestedYear : $currentYear;
$stmt = $db->prepare('SELECT * FROM matches WHERE competition_code=? AND match_date>=? AND match_date<? ORDER BY match_date,id');
$stmt->execute([$competition, $year.'-01-01', ($year+1).'-01-01']);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC); $rulesBySeason = [];
foreach (array_unique(array_column($matches, 'season_label')) as $season) {
    $rulesBySeason[$season] = ['final_matchweek'=>football_stats_get_final_matchweek($competition,$season,$db), 'points'=>football_stats_get_result_points($competition,$season,$db)];
}
$data = football_stats_calendar_year($matches, $year, $rulesBySeason);
$leagueLabel = $seasonLeagueConfigs[$currentMainTab][$currentLeague]['label'] ?? $currentLeague;
?>
<section class="panel own-table calendar-year">
<h2>Annual Table · <?= $h($leagueLabel) ?> <?= $year ?></h2>
<p>Results from <strong>1 January to 31 December <?= $year ?></strong>, combined across seasons. Includes every club with a completed regular-season match in this competition during the year, including promoted and relegated clubs.</p>
<form method="get" class="own-controls">
<input type="hidden" name="tab" value="<?= $h($currentMainTab) ?>"><input type="hidden" name="league" value="<?= $h($currentLeague) ?>"><input type="hidden" name="subtab" value="calendar-year">
<label>Calendar year <select name="calendar_year" style="padding:9px;background:#202225;color:#eee;border:1px solid #666;border-radius:5px"><?php foreach($years as $option): ?><option value="<?= $option ?>" <?= $year===$option?'selected':'' ?>><?= $option ?></option><?php endforeach; ?></select></label><button type="submit">Show</button>
</form>
<p><?= count($data['matches']) ?> completed matches · <?= count($data['rows']) ?> clubs. Based on imported results only; missing seasons may leave the annual table incomplete. Uses each season’s configured points rules, without season-wide deductions. Playoffs and unplayed fixtures are excluded.</p>
<?php if(!$data['rows']): ?><p>No completed league matches have been imported for <?= $year ?>.</p><?php else: ?>
<div class="own-scroll"><table><caption><?= $h($leagueLabel) ?> · calendar year <?= $year ?></caption><thead><tr><th scope="col">#</th><th scope="col">Club</th><th scope="col">Matches</th><th scope="col">W</th><th scope="col">D</th><th scope="col">L</th><th scope="col">Goals</th><th scope="col">GD</th><th scope="col">Pts</th><th scope="col">Points per match</th></tr></thead><tbody>
<?php foreach($data['rows'] as $index=>$row): ?><tr><td><?= $index+1 ?></td><th scope="row"><?= $h($row['club']) ?></th><td><?= $row['played'] ?></td><td><?= $row['won'] ?></td><td><?= $row['drawn'] ?></td><td><?= $row['lost'] ?></td><td><?= $row['gf'] ?>:<?= $row['ga'] ?></td><td><?= $row['gd'] ?></td><td><strong><?= $row['points'] ?></strong></td><td><?= number_format($row['ppg'],2) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<p>Ranked by points, goal difference, goals scored, then club name.</p>
<details><summary>View counted matches (<?= count($data['matches']) ?>)</summary><div class="own-scroll"><table><thead><tr><th>Date</th><th>Season</th><th>Home</th><th>Score</th><th>Away</th></tr></thead><tbody><?php foreach($data['matches'] as $match): ?><tr><td><?= $h($match['match_date']) ?></td><td><?= $h($match['season_label']) ?></td><td><?= $h($match['home_team']) ?></td><td><?= (int)$match['home_goals'] ?> : <?= (int)$match['away_goals'] ?></td><td><?= $h($match['away_team']) ?></td></tr><?php endforeach; ?></tbody></table></div></details>
<?php endif; ?>
</section>
<link rel="stylesheet" href="assets/own-table.css">
