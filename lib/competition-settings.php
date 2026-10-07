<?php

declare(strict_types=1);

function clever_competitions(): array
{
    return ['PL' => 'Premier League', 'ELC' => 'Championship', 'L1' => 'League One', 'L2' => 'League Two', 'NL' => 'National League', 'D1' => 'Division One'];
}

function clever_competition_zone_types(): array
{
    return ['champions-league' => 'Champions League', 'europa-league' => 'Europa League',
        'conference-league' => 'Conference League', 'automatic-promotion' => 'Automatic promotion',
        'playoffs' => 'Promotion playoffs', 'relegation' => 'Relegation', 'custom' => 'Other'];
}

function clever_competition_numeric_fields(): array
{
    return ['team_count' => ['Clubs', 2, 100], 'regular_matchweeks' => ['Regular-season matchweeks', 4, 200],
        'total_games' => ['Games per club', 4, 200], 'halfway_games' => ['First-half games', 1, 199],
        'safety_target_halfway' => ['Safety points at halfway', 0, 600],
        'comparison_target_one' => ['First comparison points target', 0, 600],
        'comparison_target_two' => ['Second comparison points target', 0, 600]];
}

/** Bundled rules remain the fallback; database overrides never modify source files. */
function clever_bundled_competition_rules(string $code, ?string $season = null): array
{
    static $bundled;
    $bundled ??= require dirname(__DIR__) . '/football-stats/config/competition-rules.php';
    $rules = array_replace($bundled['default'], $bundled[$code]['default'] ?? []);
    $shortSeason = in_array($code, ['PL', 'D1'], true);
    $rules += ['total_games' => $rules['regular_matchweeks'], 'halfway_games' => (int)floor($rules['regular_matchweeks'] / 2),
        'safety_target_halfway' => in_array($code, ['L1', 'L2', 'NL'], true) ? 25 : 20,
        'comparison_target_one' => $shortSeason || $code === 'ELC' ? 38 : 46, 'comparison_target_two' => 40,
        'quarter_boundaries' => $shortSeason ? [10, 19, 29] : [12, 23, 35]];
    return array_replace($rules, $season === null ? [] : ($bundled[$code][$season] ?? []));
}

function clever_competition_rules(PDO $db, string $code, ?string $season = null): array
{
    $rules = clever_bundled_competition_rules($code);
    // Readers support older/read-only databases before the Admin migration runs.
    if (!$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='competition_rule_overrides'")->fetchColumn()) {
        return clever_bundled_competition_rules($code, $season);
    }
    $stmt = $db->prepare('SELECT season_label, rules_json FROM competition_rule_overrides WHERE competition_code=? AND season_label IN (?, ?)');
    $stmt->execute([$code, '', $season ?? '']);
    $overrides = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $overrides[$row['season_label']] = json_decode($row['rules_json'], true, 512, JSON_THROW_ON_ERROR);
    $rules = array_replace($rules, $overrides[''] ?? []);
    if ($season !== null) {
        $bundled = require dirname(__DIR__) . '/football-stats/config/competition-rules.php';
        $rules = array_replace($rules, $bundled[$code][$season] ?? [], $overrides[$season] ?? []);
    }
    return $rules;
}

function clever_validate_competition_identity(string $code, string $season): void
{
    if (!isset(clever_competitions()[$code])) throw new InvalidArgumentException('Choose a supported competition.');
    if ($season !== '' && (!preg_match('/^(\d{4})-(\d{4})$/D', $season, $matches) || (int)$matches[2] !== (int)$matches[1] + 1)) {
        throw new InvalidArgumentException('Use a consecutive season such as 2026-2027, or leave it blank for league defaults.');
    }
}

