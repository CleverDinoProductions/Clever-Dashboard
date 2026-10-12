<?php
/** Team-group filtering for league match lists. */
require_once __DIR__ . '/table-view.php';
require_once __DIR__ . '/matches-result-filters.php';

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

/** Resolve full-season or selected-matchweek standings independently of custom rules. */
function football_stats_match_group_context(PDO $db, string $competition, string $season, array $settings): array
{
    $groups = clever_team_groups($settings);
    $basis = ($_GET['matches_standings_basis'] ?? null) === 'matchweek' ? 'matchweek' : 'season';
    $week = isset($_GET['matchweek']) && is_scalar($_GET['matchweek']) ? filter_var($_GET['matchweek'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
    $week = $week === false ? null : $week;
    $throughWeek = $basis === 'matchweek' ? $week : null;
    $snapshotSource = 'computed';
    $standings = football_stats_apply_points_deductions(
        football_stats_compute_custom_match_standings($db, $competition, $season, 'league_table_' . $competition, [], [], ['win' => 3, 'draw' => 1, 'loss' => 0], $throughWeek),
        football_stats_get_points_deductions($db, $competition, $season)
    );
    if ($basis === 'matchweek') {
        if ($week === null) {
            $standings = [];
        } else {
            // An archived snapshot preserves the standings and deductions known at that time.
            try {
                $snapshot = $db->prepare('SELECT * FROM league_table_snapshots WHERE competition_code = ? AND season_label = ? AND matchweek = ? ORDER BY position ASC');
                $snapshot->execute([$competition, $season, $week]);
                $archived = $snapshot->fetchAll(PDO::FETCH_ASSOC);
                if ($archived && count($archived) === count($standings) && isset($archived[0]['played'])) {
                    $standings = $archived;
                    $snapshotSource = 'archive';
                }
            } catch (PDOException $exception) {
                // Older databases may not have archived snapshots; reconstruct from results.
            }
        }
    }
    $hasStandings = array_sum(array_column($standings, 'played')) > 0;
    $half = (int)floor(count($standings) / 2);
    $groups['current_top_half'] = $hasStandings ? array_column(array_slice($standings, 0, $half), 'team_name') : [];
    $groups['current_bottom_half'] = $hasStandings ? array_column(array_slice($standings, $half), 'team_name') : [];
    $group = isset($_GET['matches_team_group']) && is_string($_GET['matches_team_group']) ? $_GET['matches_team_group'] : 'all';
    if (!array_key_exists($group, football_stats_match_group_labels())) $group = 'all';
    $opponent = isset($_GET['matches_opponent_group']) && is_string($_GET['matches_opponent_group']) ? $_GET['matches_opponent_group'] : 'all';
    if (!array_key_exists($opponent, football_stats_match_group_labels())) $opponent = 'all';
    $side = isset($_GET['matches_team_side']) && is_string($_GET['matches_team_side']) ? $_GET['matches_team_side'] : 'either';
    if (!in_array($side, ['either', 'home', 'away', 'both'], true)) $side = 'either';
    $roster = football_stats_get_match_roster($db, $competition, $season);
    $weeksQuery = $db->prepare('SELECT DISTINCT matchweek FROM matches WHERE competition_code=? AND season_label=? AND matchweek>=1 ORDER BY matchweek');
    $weeksQuery->execute([$competition,$season]);
    $weeks = $weeksQuery->fetchAll(PDO::FETCH_COLUMN);
    return ['roster'=>$roster, 'weeks'=>$weeks, 'season'=>$season, 'groups' => $groups, 'group' => $group, 'opponent_group' => $opponent, 'side' => $side, 'has_standings' => $hasStandings, 'basis' => $basis, 'matchweek' => $week, 'snapshot_source' => $snapshotSource];
}

function football_stats_filter_matches_by_team_group(array $matches, array $context): array
{
    $resultFilter = football_stats_matches_result_filter_request($_GET);
    if ($resultFilter['mode']) return football_stats_filter_match_results($matches, $resultFilter);
    $matcher = static function (string $preset) use ($context): callable {
        if ($preset === 'all') return static fn(string $team): bool => true;
        $outside = str_starts_with($preset, 'outside-');
        $key = str_replace('-', '_', $outside ? substr($preset, 8) : $preset);
        if (!array_key_exists($key, $context['groups'])) return static fn(string $team): bool => false;
        $names = array_fill_keys(array_map('football_stats_normalize_group_team', $context['groups'][$key]), true);
        return static function (string $team) use ($names, $outside): bool {
            $member = isset($names[football_stats_normalize_group_team($team)]);
            return $outside ? !$member : $member;
        };
    };
    $teamMatches = $matcher($context['group']);
    $opponentMatches = $matcher($context['opponent_group'] ?? 'all');
    return array_values(array_filter($matches, static function (array $match) use ($teamMatches, $opponentMatches, $context): bool {
        // Keep each team's opponent on the other side of the same fixture.
        $homeOrientation = $teamMatches($match['home_team']) && $opponentMatches($match['away_team']);
        $awayOrientation = $teamMatches($match['away_team']) && $opponentMatches($match['home_team']);
        return match ($context['side']) {
            'home' => $homeOrientation, 'away' => $awayOrientation,
            'both' => $homeOrientation && $awayOrientation,
            default => $homeOrientation || $awayOrientation,
        };
    }));
}

function football_stats_render_match_team_filters(array $context, $tab, $league, $subtab): void
{
    if (isset($context['roster'])) { football_stats_render_matches_result_filters($context, $tab, $league, $subtab); return; }
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
        <label class="table-view-label" for="matches-opponent-group">Opponent team group</label>
        <select id="matches-opponent-group" class="table-view-select" onchange="window.location.href=this.value;">
            <?php foreach (football_stats_match_group_labels() as $key => $label): ?>
            <option value="<?= $h(football_stats_build_table_view_url($tab, $league, $subtab, ['matches_opponent_group' => $key === 'all' ? null : $key])) ?>" <?= ($context['opponent_group'] ?? 'all') === $key ? 'selected' : '' ?> <?= str_starts_with($key, 'current-') && !$context['has_standings'] ? 'disabled' : '' ?>><?= $h($key === 'all' ? 'All opponents' : $label) ?></option>
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
    <div class="table-view-group">
        <label class="table-view-label" for="matches-standings-basis">Current-half standings</label>
        <select id="matches-standings-basis" class="table-view-select" onchange="window.location.href=this.value;">
            <?php foreach (['season' => 'Current / final season table', 'matchweek' => 'After selected matchweek'] as $key => $label): ?>
            <option value="<?= $h(football_stats_build_table_view_url($tab, $league, $subtab, ['matches_standings_basis' => $key === 'season' ? null : $key])) ?>" <?= $context['basis'] === $key ? 'selected' : '' ?>><?= $h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <p style="flex-basis:100%;color:#b9bbbe;font-size:12px;margin:4px 0;">Team group matches one club and opponent group matches the other. Either team accepts either home/away orientation; Home or Away fixes the team group’s side; Both teams requires both orientations to match. Club groups and typical top half use Admin team lists.
        <?php if ($context['basis'] === 'matchweek'): ?>
            <?php if ($context['matchweek'] === null): ?>Choose a matchweek to use a current-half snapshot.
            <?php elseif ($context['snapshot_source'] === 'archive'): ?>Current halves use the archived standings after MW<?= (int)$context['matchweek'] ?>.
            <?php else: ?>Current halves are reconstructed from completed results in MW1–<?= (int)$context['matchweek'] ?>, using standard points and recorded season deductions. Rearranged fixtures are grouped by matchweek, not their actual play date.
            <?php endif; ?>
        <?php else: ?>Current halves use this season’s actual current or final standings, across all matchweeks.<?php endif; ?>
        <?= !$context['has_standings'] && $context['matchweek'] !== null ? ' Current halves are unavailable until this period has completed matches.' : '' ?>
    </p>
    <?php
}
