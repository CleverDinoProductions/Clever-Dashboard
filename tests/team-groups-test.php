<?php
require_once dirname(__DIR__) . '/lib/football-settings.php';
function group_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$db = new PDO('sqlite::memory:');
clever_migrate_football_settings($db);
$defaults = clever_team_groups(clever_dashboard_settings($db));
group_assert(array_map('count', $defaults) === ['big_three'=>3, 'big_six'=>6, 'big_nine'=>9, 'big_twelve'=>12, 'typical_top_half'=>10], 'Default group sizes should be preserved.');
group_assert(in_array('Blackburn Rovers', $defaults['big_twelve'], true), 'Default Big 12 should contain Blackburn.');
clever_save_team_groups($db, ['big_six'=>"Brighton, Arsenal\narsenal", 'big_three'=>'Liverpool', 'big_nine'=>'Leeds', 'typical_top_half'=>'Brighton', 'big_twelve'=>'']);
$groups = clever_team_groups(clever_dashboard_settings($db));
group_assert(array_diff_assoc(array_map('json_encode', $groups), array_map('json_encode', ['big_six'=>['Brighton', 'arsenal'], 'big_three'=>['Liverpool'], 'big_nine'=>['Leeds'], 'typical_top_half'=>['Brighton'], 'big_twelve'=>[]])) === [], 'Saved groups should support commas, newlines, duplicate removal and empty lists.');
try {
    clever_save_team_groups($db, ['big_six'=>'Chelsea', 'big_three'=>'Liverpool', 'big_nine'=>'Villa', 'typical_top_half'=>'Brighton', 'big_twelve'=>str_repeat('x', 101)]);
    throw new RuntimeException('An overlong team name should be rejected.');
} catch (InvalidArgumentException $expected) {}
group_assert(clever_team_groups(clever_dashboard_settings($db)) === $groups, 'Invalid edits must not partially update groups.');
try {
    clever_save_team_groups($db, ['big_six'=>'Chelsea']);
    throw new RuntimeException('Missing groups should be rejected.');
} catch (InvalidArgumentException $expected) {}
group_assert(clever_team_groups(clever_dashboard_settings($db)) === $groups, 'Missing groups must preserve saved settings.');
echo "Configurable team group tests passed.\n";
