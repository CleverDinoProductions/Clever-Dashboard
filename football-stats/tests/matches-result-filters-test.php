<?php
require_once __DIR__.'/../includes/matches-team-filters.php';
function check_result_filter($expected,$actual,$message){if($expected!==$actual)throw new RuntimeException($message.' '.var_export($actual,true));}
$matches=[
 ['id'=>1,'home_team'=>'Leeds United','away_team'=>'Arsenal','home_goals'=>2,'away_goals'=>0,'matchweek'=>1],
 ['id'=>2,'home_team'=>'Arsenal FC','away_team'=>'Leeds','home_goals'=>1,'away_goals'=>1,'matchweek'=>2],
 ['id'=>3,'home_team'=>'Leeds','away_team'=>'Chelsea','home_goals'=>0,'away_goals'=>1,'matchweek'=>3],
 ['id'=>4,'home_team'=>'Everton','away_team'=>'Arsenal','home_goals'=>null,'away_goals'=>null,'matchweek'=>3],
];
$filter=football_stats_matches_result_filter_request(['mf_mode'=>'only','mf_teams'=>['Leeds'],'mf_opponents'=>['Arsenal FC'],'mf_result'=>'win']);
$ids=static fn($f)=>array_column(football_stats_filter_match_results($matches,$f),'id');
check_result_filter([1],$ids($filter),'Win must be evaluated for selected team against selected opponent.');
check_result_filter([2,3,4],$ids(array_replace($filter,['mode'=>'exclude'])),'Exclude must invert whole-fixture matching.');
check_result_filter([1,2],$ids(array_replace($filter,['result'=>'all'])),'Match both orientations.');
check_result_filter([2],$ids(array_replace($filter,['result'=>'draw','venue'=>'away'])),'Venue and draw perspective combine.');
check_result_filter([],$ids(array_replace($filter,['result'=>'all','teams'=>['Arsenal'],'opponents'=>['Arsenal']])),'One club cannot satisfy both team and opponent fields.');
check_result_filter([1,2,3],$ids(array_replace($filter,['result'=>'all','opponents'=>['Arsenal','Chelsea']])),'Match any selected opponent.');
check_result_filter([3],$ids(array_replace($filter,['result'=>'all','opponents'=>[],'week'=>3])),'Matchweek combines with team.');
check_result_filter([1,2,3,4],$ids(football_stats_matches_result_filter_request(['mf_mode'=>'only'])),'All fields unrestricted must include future fixtures.');
check_result_filter([1,3],$ids(football_stats_matches_result_filter_request(['mf_mode'=>'only','mf_result'=>'win'])),'Future fixtures cannot match played outcomes.');
check_result_filter([],$ids(football_stats_matches_result_filter_request(['mf_mode'=>'exclude'])),'Excluding unrestricted matching results removes everything.');
check_result_filter('',football_stats_matches_result_filter_request(['mf_mode'=>['bad']])['mode'],'Reject malformed mode.');
$_GET=['mf_mode'=>'only','mf_teams'=>['Leeds','Chelsea'],'mf_opponents'=>['Arsenal'],'mf_week'=>2,'mf_venue'=>'away','mf_result'=>'draw'];
parse_str(ltrim(football_stats_build_table_view_url('2025-2026','premier-league','matches',['snapshot_season'=>'2026-2027']),'?'),$url);
check_result_filter(['Leeds','Chelsea'],$url['mf_teams'],'Navigation preserves multi-team values.');
check_result_filter('draw',$url['mf_result'],'Navigation preserves outcome.');
$context=['groups'=>clever_team_groups([]),'roster'=>['Leeds','Arsenal','Chelsea'],'weeks'=>[1,2,3],'season'=>'2025-2026','has_standings'=>false,'basis'=>'season','matchweek'=>null];
ob_start();football_stats_render_matches_result_filters($context,'2025-2026','premier-league','matches');$html=ob_get_clean();
check_result_filter(true,str_contains($html,'Include only matching results')&&str_contains($html,'Include everything but matching results'),'Render both screenshot panels.');
check_result_filter(true,str_contains($html,'data-matches-team-search')&&str_contains($html,'Outside Big 9'),'Render search and group presets.');
echo "Matches result filters tests passed.\n";
