<?php
require_once __DIR__.'/../includes/nations-league.php';
function nl_check(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
function build_tab_url($tab, $league = null, $subtab = null, array $params = []): string {
    return '?'.http_build_query(array_merge(['tab'=>$tab, 'league'=>$league, 'subtab'=>$subtab], $params));
}
$default = ['tournament'=>'2026-2027','league'=>'league-a','subtab'=>'groups'];
nl_check(football_stats_nations_league_route([]) === $default, 'Default route');
nl_check(football_stats_nations_league_route(['tournament'=>[], 'league'=>[], 'subtab'=>[]]) === $default, 'Malformed input falls back safely');
nl_check(football_stats_nations_league_route(['tournament'=>'bad','league'=>'bad','subtab'=>'bad']) === $default, 'Unknown route falls back safely');
foreach (football_stats_nations_league_editions() as $edition=>$label) {
    foreach (['league-a','league-b','league-c','league-d'] as $league) {
        foreach (football_stats_nations_league_tabs($edition, $league) as $view=>$tab) {
            $route = ['tournament'=>$edition, 'league'=>$league, 'subtab'=>$view];
            nl_check(football_stats_nations_league_route($route) === $route, 'Every valid route survives selection');
            ob_start(); football_stats_render_nations_league_navigation($edition, $league, $view); $html = ob_get_clean();
            nl_check(str_contains($html, 'value="'.$edition.'" selected'), 'Selected edition is visible');
            preg_match_all('/href="([^"]+)"/', $html, $links);
            foreach ($links[1] as $link) {
                parse_str(parse_url(html_entity_decode($link), PHP_URL_QUERY), $query);
                nl_check($query['tournament'] === $edition && $query['tab'] === 'nations-league', 'Links retain competition and edition');
                nl_check(football_stats_nations_league_route($query) == array_intersect_key($query, $route), 'Links use valid league views');
            }
            nl_check(substr_count($html, 'aria-current="page"') === 1, 'One selected view');
        }
        nl_check(isset(football_stats_nations_league_tabs($edition, $league)['finals']) === ($league === 'league-a'), 'Finals only in League A');
    }
}
nl_check(!isset(football_stats_nations_league_tabs('2018-2019', 'league-b')['playoffs']), 'No inaugural play-offs');
nl_check(isset(football_stats_nations_league_tabs('2022-2023', 'league-c')['playoffs']), 'Historic League C play-outs');
nl_check(football_stats_nations_league_route(['tournament'=>'2018-2019','league'=>'league-b','subtab'=>'playoffs'])['subtab'] === 'groups', 'Unavailable view falls back when changing edition');

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
function nl_render(string $edition, string $league, string $view): string {
    global $db;
    $currentTournament = $edition; $currentLeague = $league; $currentSubTab = $view;
    ob_start(); include __DIR__.'/../tabs/nations-league/view.php'; return ob_get_clean();
}
nl_check(str_contains(nl_render('2024-2025','league-a','groups'), 'No groups'), 'Missing tables render empty state');
$db->exec(file_get_contents(__DIR__.'/../tabs/nations-league/schema.sql'));
$db->exec("INSERT INTO nl_standings (tournament,league,group_name,position,team_name) VALUES ('2024-2025','league-a','A1',1,'Example <team>')");
$db->exec("INSERT INTO nl_matches (id,tournament,league,stage,round_name,home_team,away_team,home_score,away_score,status) VALUES ('one','2024-2025','league-a','finals','Final','Home','Away',0,0,'Finished'), ('two','2024-2025','league-a','groups','Group A1','Other','Visitor',NULL,NULL,'Scheduled')");
nl_check(str_contains(nl_render('2024-2025','league-a','groups'), 'Example &lt;team&gt;'), 'Escape imported names');
nl_check(!str_contains(nl_render('2026-2027','league-a','groups'), 'Example'), 'Never mix editions');
nl_check(!str_contains(nl_render('2024-2025','league-b','groups'), 'Example'), 'Never mix leagues');
$finals = nl_render('2024-2025','league-a','finals');
nl_check(str_contains($finals, '0–0') && !str_contains($finals, 'Visitor'), 'Finals filter stage and preserve zero scores');
$matches = nl_render('2024-2025','league-a','matches');
nl_check(str_contains($matches, 'Visitor') && str_contains($matches, '—'), 'Fixtures include unplayed matches');
nl_check(str_contains(nl_render('2018-2019','league-a','format'), 'inaugural'), 'Historic rules are edition-aware');
echo "Nations League tests passed.\n";
