<?php
require_once __DIR__.'/../includes/cross-season-analysis.php';
function check_analysis($expected,$actual,$message) { if($expected!==$actual) throw new RuntimeException($message.' '.var_export($actual,true)); }
function fixture($id,$date,$home,$away,$hg,$ag,$week=1) { return ['id'=>$id,'match_date'=>substr($date,0,10),'match_timestamp'=>$date,'home_team'=>$home,'away_team'=>$away,'home_goals'=>$hg,'away_goals'=>$ag,'matchweek'=>$week]; }
$options=['venue'=>'all','result'=>'all','start'=>1,'end'=>100,'opponent_group'=>'all','points'=>['win'=>3,'draw'=>1,'loss'=>0],'outcomes'=>[],'excluded'=>[]];
$fixtures=[fixture(1,'2025-08-01T12:00:00Z','Leeds United','Arsenal',0,2),fixture(2,'2025-08-01T12:00:00Z','Everton','Chelsea',1,0),fixture(3,'2025-08-08T12:00:00Z','Leeds United','Chelsea',2,0,2),fixture(4,'2025-08-15T12:00:00Z','Arsenal','Leeds United',2,1,3),fixture(5,'2025-08-22T12:00:00Z','Leeds United','Everton',null,null,4)];
$r=football_stats_cross_season_analysis($fixtures,'Leeds',$options,clever_team_groups([]));
check_analysis(['unclassified','bottom-half','top-half'],array_column($r['matches'],'half'),'Use rankings before each fixture, without target-result leakage.');
check_analysis(3,$r['summary']['bottom-half']['points'],'Bottom half points.');
check_analysis(0,$r['summary']['top-half']['points'],'Top half points.');
check_analysis(3,count($r['matches']),'Unplayed fixtures excluded.');
$custom=$options; $custom['points']['win']=5;$custom['outcomes'][4]='win';$custom['excluded']=[3];
$c=football_stats_cross_season_analysis($fixtures,'Leeds',$custom,clever_team_groups([]));
check_analysis(array_column($r['matches'],'half'),array_column($c['matches'],'half'),'Custom outcomes cannot change historical classification.');
check_analysis(5,$c['summary']['top-half']['points'],'Custom scoring and outcome applied.');
check_analysis(false,isset($c['summary']['bottom-half']),'Excluded results do not count.');
$filtered=$options;$filtered['venue']='away';$filtered['opponent_group']='big-three';
$f=football_stats_cross_season_analysis($fixtures,'Leeds',$filtered,clever_team_groups([]));
check_analysis([4],array_column($f['matches'],'id'),'Venue and configured opponent group combine.');
$filtered=$options;$filtered['opponent_group']='current-top-half';
check_analysis([4],array_column(football_stats_cross_season_analysis($fixtures,'Leeds',$filtered,clever_team_groups([]))['matches'],'id'),'Half filter uses pre-match groups.');
$tie=[fixture(1,'2025-08-01','Leeds','Arsenal',0,0),fixture(2,'2025-08-01','Everton','Chelsea',0,0),fixture(3,'2025-08-08','Leeds','Everton',2,0,2)];
check_analysis('unclassified',football_stats_cross_season_analysis($tie,'Leeds',$options,[])['matches'][1]['half'],'Tie spanning boundary must be unclassified.');
$other=[fixture(10,'2026-08-01','Leeds','Chelsea',1,0)];
check_analysis('unclassified',football_stats_cross_season_analysis($other,'Leeds',$options,[])['matches'][0]['half'],'Each season starts with a fresh baseline.');
$overlap=[fixture(1,'2025-08-01T12:00:00Z','Arsenal','Chelsea',3,0),fixture(2,'2025-08-01T13:00:00Z','Leeds','Everton',1,0)];
check_analysis('unclassified',football_stats_cross_season_analysis($overlap,'Leeds',$options,[])['matches'][0]['half'],'Overlapping matches must not enter pre-kickoff standings.');
// Render the actual tab against an isolated two-season database.
$db=new PDO('sqlite::memory:');
$db->exec('CREATE TABLE matches (id INTEGER PRIMARY KEY,competition_code TEXT,season_label TEXT,matchweek INTEGER,match_date TEXT,match_timestamp TEXT,home_team TEXT,away_team TEXT,home_goals INTEGER,away_goals INTEGER)');
$db->exec("INSERT INTO matches VALUES (1,'PL','2025-2026',1,'2025-08-01',NULL,'Leeds United','Arsenal',0,1),(2,'PL','2026-2027',1,'2026-08-01',NULL,'Leeds United','Arsenal',1,0)");
clever_migrate_football_settings($db);
function build_tab_url($tab,$league,$subtab) { return '?'.http_build_query(compact('tab','league','subtab')); }
$currentLeague='premier-league';$currentMainTab='2025-2026';$seasonLabels=['2025-2026'=>'2025/26','2026-2027'=>'2026/27'];$dashboardSettings=[];$_GET=[];
ob_start();include __DIR__.'/../tabs/shared/cross-season.php';$html=ob_get_clean();
check_analysis(true,str_contains($html,'2025/26')&&str_contains($html,'2026/27'),'Render both season comparisons.');
check_analysis(true,str_contains($html,'Leeds United'),'Default to Leeds when present.');
check_analysis(true,str_contains($html,'analysis_outcomes[1]'),'Render custom fixture outcome controls.');
echo "Cross-season analysis tests passed.\n";
