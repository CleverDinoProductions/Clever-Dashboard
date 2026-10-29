<?php
require_once __DIR__.'/../includes/calendar-year.php';
function annual_check($condition,$message) { if (!$condition) throw new RuntimeException($message); }
function annual_match($home,$away,$hg,$ag,$date,$season='2025-2026',$week=1) { return ['home_team'=>$home,'away_team'=>$away,'home_goals'=>$hg,'away_goals'=>$ag,'match_date'=>$date,'season_label'=>$season,'matchweek'=>$week]; }
$matches=[
annual_match('A','Relegated',2,0,'2026-01-01'),
annual_match('Promoted','A',1,1,'2026-12-31','2026-2027'),
annual_match('A','Relegated',8,0,'2025-12-31'),
annual_match('Promoted','A',8,0,'2027-01-01','2026-2027'),
annual_match('Future','A',null,null,'2026-11-01','2026-2027'),
annual_match('Playoff','A',9,0,'2026-05-01','2025-2026',39),
annual_match('Unknown','A',1,0,null),
];
$rules=['2025-2026'=>['final_matchweek'=>38,'points'=>['win'=>2,'draw'=>1,'loss'=>0]],'2026-2027'=>['final_matchweek'=>38,'points'=>['win'=>3,'draw'=>1,'loss'=>0]]];
$data=football_stats_calendar_year($matches,2026,$rules);
annual_check(count($data['matches'])===2,'Inclusive year boundaries across seasons; exclude playoffs, unknown dates and unplayed fixtures');
annual_check(count($data['rows'])===3,'Include promoted and relegated clubs');
annual_check($data['rows'][0]['club']==='A' && $data['rows'][0]['played']===2 && $data['rows'][0]['points']===3,'Merge club totals using each season scoring');
annual_check($data['rows'][0]['gf']===3 && $data['rows'][0]['ga']===1 && $data['rows'][0]['ppg']===1.5,'Correct goals and PPG');
annual_check(football_stats_calendar_year($matches,2024,$rules)===['rows'=>[],'matches'=>[]],'Empty year');
require_once __DIR__.'/../includes/table-view.php';
require_once dirname(__DIR__,2).'/lib/english-pyramid.php';
$source=file_get_contents(__DIR__.'/../index.php'); $start=strpos($source,'$seasonLeagueConfigs ='); $end=strpos($source,'$hasExplicitDestination',$start); eval(substr($source,$start,$end-$start));
foreach($seasonLeagueConfigs as $leagues) foreach($leagues as $config) annual_check(($config['tabs']['calendar-year']['file']??'')==='tabs/shared/calendar-year.php','Every English league has annual table');
$db=new PDO('sqlite::memory:');
$db->exec('CREATE TABLE matches (id INTEGER PRIMARY KEY,competition_code TEXT,season_label TEXT,matchweek INTEGER,home_team TEXT,away_team TEXT,home_goals INTEGER,away_goals INTEGER,match_date TEXT)');
$db->exec("INSERT INTO matches VALUES (1,'PL','2025-2026',1,'A','B',2,0,'2026-01-01'),(2,'PL','2026-2027',1,'C','A',1,1,'2026-12-31'),(3,'ELC','2026-2027',1,'Other','League',9,0,'2026-10-01')");
$currentMainTab='2025-2026'; $currentLeague='premier-league'; $_GET=['calendar_year'=>'2026'];
ob_start(); include __DIR__.'/../tabs/shared/calendar-year.php'; $html=ob_get_clean();
annual_check(str_contains($html,'2 completed matches · 3 clubs') && !str_contains($html,'>Other<'),'Rendering crosses seasons and isolates competition');
$_GET=['calendar_year'=>['bad']]; ob_start(); include __DIR__.'/../tabs/shared/calendar-year.php'; ob_end_clean();
echo "Calendar Year tests passed.\n";
