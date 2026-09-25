<?php

/**
 * Rules which change by competition and season.  Keep these out of the views:
 * the number of clubs (and therefore matchweeks/cut lines) is not universal.
 */
return [
    'default' => [
        'team_count' => 24,
        'regular_matchweeks' => 46,
        'zones' => [],
    ],
    'D1' => [
        'default' => ['team_count' => 20, 'regular_matchweeks' => 38, 'zones' => []],
    ],
    'PL' => [
        'default' => ['team_count' => 20, 'regular_matchweeks' => 38, 'zones' => [
            ['key' => 'champions-league', 'label' => 'Champions League', 'from' => 1, 'to' => 4],
            ['key' => 'europa-league', 'label' => 'Europa League', 'from' => 5, 'to' => 5],
            ['key' => 'conference-league', 'label' => 'Conference League', 'from' => 6, 'to' => 6],
            ['key' => 'relegation', 'label' => 'Relegation', 'from' => 18, 'to' => 20],
        ]],
        // The first three Premier League seasons had 22 clubs. European
        // qualification and relegation places reflect each season's format.
        '1992-1993' => ['team_count' => 22, 'regular_matchweeks' => 42, 'zones' => [
            ['key' => 'champions-league', 'label' => 'Champions League', 'from' => 1, 'to' => 1],
            ['key' => 'europa-league', 'label' => 'UEFA Cup', 'from' => 2, 'to' => 3],
            ['key' => 'relegation', 'label' => 'Relegation', 'from' => 20, 'to' => 22],
        ]],
        '1993-1994' => ['team_count' => 22, 'regular_matchweeks' => 42, 'zones' => [
            ['key' => 'champions-league', 'label' => 'Champions League', 'from' => 1, 'to' => 1],
            ['key' => 'europa-league', 'label' => 'UEFA Cup', 'from' => 2, 'to' => 3],
            ['key' => 'relegation', 'label' => 'Relegation', 'from' => 20, 'to' => 22],
        ]],
        '1994-1995' => ['team_count' => 22, 'regular_matchweeks' => 42, 'zones' => [
            ['key' => 'champions-league', 'label' => 'Champions League', 'from' => 1, 'to' => 1],
            ['key' => 'europa-league', 'label' => 'UEFA Cup', 'from' => 2, 'to' => 5],
            ['key' => 'relegation', 'label' => 'Relegation', 'from' => 19, 'to' => 22],
        ]],
        // England earned a fifth Champions League place for 2025/26.
        '2025-2026' => ['zones' => [
            ['key' => 'champions-league', 'label' => 'Champions League', 'from' => 1, 'to' => 5],
            ['key' => 'europa-league', 'label' => 'Europa League', 'from' => 6, 'to' => 6],
            ['key' => 'conference-league', 'label' => 'Conference League', 'from' => 7, 'to' => 7],
            ['key' => 'relegation', 'label' => 'Relegation', 'from' => 18, 'to' => 20],
        ]],
    ],
    'ELC' => ['default' => ['team_count' => 24, 'regular_matchweeks' => 46, 'zones' => [
        ['key' => 'automatic-promotion', 'label' => 'Automatic promotion', 'from' => 1, 'to' => 2],
        ['key' => 'playoffs', 'label' => 'Promotion playoffs', 'from' => 3, 'to' => 6],
        ['key' => 'relegation', 'label' => 'Relegation', 'from' => 22, 'to' => 24],
    ]]],
    'L1' => ['default' => ['team_count' => 24, 'regular_matchweeks' => 46, 'zones' => [
        ['key' => 'automatic-promotion', 'label' => 'Automatic promotion', 'from' => 1, 'to' => 2],
        ['key' => 'playoffs', 'label' => 'Promotion playoffs', 'from' => 3, 'to' => 6],
        ['key' => 'relegation', 'label' => 'Relegation', 'from' => 21, 'to' => 24],
    ]]],
    'L2' => ['default' => ['team_count' => 24, 'regular_matchweeks' => 46, 'zones' => [
        ['key' => 'automatic-promotion', 'label' => 'Automatic promotion', 'from' => 1, 'to' => 3],
        ['key' => 'playoffs', 'label' => 'Promotion playoffs', 'from' => 4, 'to' => 7],
        ['key' => 'relegation', 'label' => 'Relegation', 'from' => 24, 'to' => 24],
    ]]],
    'NL' => ['default' => ['team_count' => 24, 'regular_matchweeks' => 46, 'zones' => [
        ['key' => 'automatic-promotion', 'label' => 'Automatic promotion', 'from' => 1, 'to' => 1],
        ['key' => 'playoffs', 'label' => 'Promotion playoffs', 'from' => 2, 'to' => 7],
        ['key' => 'relegation', 'label' => 'Relegation', 'from' => 21, 'to' => 24],
    ]]],
];
