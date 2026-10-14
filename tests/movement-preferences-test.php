<?php
$path=tempnam(sys_get_temp_dir(),'movement-');putenv('CLEVER_ACCOUNTS_DB='.$path);
require_once dirname(__DIR__).'/lib/auth.php';
require_once dirname(__DIR__).'/football-stats/includes/table-view.php';
function movement_check($expected,$actual,$message){if($expected!==$actual)throw new RuntimeException($message);}
try {
$db=clever_accounts_db();$db->exec("INSERT INTO users(id,username,email,password_hash) VALUES(1,'one','one@example.test','test'),(2,'two','two@example.test','test')");
clever_save_movement_preferences(1,['movement_mode'=>'by_date','movement_compare'=>'completed','movement_style'=>'badge','movement_direction'=>'reverse']);
clever_save_movement_preferences(1,['movement_mode'=>'by_matchweek','movement_compare'=>'relevant','movement_style'=>'compact','movement_direction'=>'forward']);
$GLOBALS['dashboardMovementPreferences']=clever_movement_preferences(1);
movement_check('reverse',$GLOBALS['dashboardMovementPreferences']['by_date']['direction'],'Persist date direction.');
movement_check([],clever_movement_preferences(2),'Account settings must be isolated.');
$_GET=['calc_mode'=>'by_date'];
$view=football_stats_apply_movement_preference(['calc_mode'=>'by_date','completed_standings'=>[['team_name'=>'Leeds','position'=>2]]],[['team_name'=>'Leeds','position'=>1]],[]);
movement_check(-1,$view['position_movements']['Leeds'],'Reverse comparison must invert arrow movement.');
movement_check('badge',$view['movement_style'],'Restore saved style.');
$_GET=['calc_mode'=>'by_matchweek'];movement_check('forward',football_stats_movement_settings()['direction'],'Modes retain separate directions.');
$_GET=['calc_mode'=>'by_date','movement_direction'=>'forward'];movement_check('forward',football_stats_movement_settings()['direction'],'Explicit view override remains possible.');
try{clever_save_movement_preferences(1,['movement_mode'=>'bad','movement_compare'=>'completed','movement_style'=>'badge','movement_direction'=>'reverse']);throw new RuntimeException('Invalid mode accepted.');}catch(InvalidArgumentException $expected){}
movement_check(2,count(clever_movement_preferences(1)),'Invalid input cannot overwrite saved settings.');
echo "Account movement preference tests passed.\n";
} finally { unlink($path); }
