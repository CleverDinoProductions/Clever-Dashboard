<?php
/** Team-group filtering for league match lists. */
require_once __DIR__ . '/table-view.php';

function football_stats_match_group_labels(): array
{
    return [
        'all' => 'All teams',
        'big-three' => 'Big 3', 'big-six' => 'Big 6', 'big-nine' => 'Big 9', 'big-twelve' => 'Big 12',
        'outside-big-three' => 'Outside Big 3', 'outside-big-six' => 'Outside Big 6',
        'outside-big-nine' => 'Outside Big 9', 'outside-big-twelve' => 'Outside Big 12',
        'typical-top-half' => 'Typical top half', 'outside-typical-top-half' => 'Outside typical top half',
        'current-top-half' => 'Current top half', 'current-bottom-half' => 'Current bottom half',
    ];
}

function football_stats_normalize_group_team(string $name): string
{
    $name = strtolower(trim($name));
    $name = preg_replace('/\s+(?:football club|fc)$/i', '', $name);
    return [
        'tottenham' => 'tottenham hotspur', 'spurs' => 'tottenham hotspur',
        'man united' => 'manchester united', 'man utd' => 'manchester united', 'man city' => 'manchester city',
        'leeds' => 'leeds united', 'newcastle' => 'newcastle united', 'palace' => 'crystal palace',
        'brighton' => 'brighton & hove albion', 'brighton and hove albion' => 'brighton & hove albion',
        'west ham' => 'west ham united',
    ][$name] ?? $name;
}

/** Standings always describe the entire selected season, independent of matchweek and custom rules. */
function football_stats_match_group_context(PDO $db, string $competition, string $season, array $settings): array
{
    $groups = clever_team_groups($settings);
    $standings = football_stats_apply_points_deductions(
        football_stats_compute_custom_match_standings($db, $competition, $season, 'league_table_' . $competition, [], [], ['win' => 3, 'draw' => 1, 'loss' => 0]),
        football_stats_get_points_deductions($db, $competition, $season)
    );
    $hasStandings = array_sum(array_column($standings, 'played')) > 0;
    $half = (int)floor(count($standings) / 2);
    $groups['current_top_half'] = $hasStandings ? array_column(array_slice($standings, 0, $half), 'team_name') : [];
    $groups['current_bottom_half'] = $hasStandings ? array_column(array_slice($standings, $half), 'team_name') : [];
    $group = isset($_GET['matches_team_group']) && is_string($_GET['matches_team_group']) ? $_GET['matches_team_group'] : 'all';
    if (!array_key_exists($group, football_stats_match_group_labels())) $group = 'all';
    $side = isset($_GET['matches_team_side']) && is_string($_GET['matches_team_side']) ? $_GET['matches_team_side'] : 'either';
    if (!in_array($side, ['either', 'home', 'away', 'both'], true)) $side = 'either';
    return ['groups' => $groups, 'group' => $group, 'side' => $side, 'has_standings' => $hasStandings];
}

function football_stats_filter_matches_by_team_group(array $matches, array $context): array
{
    $preset = $context['group'];
    if ($preset === 'all') return $matches;
    $outside = str_starts_with($preset, 'outside-');
    $key = str_replace('-', '_', $outside ? substr($preset, 8) : $preset);
    if (!array_key_exists($key, $context['groups'])) return [];
    $names = array_fill_keys(array_map('football_stats_normalize_group_team', $context['groups'][$key]), true);
    $isMember = static function (string $team) use ($names, $outside): bool {
        $member = isset($names[football_stats_normalize_group_team($team)]);
        return $outside ? !$member : $member;
    };
    return array_values(array_filter($matches, static function (array $match) use ($isMember, $context): bool {
        $home = $isMember($match['home_team']);
        $away = $isMember($match['away_team']);
        return match ($context['side']) {
            'home' => $home, 'away' => $away, 'both' => $home && $away, default => $home || $away,
        };
    }));
}

function football_stats_render_match_team_filters(array $context, $tab, $league, $subtab): void
{
    $h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    ?>
    <div class="table-view-group">
        <label class="table-view-label" for="matches-team-group">Team group</label>
        <select id="matches-team-group" class="table-view-select" onchange="window.location.href=this.value;">
            <?php foreach (football_stats_match_group_labels() as $key => $label): ?>
            <option value="<?= $h(football_stats_build_table_view_url($tab, $league, $subtab, ['matches_team_group' => $key === 'all' ? null : $key])) ?>" <?= $context['group'] === $key ? 'selected' : '' ?> <?= str_starts_with($key, 'current-') && !$context['has_standings'] ? 'disabled' : '' ?>><?= $h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="table-view-group">
        <label class="table-view-label" for="matches-team-side">Group applies to</label>
        <select id="matches-team-side" class="table-view-select" onchange="window.location.href=this.value;">
            <?php foreach (['either' => 'Either team', 'home' => 'Home team', 'away' => 'Away team', 'both' => 'Both teams'] as $key => $label): ?>
            <option value="<?= $h(football_stats_build_table_view_url($tab, $league, $subtab, ['matches_team_side' => $key === 'either' ? null : $key, 'matches_team_group' => $context['group'] === 'all' ? null : $context['group']])) ?>" <?= $context['side'] === $key ? 'selected' : '' ?>><?= $h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <p style="flex-basis:100%;color:#b9bbbe;font-size:12px;margin:4px 0;">Club groups and typical top half use Admin team lists. Current half uses this season’s actual current or final standings, across all matchweeks.<?= !$context['has_standings'] ? ' Current halves are unavailable until matches have been played.' : '' ?></p>
    <?php
}
