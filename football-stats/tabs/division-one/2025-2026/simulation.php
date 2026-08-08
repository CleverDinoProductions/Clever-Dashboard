<?php
$league_config = [
    'comp_code'    => 'PL',
    'season_label' => $currentMainTab ?? '2025-2026',
    'total_games'  => 38,
    'halfway_games' => 19,
    'n_teams'      => 20,
    'league_name'  => 'Division One',
    'zones'        => [
        ['name' => 'Champion',          'emoji' => '🏆', 'from' => 1,  'to' => 1,  'color' => '#FFD700', 'bg' => 'rgba(255,215,0,0.12)'],
        ['name' => 'Champions League',  'emoji' => '🌍', 'from' => 1,  'to' => 4,  'color' => '#43b581', 'bg' => 'rgba(67,181,129,0.10)'],
        ['name' => 'Europa League',     'emoji' => '⚽', 'from' => 5,  'to' => 6,  'color' => '#5865F2', 'bg' => 'rgba(88,101,242,0.10)'],
        ['name' => 'Conference League', 'emoji' => '🏅', 'from' => 7,  'to' => 7,  'color' => '#FFCD00', 'bg' => 'rgba(255,205,0,0.08)'],
        ['name' => 'Safe',              'emoji' => '✅', 'from' => 1,  'to' => 17, 'color' => '#555',    'bg' => 'transparent'],
        ['name' => 'Relegated',         'emoji' => '⬇️', 'from' => 18, 'to' => 20, 'color' => '#f04747', 'bg' => 'rgba(240,71,71,0.12)'],
    ],
    'prob_columns' => [
        ['label' => 'P(🏆 Title)',  'from' => 1,  'to' => 1,  'color' => '#FFD700'],
        ['label' => 'P(Top 4 CL)', 'from' => 1,  'to' => 4,  'color' => '#43b581'],
        ['label' => 'P(Europe)',    'from' => 1,  'to' => 7,  'color' => '#5865F2'],
        ['label' => 'P(Rel)',       'from' => 18, 'to' => 20, 'color' => '#f04747'],
    ],
];

require_once __DIR__ . '/../../../includes/simulation-view.php';