function clever_validate_competition_rules(array $input): array
{
    $rules = [];
    foreach (clever_competition_numeric_fields() as $key => [$label, $min, $max]) {
        $value = filter_var($input[$key] ?? null, FILTER_VALIDATE_INT);
        if ($value === false || $value === null || $value < $min || $value > $max) throw new InvalidArgumentException("$label must be a whole number from $min to $max.");
        $rules[$key] = $value;
    }
    if ($rules['halfway_games'] >= min($rules['total_games'], $rules['regular_matchweeks'])) throw new InvalidArgumentException('First-half games must fall inside the regular season and be less than games per club.');
    if ($rules['safety_target_halfway'] > 3 * $rules['halfway_games']) throw new InvalidArgumentException('Halfway safety points cannot exceed three points per first-half game.');
    foreach (['comparison_target_one', 'comparison_target_two'] as $key) {
        if ($rules[$key] > 3 * $rules['total_games']) throw new InvalidArgumentException('Comparison targets cannot exceed the maximum season points.');
    }
    $quarters = array_map('trim', explode(',', (string)($input['quarter_boundaries'] ?? '')));
    if (count($quarters) !== 3) throw new InvalidArgumentException('Enter three quarter boundaries separated by commas.');
    $previous = 0;
    foreach ($quarters as $quarter) {
        $value = filter_var($quarter, FILTER_VALIDATE_INT);
        if ($value === false || $value <= $previous || $value >= $rules['total_games'] || $value >= $rules['regular_matchweeks']) throw new InvalidArgumentException('Quarter boundaries must increase and fall inside the regular season.');
        $rules['quarter_boundaries'][] = $value;
        $previous = $value;
    }
    $rules['zones'] = [];
    $occupied = [];
    $zones = $input['zones'] ?? [];
    if (!is_array($zones) || count($zones) > 100) throw new InvalidArgumentException('Supply at most 100 position zones.');
    foreach ($zones as $zone) {
        if (!is_array($zone)) throw new InvalidArgumentException('Invalid position zone.');
        $key = trim((string)($zone['key'] ?? ''));
        if ($key === '') continue;
        if (!isset(clever_competition_zone_types()[$key])) throw new InvalidArgumentException('Choose a supported zone type.');
        $label = trim((string)($zone['label'] ?? ''));
        if ($label === '' || strlen($label) > 100 || preg_match('/[\x00-\x1f\x7f]/', $label)) throw new InvalidArgumentException('Each zone needs a label of at most 100 characters.');
        $from = filter_var($zone['from'] ?? null, FILTER_VALIDATE_INT);
        $to = filter_var($zone['to'] ?? null, FILTER_VALIDATE_INT);
        if (!$from || !$to || $from < 1 || $to < $from || $to > $rules['team_count']) throw new InvalidArgumentException('Zone positions must run from 1 to the club count, with the end at or after the start.');
        for ($position = $from; $position <= $to; $position++) {
            if (isset($occupied[$position])) throw new InvalidArgumentException('Position zones cannot overlap.');
            $occupied[$position] = true;
        }
        $color = strtoupper(trim((string)($zone['color'] ?? '')));
        if (!preg_match('/^#[0-9A-F]{6}$/D', $color)) throw new InvalidArgumentException('Each zone colour must be a six-digit hex colour.');
        $rules['zones'][] = compact('key', 'label', 'from', 'to', 'color');
    }
    usort($rules['zones'], static fn($a, $b) => $a['from'] <=> $b['from']);
    return $rules;
}

function clever_save_competition_rules(PDO $db, string $code, string $season, array $input): void
{
    clever_validate_competition_identity($code, $season);
    $rules = clever_validate_competition_rules($input);
    $stmt = $db->prepare('INSERT INTO competition_rule_overrides (competition_code, season_label, rules_json) VALUES (?, ?, ?) ON CONFLICT(competition_code, season_label) DO UPDATE SET rules_json=excluded.rules_json');
    $stmt->execute([$code, $season, json_encode($rules, JSON_THROW_ON_ERROR)]);
}

function clever_reset_competition_rules(PDO $db, string $code, string $season): void
{
    clever_validate_competition_identity($code, $season);
    $db->prepare('DELETE FROM competition_rule_overrides WHERE competition_code=? AND season_label=?')->execute([$code, $season]);
}
