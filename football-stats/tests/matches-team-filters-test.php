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
echo "Matches team filter tests passed.\n";
