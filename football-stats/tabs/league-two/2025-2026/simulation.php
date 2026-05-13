<?php
$league_config = [
    'comp_code'    => 'L2',
    'season_label' => $currentMainTab ?? '2025-2026',
    'total_games'  => 46,
    'n_teams'      => 24,
    'league_name'  => 'League Two',
    'zones'        => [
        ['name' => 'Automatic Promotion', 'emoji' => '⬆️', 'from' => 1,  'to' => 3,  'color' => '#43b581', 'bg' => 'rgba(67,181,129,0.12)'],
        ['name' => 'Playoff Zone',        'emoji' => '🎯', 'from' => 4,  'to' => 7,  'color' => '#5865F2', 'bg' => 'rgba(88,101,242,0.10)'],
        ['name' => 'Safe',                'emoji' => '✅', 'from' => 8,  'to' => 23, 'color' => '#555',    'bg' => 'transparent'],
        ['name' => 'Relegated',           'emoji' => '⬇️', 'from' => 24, 'to' => 24, 'color' => '#f04747', 'bg' => 'rgba(240,71,71,0.12)'],
    ],
    'prob_columns' => [
        ['label' => 'P(Auto Up)',    'from' => 1,  'to' => 3,  'color' => '#43b581'],
        ['label' => 'P(Playoffs)',   'from' => 4,  'to' => 7,  'color' => '#5865F2'],
        ['label' => 'P(Any Promo)', 'from' => 1,  'to' => 7,  'color' => '#00ff88'],
        ['label' => 'P(Rel)',        'from' => 24, 'to' => 24, 'color' => '#f04747'],
    ],
];

require_once __DIR__ . '/../../../includes/simulation-view.php';
