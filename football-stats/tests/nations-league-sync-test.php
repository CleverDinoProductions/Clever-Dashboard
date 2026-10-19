<?php
require_once __DIR__.'/../includes/nations-league-sync.php';
require_once __DIR__.'/../includes/sportsdb-config.php';
function sync_check(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
function sync_event(string $id, string $home, string $away, array $extra = []): array {
    return array_replace(['idEvent'=>$id,'idLeague'=>'4490','strSeason'=>'2024-2025','strHomeTeam'=>$home,'strAwayTeam'=>$away,'intRound'=>'1','intHomeScore'=>'0','intAwayScore'=>'0','strStatus'=>'Match Finished','dateEvent'=>'2024-09-05','strTime'=>'18:45:00'], $extra);
}
$previousKey = getenv('THESPORTSDB_API_KEY');
putenv('THESPORTSDB_API_KEY');
sync_check(football_stats_sportsdb_api_key() !== '', 'Read the existing key without running the English importer');
putenv('THESPORTSDB_API_KEY=test-override');
sync_check(football_stats_sportsdb_api_key() === 'test-override' && football_stats_sportsdb_api_key('legacy') === 'test-override', 'Both fetchers share the environment override');
putenv($previousKey === false ? 'THESPORTSDB_API_KEY' : 'THESPORTSDB_API_KEY='.$previousKey);
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$groups = football_stats_nations_groups('2024-2025');
foreach (football_stats_nations_league_editions() as $edition=>$label) {
    $all = array_merge(...array_values(football_stats_nations_groups($edition)));
    sync_check(count($all) === count(array_unique($all)), 'Each team belongs to one group');
    sync_check(count($all) === ($edition <= '2020-2021' ? 55 : 54), 'Edition membership reflects suspensions');
}
sync_check(football_stats_nations_event(sync_event('bad','Portugal','Croatia',['idLeague'=>'4328']), '2024-2025',$groups) === null, 'Reject other competitions');
sync_check(football_stats_nations_event(sync_event('bad','Portugal','Croatia',['strSeason'=>'2022-2023']), '2024-2025',$groups) === null, 'Reject other editions');
sync_check(football_stats_nations_event(sync_event('bad','Unknown','Croatia'), '2024-2025',$groups) === null, 'Never guess unknown team allocation');
sync_check(football_stats_nations_event(sync_event('alias','N.Ireland','Belarus'), '2024-2025',$groups)['league'] === 'league-c', 'Normalize provider aliases');
sync_check(football_stats_nations_event(sync_event('alias','Türkiye','Wales'), '2024-2025',$groups)['stage'] === 'groups', 'Normalize country names');
$events = [
    sync_event('group','Portugal','Croatia'),
    sync_event('scheduled','Scotland','Poland',['intHomeScore'=>'','intAwayScore'=>null,'strStatus'=>'Not Started']),
    sync_event('live','Portugal','Poland',['strStatus'=>'2H','intHomeScore'=>'4','intAwayScore'=>'0']),
    sync_event('postponed','Croatia','Scotland',['strStatus'=>'Postponed','intHomeScore'=>'5','intAwayScore'=>'0']),
    sync_event('cancelled','Poland','Scotland',['strStatus'=>'Cancelled']),
    sync_event('final','Portugal','Spain',['intRound'=>'125','intHomeScore'=>'2','intAwayScore'=>'2','intHomeScorePen'=>'5','intAwayScorePen'=>'3']),
    sync_event('playoff','Scotland','Greece',['intRound'=>'0','intHomeScore'=>'0','intAwayScore'=>'1']),
    sync_event('quarter','Germany','Italy',['intRound'=>'160']),
];
$request = static function($key,$endpoint,$params) use ($events) {
    sync_check($key === 'test-key' && $endpoint === 'eventsseason.php' && $params === ['id'=>'4490','s'=>'2024-2025'], 'Request the correct provider competition and season');
    return ['events'=>$events];
};
$result = football_stats_sync_nations_league($db,'test-key',['2024-2025'],$request);
sync_check($result['2024-2025']['matches'] === 8, 'Import fixtures and all stages');
sync_check((int)$db->query('SELECT count(*) FROM nl_matches')->fetchColumn() === 8, 'Store each event once');
sync_check((int)$db->query("SELECT count(*) FROM nl_match_leagues WHERE match_id = 'tsdb:2024-2025:playoff'")->fetchColumn() === 2, 'Cross-league playoffs link both leagues');
sync_check($db->query("SELECT stage FROM nl_matches WHERE id = 'tsdb:2024-2025:quarter'")->fetchColumn() === 'finals', 'Quarter-finals belong to Finals');
sync_check((int)$db->query("SELECT home_pens FROM nl_matches WHERE id = 'tsdb:2024-2025:final'")->fetchColumn() === 5, 'Preserve penalty shootout scores');
$portugal = $db->query("SELECT * FROM nl_standings WHERE team_name = 'Portugal'")->fetch(PDO::FETCH_ASSOC);
sync_check((int)$portugal['played'] === 1 && (int)$portugal['points'] === 1, 'Standings exclude live, postponed, cancelled and knockout matches');
sync_check($db->query("SELECT home_score FROM nl_matches WHERE id = 'tsdb:2024-2025:scheduled'")->fetchColumn() === null, 'Empty scores remain null');
football_stats_nations_import($db,'2024-2025',[sync_event('group','Portugal','Croatia',['intHomeScore'=>'2','intAwayScore'=>'1'])]);
sync_check((int)$db->query('SELECT count(*) FROM nl_matches')->fetchColumn() === 8, 'Partial responses retain existing fixtures');
sync_check((int)$db->query("SELECT points FROM nl_standings WHERE team_name = 'Portugal'")->fetchColumn() === 3, 'Corrections rebuild standings without double counting');
foreach ([null, [], ['events'=>null], ['events'=>[]], ['events'=>[['idEvent'=>'invalid']]]] as $badResponse) {
    $failed = football_stats_sync_nations_league($db,'test-key',['2024-2025'],static fn()=> $badResponse);
    sync_check(isset($failed['2024-2025']['error']), 'Report unavailable or unrecognised responses');
    sync_check((int)$db->query('SELECT count(*) FROM nl_matches')->fetchColumn() === 8, 'Failed fetches preserve cache');
}
// Transaction failure must not expose half an updated tournament.
$db->exec("CREATE TRIGGER reject_match BEFORE INSERT ON nl_matches WHEN NEW.id LIKE '%:rollback' BEGIN SELECT RAISE(ABORT, 'test'); END");
try {
    football_stats_nations_import($db,'2024-2025',[sync_event('new','France','Italy'),sync_event('rollback','Portugal','Croatia')]);
    throw new RuntimeException('Expected rollback');
} catch (PDOException $exception) {
    sync_check((int)$db->query('SELECT count(*) FROM nl_matches')->fetchColumn() === 8, 'Roll back the entire failed import');
}
// Head-to-head takes priority over a better overall goal difference.
$matches = [['home_team'=>'A','away_team'=>'B','home_score'=>1,'away_score'=>0,'status'=>'Finished']];
$stats = football_stats_nations_totals(['A','B'],$matches); $stats['B']['gd'] = 20;
sync_check(football_stats_nations_order(['B','A'],$matches,$stats,'2024-2025') === ['A','B'], 'Use head-to-head before overall goal difference');
// Upgrade the schema produced by the original navigation PR.
$legacy = new PDO('sqlite::memory:'); $legacy->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$legacy->exec('CREATE TABLE nl_matches (id TEXT PRIMARY KEY,tournament TEXT,league TEXT,stage TEXT,round_name TEXT,match_date TEXT,home_team TEXT,away_team TEXT,home_score INTEGER,away_score INTEGER,status TEXT)');
football_stats_nations_import($legacy,'2024-2025',[sync_event('upgrade','Portugal','Croatia')]);
sync_check(in_array('home_pens',array_column($legacy->query('PRAGMA table_info(nl_matches)')->fetchAll(PDO::FETCH_ASSOC),'name'),true), 'Upgrade existing installations');
// Render imported data through the actual tabs, including the lower league of a play-off.
football_stats_nations_import($db,'2024-2025',[sync_event('cd','Latvia','Gibraltar',['intRound'=>'0'])]);
function sync_render(string $league, string $view): string {
    global $db;
    $currentTournament = '2024-2025'; $currentLeague = $league; $currentSubTab = $view;
    ob_start(); include __DIR__.'/../tabs/nations-league/view.php'; return ob_get_clean();
}
foreach (['league-a','league-b'] as $league) sync_check(str_contains(sync_render($league,'playoffs'),'Greece'), 'Render a cross-league play-off in both tabs');
sync_check(str_contains(sync_render('league-d','playoffs'),'Latvia'), 'League D can see its C/D play-offs');
$finalsHtml = sync_render('league-a','finals');
sync_check(str_contains($finalsHtml,'pens 5–3') && str_contains($finalsHtml,'Last synced:'), 'Render shootouts and sync metadata');
echo "Nations League sync tests passed.\n";
