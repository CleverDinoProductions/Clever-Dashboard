<?php
require_once __DIR__ . '/../includes/matches-team-filters.php';
function match_filter_assert($expected, $actual, string $message): void {
    if ($expected !== $actual) throw new RuntimeException($message . '\n' . var_export($actual, true));
}
$matches = [
    ['id'=>1, 'home_team'=>'Arsenal FC', 'away_team'=>'Everton'],
    ['id'=>2, 'home_team'=>'Chelsea', 'away_team'=>'Man Utd'],
    ['id'=>3, 'home_team'=>'Everton', 'away_team'=>'Brighton'],
    ['id'=>4, 'home_team'=>'Liverpool', 'away_team'=>'Arsenal'],
];
$context = ['groups'=>clever_team_groups([]), 'group'=>'big-three', 'side'=>'either'];
$ids = static fn($c) => array_column(football_stats_filter_matches_by_team_group($matches, $c), 'id');
match_filter_assert([1,2,4], $ids($context), 'Either team should match group members and aliases.');
match_filter_assert([1,4], $ids(array_replace($context, ['side'=>'home'])), 'Home membership should restrict the home club.');
match_filter_assert([2,4], $ids(array_replace($context, ['side'=>'away'])), 'Away membership should restrict the away club.');
match_filter_assert([4], $ids(array_replace($context, ['side'=>'both'])), 'Both clubs must belong to the group.');
match_filter_assert([1,2,3], $ids(array_replace($context, ['group'=>'outside-big-three'])), 'Outside groups should match individual club membership.');
match_filter_assert([3], $ids(array_replace($context, ['group'=>'outside-big-three', 'side'=>'both'])), 'Both outside excludes fixtures with any group member.');
match_filter_assert([2,3,4], $ids(array_replace($context, ['group'=>'typical-top-half', 'side'=>'away'])), 'Typical top half should match Brighton alias.');
$context['groups']['big_three'] = ['Everton'];
match_filter_assert([1,3], $ids($context), 'Admin changes must affect membership.');
$context['groups']['big_three'] = [];
match_filter_assert([], $ids($context), 'An empty saved group must match no fixtures.');
match_filter_assert([1,2,3,4], $ids(array_replace($context, ['group'=>'all'])), 'All teams must preserve fixtures.');

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
clever_migrate_football_settings($db);
$db->exec('CREATE TABLE matches (id INTEGER PRIMARY KEY, competition_code TEXT, season_label TEXT, matchweek INTEGER, match_date TEXT, match_timestamp TEXT, home_team TEXT, away_team TEXT, home_goals INTEGER, away_goals INTEGER)');
$db->exec("INSERT INTO matches VALUES (1, 'PL', '2025-2026', 1, '2025-08-01', NULL, 'Arsenal', 'Everton', 2, 0), (2, 'PL', '2025-2026', 2, '2025-08-08', NULL, 'Chelsea', 'Liverpool', 0, 3), (3, 'PL', '2024-2025', 1, '2024-08-01', NULL, 'Everton', 'Arsenal', 2, 0), (4, 'PL', '2026-2027', 1, '2026-08-01', NULL, 'Arsenal', 'Everton', NULL, NULL)");
$_GET = ['matches_team_group'=>'current-top-half', 'matches_team_side'=>'home', 'matchweek'=>1, 'custom_win_points'=>0, 'excluded_results'=>'h2', 'outcome_overrides'=>'2-h'];
$c = football_stats_match_group_context($db, 'PL', '2025-2026', []);
match_filter_assert(['Liverpool', 'Arsenal'], $c['groups']['current_top_half'], 'Current groups must use the full actual season and standard points.');
$c = football_stats_match_group_context($db, 'PL', '2024-2025', []);
match_filter_assert(['Everton'], $c['groups']['current_top_half'], 'Historic seasons must use their final standings.');
$c = football_stats_match_group_context($db, 'PL', '2026-2027', []);
match_filter_assert(false, $c['has_standings'], 'Unplayed seasons must disable current halves.');
match_filter_assert([], $c['groups']['current_top_half'], 'Unplayed seasons must not fabricate current standings.');
$_GET['matches_team_group'] = ['bad']; $_GET['matches_team_side'] = 'bad';
$c = football_stats_match_group_context($db, 'PL', '2025-2026', []);
match_filter_assert('all', $c['group'], 'Invalid preset input must fall back to all teams.');
match_filter_assert('either', $c['side'], 'Invalid side input must fall back to either team.');
$_GET = ['matches_team_group'=>'big-nine', 'matches_team_side'=>'both', 'matchweek'=>1];
parse_str(ltrim(football_stats_build_table_view_url('2025-2026', 'premier-league', 'matches', ['snapshot_season'=>'2024-2025']), '?'), $url);
match_filter_assert('big-nine', $url['matches_team_group'], 'Season navigation must preserve group.');
match_filter_assert('both', $url['matches_team_side'], 'Season navigation must preserve side.');
ob_start(); football_stats_render_matches_controls(['2025-2026'], [1,2], '2025-2026', '', '2025-2026', 'premier-league', 'matches', $c); $html = ob_get_clean();
match_filter_assert(true, str_contains($html, 'matches-team-group'), 'Shared controls must render team groups.');
match_filter_assert(true, str_contains($html, 'Outside Big 12'), 'Shared controls must offer outside group presets.');
$_GET = ['matches_standings_basis'=>'matchweek', 'matches_team_group'=>'current-top-half', 'matchweek'=>1];
$c = football_stats_match_group_context($db, 'PL', '2025-2026', []);
match_filter_assert(['Arsenal', 'Chelsea'], $c['groups']['current_top_half'], 'Matchweek reconstruction must exclude later results.');
match_filter_assert('computed', $c['snapshot_source'], 'Missing archives must use reconstruction.');
$_GET['matchweek'] = 2;
$c = football_stats_match_group_context($db, 'PL', '2025-2026', []);
match_filter_assert(['Liverpool', 'Arsenal'], $c['groups']['current_top_half'], 'Changing matchweek must update membership.');
$db->exec('CREATE TABLE league_table_snapshots (competition_code TEXT, season_label TEXT, matchweek INTEGER, team_name TEXT, position INTEGER, played INTEGER)');
$db->exec("INSERT INTO league_table_snapshots VALUES ('PL','2025-2026',1,'Everton',1,1), ('PL','2025-2026',1,'Arsenal',2,1), ('PL','2025-2026',1,'Chelsea',3,0), ('PL','2025-2026',1,'Liverpool',4,0)");
$_GET['matchweek'] = 1;
$c = football_stats_match_group_context($db, 'PL', '2025-2026', []);
match_filter_assert(['Everton', 'Arsenal'], $c['groups']['current_top_half'], 'Archived standings must take precedence over reconstruction.');
match_filter_assert('archive', $c['snapshot_source'], 'Snapshot source should identify archived standings.');
parse_str(ltrim(football_stats_build_table_view_url('2025-2026', 'premier-league', 'matches', ['matchweek'=>2]), '?'), $url);
match_filter_assert('matchweek', $url['matches_standings_basis'], 'Matchweek navigation must preserve snapshot basis.');
unset($_GET['matchweek']);
$c = football_stats_match_group_context($db, 'PL', '2025-2026', []);
match_filter_assert(false, $c['has_standings'], 'All matchweeks must require choosing a snapshot week.');
$_GET['matches_standings_basis'] = 'invalid';
$c = football_stats_match_group_context($db, 'PL', '2025-2026', []);
match_filter_assert('season', $c['basis'], 'Invalid snapshot basis must fall back to full season.');
$c = ['groups'=>clever_team_groups([]), 'group'=>'big-three', 'opponent_group'=>'outside-big-three', 'side'=>'either'];
match_filter_assert([1,2], $ids($c), 'Two groups must match opposite participants in either orientation.');
match_filter_assert([1], $ids(array_replace($c, ['side'=>'home'])), 'Home group must pair with away opponent.');
match_filter_assert([2], $ids(array_replace($c, ['side'=>'away'])), 'Away group must pair with home opponent.');
match_filter_assert([], $ids(array_replace($c, ['side'=>'both'])), 'Disjoint groups cannot satisfy both orientations.');
match_filter_assert([4], $ids(array_replace($c, ['opponent_group'=>'big-three'])), 'The same group on both sides must match two distinct clubs.');
match_filter_assert([4], $ids(array_replace($c, ['opponent_group'=>'big-three', 'side'=>'both'])), 'Overlapping groups must satisfy both orientations.');
match_filter_assert([1,2,4], $ids(array_replace($c, ['group'=>'all', 'opponent_group'=>'big-six'])), 'Opponent-only filtering must work when team group is unrestricted.');
$_GET = ['matches_opponent_group'=>['invalid']];
$c = football_stats_match_group_context($db, 'PL', '2025-2026', []);
match_filter_assert('all', $c['opponent_group'], 'Invalid opponent input must fall back to all.');
$_GET = ['matches_opponent_group'=>'current-bottom-half', 'matches_standings_basis'=>'matchweek', 'matchweek'=>1];
$c = football_stats_match_group_context($db, 'PL', '2025-2026', []);
match_filter_assert([2,4], array_column(football_stats_filter_matches_by_team_group($matches, $c), 'id'), 'Opponent current half must use the chosen archived snapshot.');
parse_str(ltrim(football_stats_build_table_view_url('2025-2026', 'premier-league', 'matches', ['matchweek'=>2, 'snapshot_season'=>'2024-2025']), '?'), $url);
match_filter_assert('current-bottom-half', $url['matches_opponent_group'], 'Navigation must preserve opponent group.');
ob_start(); football_stats_render_match_team_filters($c, '2025-2026', 'premier-league', 'matches'); $html = ob_get_clean();
match_filter_assert(true, str_contains($html, 'matches-opponent-group'), 'Controls must render opponent group.');
echo "Matches team filter tests passed.\n";
