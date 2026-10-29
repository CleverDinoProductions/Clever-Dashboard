<?php
require_once __DIR__ . '/../includes/own-table.php';
function own_check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function fixture($home, $away, $hg, $ag, $date) { return ['home_team'=>$home,'away_team'=>$away,'home_goals'=>$hg,'away_goals'=>$ag,'match_date'=>$date]; }
$matches = [fixture('A','B',3,2,'2025-08-01'),fixture('B','A',1,1,'2025-09-01'),fixture('A','C',9,0,'2025-08-10'),fixture('A','B',null,null,'2025-10-01')];
$data = football_stats_own_table($matches, ['A','B','D']);
own_check(count($data['matches']) === 2, 'Count only scored fixtures between selected clubs');
own_check($data['rows'][0]['club']==='A' && $data['rows'][0]['points']===4 && $data['rows'][0]['ppg']===2.0, 'Calculate points and PPG');
own_check($data['rows'][0]['gf']===4 && $data['rows'][0]['ga']===3 && $data['rows'][0]['gd']===1, 'Goal totals');
own_check($data['rows'][1]['lost']===1 && $data['rows'][1]['drawn']===1, 'Both perspectives');
own_check($data['rows'][2]['played']===0, 'Retain unplayed selected clubs');
$data = football_stats_own_table($matches, ['A','B'], '2025-09-01', '2025-09-01');
own_check(count($data['matches'])===1 && $data['rows'][0]['points']===1, 'Inclusive date boundaries');
own_check(football_stats_own_table([], []) === ['rows'=>[], 'matches'=>[]], 'Empty selection');
own_check(football_stats_own_table_date('2025-02-30')==='' && football_stats_own_table_date(['bad'])==='' && football_stats_own_table_date('2025-08-01')==='2025-08-01', 'Validate date input');
$data = football_stats_own_table($matches, ['A','B'], '', '', ['win'=>2,'draw'=>1,'loss'=>0]);
own_check($data['rows'][0]['points']===3, 'Use competition scoring');
require_once __DIR__ . '/../includes/table-view.php';
require_once dirname(__DIR__,2).'/lib/english-pyramid.php';
$source=file_get_contents(__DIR__.'/../index.php');
$start=strpos($source,'$seasonLeagueConfigs ='); $end=strpos($source,'$hasExplicitDestination',$start);
eval(substr($source,$start,$end-$start));
foreach($seasonLeagueConfigs as $leagues) foreach($leagues as $config) own_check(($config['tabs']['own-table']['file']??'')==='tabs/shared/own-table.php','Own Table available in every English league');
$db = new PDO('sqlite::memory:');
$db->exec('CREATE TABLE matches (id INTEGER PRIMARY KEY, competition_code TEXT, season_label TEXT, matchweek INTEGER, home_team TEXT, away_team TEXT, home_goals INTEGER, away_goals INTEGER, match_date TEXT)');
$db->exec("INSERT INTO matches VALUES (1,'PL','2025-2026',1,'A','B',3,2,'2025-08-01')");
$currentMainTab='2025-2026'; $currentLeague='premier-league';
function build_tab_url($tab,$league,$subtab) { return '?'.http_build_query(compact('tab','league','subtab')); }
$_GET=['own_clubs'=>['A','B','<script>'],'own_sort'=>'club','own_direction'=>'asc'];
ob_start(); include __DIR__.'/../tabs/shared/own-table.php'; $html=ob_get_clean();
own_check(str_contains($html,'1 matches counted') && str_contains($html,'3 : 2') && !str_contains($html,'<script>'), 'Render real selected results safely');
echo "Own Table tests passed.\n";
