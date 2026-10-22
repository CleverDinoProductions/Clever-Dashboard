<?php

declare(strict_types=1);

/** Regional divisions in tiers 6–8 (FA steps 2–4). */
function clever_regional_competitions(): array
{
    return [
        'national-league-north' => ['code' => 'NLN', 'label' => 'National League North', 'tier' => 6],
        'national-league-south' => ['code' => 'NLS', 'label' => 'National League South', 'tier' => 6],
        'northern-premier-league' => ['code' => 'NPL', 'label' => 'Northern Premier League Premier Division', 'tier' => 7],
        'southern-league-central' => ['code' => 'SLPC', 'label' => 'Southern League Premier Central', 'tier' => 7],
        'southern-league-south' => ['code' => 'SLPS', 'label' => 'Southern League Premier South', 'tier' => 7],
        'isthmian-league-premier' => ['code' => 'ILP', 'label' => 'Isthmian League Premier Division', 'tier' => 7],
        'northern-premier-east' => ['code' => 'NPLE', 'label' => 'Northern Premier League Division One East', 'tier' => 8],
        'northern-premier-midlands' => ['code' => 'NPLM', 'label' => 'Northern Premier League Division One Midlands', 'tier' => 8],
        'northern-premier-west' => ['code' => 'NPLW', 'label' => 'Northern Premier League Division One West', 'tier' => 8],
        'southern-league-central-one' => ['code' => 'SLDC', 'label' => 'Southern League Division One Central', 'tier' => 8],
        'southern-league-south-one' => ['code' => 'SLDS', 'label' => 'Southern League Division One South', 'tier' => 8],
        'isthmian-league-north' => ['code' => 'ILN', 'label' => 'Isthmian League North Division', 'tier' => 8],
        'isthmian-league-south-central' => ['code' => 'ILSC', 'label' => 'Isthmian League South Central Division', 'tier' => 8],
        'isthmian-league-south-east' => ['code' => 'ILSE', 'label' => 'Isthmian League South East Division', 'tier' => 8],
    ];
}

function clever_regional_league_codes(): array
{
    return array_map(static fn(array $league): string => $league['code'], clever_regional_competitions());
}

/** Match explicit provider aliases so Premier and Division One stay separate. */
function clever_regional_provider_code(array $league): ?string
{
    if (($league['strSport'] ?? '') !== 'Soccer') return null;
    $aliases = [
        'NPL' => ['Northern Premier League'],
        'ILP' => ['Isthmian Premier League'],
        'SLPC' => ['Southern Premier League Central Division', 'Southern Football League Division Central'],
        'SLPS' => ['Southern Premier League South Division', 'Southern Football League Division South'],
    ];
    $names = array_merge([$league['strLeague'] ?? ''], explode(';', $league['strLeagueAlternate'] ?? ''));
    foreach ($names as $name) {
        $normalized = strtolower(preg_replace('/^English /i', '', trim($name)));
        foreach (clever_regional_competitions() as $regional) {
            $accepted = array_merge([$regional['label']], $aliases[$regional['code']] ?? []);
            if (in_array($normalized, array_map('strtolower', $accepted), true)) return $regional['code'];
        }
    }
    return null;
}
