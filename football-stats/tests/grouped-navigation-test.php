<?php
require_once __DIR__.'/../includes/table-view.php';
require_once __DIR__.'/../includes/grouped-navigation.php';
function build_tab_url($tab,$league=null,$subtab=null,array $params=[]){return '?'.http_build_query(array_merge(['tab'=>$tab,'league'=>$league,'subtab'=>$subtab],$params));}
function nav_check($condition,$message){if(!$condition)throw new RuntimeException($message);}
$source=file_get_contents(__DIR__.'/../index.php');
$start=strpos($source,'$seasonLeagueConfigs =');$end=strpos($source,'$hasExplicitDestination', $start);
eval(substr($source,$start,$end-$start));
nav_check(football_stats_navigation_league_group('championship')==='EFL [Tiers 2–4]','Championship belongs in EFL.');
nav_check(football_stats_navigation_function('cross-season')==='Comparisons','Cross-season comparison belongs with comparisons.');
nav_check(football_stats_navigation_function('blocks-dynamic')==='Season Blocks','All blocks should be grouped.');
ob_start();football_stats_render_grouped_navigation($seasonLeagueConfigs,$worldCupTabs,'2025-2026','premier-league','team-tracker',['calc_mode'=>'by_date'],['tracker_team'=>'Leeds United'],'42');$html=ob_get_clean();
nav_check(str_contains($html,'EFL')&&str_contains($html,'National League')&&str_contains($html,'Historic Football League'),'Render governing/competition groups.');
nav_check(str_contains($html,'data-storage-scope="42"'),'Scope browser preferences to the current user.');
nav_check(str_contains($html,'data-nav-pin="premier-league/team-tracker"'),'Allow pinning tracker tabs.');
nav_check(str_contains($html,'tracker_team=Leeds+United'),'Preserve tracker context.');
nav_check(str_contains($html,'data-nav-expand')&&str_contains($html,'data-nav-collapse'),'Provide expand/collapse controls.');
preg_match('~<script type="application/json" data-nav-catalog>(.*?)</script>~s',$html,$match);$catalog=json_decode($match[1],true,512,JSON_THROW_ON_ERROR);
foreach($seasonLeagueConfigs['2025-2026'] as $league=>$config)foreach($config['tabs'] as $key=>$tab)nav_check(isset($catalog[$league.'/'.$key]),'Every tab must remain reachable and pinnable.');
nav_check($catalog['premier-league/team-tracker']['active'],'Mark the selected tab active.');
ob_start();football_stats_render_grouped_navigation($seasonLeagueConfigs,$worldCupTabs,'world-cup',null,'simulation',[],[],'guest');$html=ob_get_clean();
nav_check(str_contains($html,'Simulations &amp; Predictions')&&str_contains($html,'data-nav-pin="world-cup/simulation"'),'World Cup gets function groups and pin controls.');
echo "Grouped navigation tests passed.\n";
