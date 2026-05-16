<?php

if (!function_exists('sim_poisson_random')) {
    function sim_poisson_random($lambda) {
        if ($lambda <= 0) return 0;
        if ($lambda > 20) return (int) round($lambda);
        $L = exp(-$lambda);
        $k = 0;
        $p = 1.0;
        do {
            $k++;
            $p *= mt_rand() / mt_getrandmax();
        } while ($p > $L);
        return $k - 1;
    }
}

if (!function_exists('sim_get_all_matches')) {
    function sim_get_all_matches(PDO $db, $comp_code, $season_label) {
        $stmt = $db->prepare(
            'SELECT id, matchweek, match_date, home_team, away_team, home_goals, away_goals
             FROM matches
             WHERE competition_code = ? AND season_label = ?
             ORDER BY matchweek ASC, match_date ASC, id ASC'
        );
        $stmt->execute([$comp_code, $season_label]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('sim_build_standings_and_stats')) {
    function sim_build_standings_and_stats(array $all_matches, $sim_from_mw) {
        return sim_build_standings_and_stats_impl($all_matches, function ($m) use ($sim_from_mw) {
            // sim_from_mw=0 (Pre-Season) → no matches are pre-completed
            if ((int) $sim_from_mw === 0) return false;
            return (int) $m['matchweek'] < (int) $sim_from_mw;
        });
    }
}

if (!function_exists('sim_build_standings_and_stats_by_date')) {
    function sim_build_standings_and_stats_by_date(array $all_matches, $sim_from_date) {
        return sim_build_standings_and_stats_impl($all_matches, function ($m) use ($sim_from_date) {
            if (empty($m['match_date'])) return false;
            return strcmp((string) $m['match_date'], (string) $sim_from_date) < 0;
        });
    }
}

if (!function_exists('sim_build_standings_and_stats_impl')) {
    function sim_build_standings_and_stats_impl(array $all_matches, callable $is_completed) {
        $standings = [];
        $home_goals_total = 0;
        $away_goals_total = 0;
        $n_completed = 0;

        // First pass: collect every team name from the fixture list
        foreach ($all_matches as $m) {
            foreach ([$m['home_team'], $m['away_team']] as $team) {
                if (!isset($standings[$team])) {
                    $standings[$team] = [
                        'team' => $team,
                        'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0,
                        'gf' => 0, 'ga' => 0, 'gd' => 0, 'points' => 0,
                    ];
                }
            }
        }

        // Second pass: accumulate results for matches identified as completed
        foreach ($all_matches as $m) {
            if (!$is_completed($m)) continue;
            if (!is_numeric($m['home_goals']) || !is_numeric($m['away_goals'])) continue;

            $home = $m['home_team'];
            $away = $m['away_team'];
            $hg   = (int) $m['home_goals'];
            $ag   = (int) $m['away_goals'];

            $standings[$home]['played']++;
            $standings[$away]['played']++;
            $standings[$home]['gf'] += $hg;
            $standings[$home]['ga'] += $ag;
            $standings[$home]['gd'] += $hg - $ag;
            $standings[$away]['gf'] += $ag;
            $standings[$away]['ga'] += $hg;
            $standings[$away]['gd'] += $ag - $hg;

            if ($hg > $ag) {
                $standings[$home]['won']++;
                $standings[$home]['points'] += 3;
                $standings[$away]['lost']++;
            } elseif ($hg < $ag) {
                $standings[$away]['won']++;
                $standings[$away]['points'] += 3;
                $standings[$home]['lost']++;
            } else {
                $standings[$home]['drawn']++;
                $standings[$away]['drawn']++;
                $standings[$home]['points']++;
                $standings[$away]['points']++;
            }

            $home_goals_total += $hg;
            $away_goals_total += $ag;
            $n_completed++;
        }

        // League goal averages (fall back to typical English football values)
        if ($n_completed >= 10) {
            $avg_home_goals = $home_goals_total / $n_completed;
            $avg_away_goals = $away_goals_total / $n_completed;
        } else {
            $avg_home_goals = 1.50;
            $avg_away_goals = 1.15;
        }

        // Sort: points → GD → GF
        uasort($standings, function ($a, $b) {
            if ($b['points'] !== $a['points']) return $b['points'] - $a['points'];
            if ($b['gd']     !== $a['gd'])     return $b['gd']     - $a['gd'];
            return $b['gf'] - $a['gf'];
        });

        $pos = 1;
        foreach ($standings as &$t) {
            $t['position'] = $pos++;
        }
        unset($t);

        return [
            'standings'      => $standings,
            'avg_home_goals' => $avg_home_goals,
            'avg_away_goals' => $avg_away_goals,
            'n_completed'    => $n_completed,
        ];
    }
}

if (!function_exists('sim_calculate_strengths')) {
    /**
     * Compute attack/defense ratings relative to league average.
     * attack  > 1 → scores more than average
     * defense > 1 → concedes more than average (i.e. weak defence)
     */
    function sim_calculate_strengths(array $standings, float $avg_home_goals, float $avg_away_goals) {
        $avg_goals = ($avg_home_goals + $avg_away_goals) / 2;
        $strengths = [];

        foreach ($standings as $team => $data) {
            if ($data['played'] >= 3) {
                $attack  = ($data['gf'] / $data['played']) / $avg_goals;
                $defense = ($data['ga'] / $data['played']) / $avg_goals;
            } else {
                $attack  = 1.0;
                $defense = 1.0;
            }

            $strengths[$team] = [
                'attack'  => max(0.2, min(4.0, $attack)),
                'defense' => max(0.2, min(4.0, $defense)),
            ];
        }

        return $strengths;
    }
}

if (!function_exists('sim_run_single')) {
    /**
     * Simulate one full season outcome from the current standings.
     * Returns [ team_name => ['position' => int, 'points' => int], … ]
     */
    function sim_run_single(
        array $standings,
        array $remaining_matches,
        array $strengths,
        float $avg_home_goals,
        float $avg_away_goals
    ) {
        $pts = [];
        $gd  = [];
        $gf  = [];

        foreach ($standings as $team => $data) {
            $pts[$team] = $data['points'];
            $gd[$team]  = $data['gd'];
            $gf[$team]  = $data['gf'];
        }

        foreach ($remaining_matches as $m) {
            $home = $m['home_team'];
            $away = $m['away_team'];

            $hs = $strengths[$home] ?? ['attack' => 1.0, 'defense' => 1.0];
            $as = $strengths[$away] ?? ['attack' => 1.0, 'defense' => 1.0];

            // Expected goals: home attack × away defensive weakness × league avg home goals
            $exp_home = max(0.1, min(8.0, $hs['attack'] * $as['defense'] * $avg_home_goals));
            $exp_away = max(0.1, min(8.0, $as['attack'] * $hs['defense'] * $avg_away_goals));

            $hg = sim_poisson_random($exp_home);
            $ag = sim_poisson_random($exp_away);

            if (!isset($pts[$home])) { $pts[$home] = 0; $gd[$home] = 0; $gf[$home] = 0; }
            if (!isset($pts[$away])) { $pts[$away] = 0; $gd[$away] = 0; $gf[$away] = 0; }

            $gf[$home] += $hg;
            $gf[$away] += $ag;
            $gd[$home] += $hg - $ag;
            $gd[$away] += $ag - $hg;

            if ($hg > $ag) {
                $pts[$home] += 3;
            } elseif ($hg < $ag) {
                $pts[$away] += 3;
            } else {
                $pts[$home]++;
                $pts[$away]++;
            }
        }

        $teams = array_keys($standings);
        usort($teams, function ($a, $b) use ($pts, $gd, $gf) {
            if (($pts[$b] ?? 0) !== ($pts[$a] ?? 0)) return ($pts[$b] ?? 0) - ($pts[$a] ?? 0);
            if (($gd[$b]  ?? 0) !== ($gd[$a]  ?? 0)) return ($gd[$b]  ?? 0) - ($gd[$a]  ?? 0);
            return ($gf[$b] ?? 0) - ($gf[$a] ?? 0);
        });

        $result = [];
        foreach ($teams as $i => $team) {
            $result[$team] = [
                'position' => $i + 1,
                'points'   => $pts[$team] ?? 0,
            ];
        }

        return $result;
    }
}

if (!function_exists('sim_run_monte_carlo_by_date')) {
    /**
     * Monte Carlo run that uses a calendar date as the threshold between
     * completed and to-be-simulated matches (matches with match_date < $sim_from_date
     * are treated as completed; the rest are simulated).
     */
    function sim_run_monte_carlo_by_date(
        PDO   $db,
        $comp_code,
        $season_label,
        $sim_from_date,
        $n_sims = 1000,
        $sim_to_date = null
    ) {
        $all_matches = sim_get_all_matches($db, $comp_code, $season_label);

        $built       = sim_build_standings_and_stats_by_date($all_matches, $sim_from_date);
        $standings   = $built['standings'];
        $avg_home    = $built['avg_home_goals'];
        $avg_away    = $built['avg_away_goals'];
        $n_completed = $built['n_completed'];

        $strengths = sim_calculate_strengths($standings, $avg_home, $avg_away);

        // Simulate matches in [sim_from_date, sim_to_date]; sim_to_date=null means season end
        $remaining = array_values(array_filter($all_matches, function ($m) use ($sim_from_date, $sim_to_date) {
            if (empty($m['match_date'])) return false;
            $d = (string) $m['match_date'];
            if (strcmp($d, (string) $sim_from_date) < 0) return false;
            if ($sim_to_date !== null && strcmp($d, (string) $sim_to_date) > 0) return false;
            return true;
        }));

        $n_teams    = count($standings);
        $team_names = array_keys($standings);

        $position_counts = [];
        $points_sums     = [];
        foreach ($team_names as $team) {
            $position_counts[$team] = array_fill(1, $n_teams, 0);
            $points_sums[$team]     = [];
        }

        for ($i = 0; $i < $n_sims; $i++) {
            $result = sim_run_single($standings, $remaining, $strengths, $avg_home, $avg_away);
            foreach ($result as $team => $r) {
                if (!isset($position_counts[$team])) continue;
                $pos = $r['position'];
                if (isset($position_counts[$team][$pos])) {
                    $position_counts[$team][$pos]++;
                }
                $points_sums[$team][] = $r['points'];
            }
        }

        $aggregated = [];
        foreach ($team_names as $team) {
            $pts_list = $points_sums[$team] ?? [];
            sort($pts_list);
            $n = count($pts_list);

            $avg_pos   = 0;
            $total_pos = array_sum($position_counts[$team] ?? []);
            if ($total_pos > 0) {
                foreach ($position_counts[$team] as $pos => $count) {
                    $avg_pos += $pos * $count;
                }
                $avg_pos /= $total_pos;
            }

            $aggregated[$team] = [
                'team'                => $team,
                'current_points'      => $standings[$team]['points'],
                'current_position'    => $standings[$team]['position'],
                'current_played'      => $standings[$team]['played'],
                'avg_final_position'  => round($avg_pos, 2),
                'avg_final_points'    => $n > 0 ? round(array_sum($pts_list) / $n, 1) : 0,
                'min_final_points'    => $n > 0 ? $pts_list[0] : 0,
                'max_final_points'    => $n > 0 ? $pts_list[$n - 1] : 0,
                'p10_final_points'    => $n > 0 ? $pts_list[max(0, (int) ($n * 0.10))] : 0,
                'p90_final_points'    => $n > 0 ? $pts_list[min($n - 1, (int) ($n * 0.90))] : 0,
                'pos_dist'            => $position_counts[$team] ?? [],
                'n_teams'             => $n_teams,
            ];
        }

        uasort($aggregated, function ($a, $b) {
            return $a['avg_final_position'] <=> $b['avg_final_position'];
        });

        // First unplayed date in the season (for default selection / UI hint)
        $next_unplayed_date = null;
        foreach ($all_matches as $m) {
            if (!is_numeric($m['home_goals']) || !is_numeric($m['away_goals'])) {
                $d = (string) ($m['match_date'] ?? '');
                if ($d === '') continue;
                if ($next_unplayed_date === null || strcmp($d, $next_unplayed_date) < 0) {
                    $next_unplayed_date = $d;
                }
            }
        }

        // All distinct match dates in the season (for UI dropdown)
        $dates = array_values(array_unique(array_filter(array_map(function ($m) {
            return (string) ($m['match_date'] ?? '');
        }, $all_matches))));
        sort($dates);

        return [
            'teams'              => $aggregated,
            'standings'          => $standings,
            'sim_from_date'      => (string) $sim_from_date,
            'sim_to_date'        => $sim_to_date !== null ? (string) $sim_to_date : null,
            'n_sims'             => $n_sims,
            'n_remaining'        => count($remaining),
            'avg_home_goals'     => $avg_home,
            'avg_away_goals'     => $avg_away,
            'n_completed'        => $n_completed,
            'match_dates'        => $dates,
            'next_unplayed_date' => $next_unplayed_date,
        ];
    }
}

if (!function_exists('sim_run_monte_carlo')) {
    /**
     * Full Monte Carlo run.
     *
     * Returns:
     *   teams          – aggregated per-team results sorted by avg_final_position
     *   standings      – standings at sim_from_mw (before simulation)
     *   sim_from_mw    – the starting matchweek
     *   n_sims         – simulations run
     *   n_remaining    – remaining matches simulated
     *   avg_home_goals – league average home goals (used for simulation)
     *   avg_away_goals – league average away goals
     *   n_completed    – completed matches used to build initial state
     *   matchweeks     – all matchweeks in the season (for UI dropdown)
     *   next_unplayed_mw – first matchweek with an unplayed fixture
     */
    function sim_run_monte_carlo(
        PDO   $db,
        $comp_code,
        $season_label,
        $sim_from_mw,
        $n_sims = 1000,
        $sim_to_mw = null
    ) {
        $all_matches = sim_get_all_matches($db, $comp_code, $season_label);

        $built      = sim_build_standings_and_stats($all_matches, $sim_from_mw);
        $standings  = $built['standings'];
        $avg_home   = $built['avg_home_goals'];
        $avg_away   = $built['avg_away_goals'];
        $n_completed = $built['n_completed'];

        $strengths = sim_calculate_strengths($standings, $avg_home, $avg_away);

        // Simulate matches within [sim_from_mw, sim_to_mw]; sim_to_mw=null means season end
        $remaining = array_values(array_filter($all_matches, function ($m) use ($sim_from_mw, $sim_to_mw) {
            $mw = (int) $m['matchweek'];
            if ($mw < (int) $sim_from_mw) return false;
            if ($sim_to_mw !== null && $mw > (int) $sim_to_mw) return false;
            return true;
        }));

        $n_teams    = count($standings);
        $team_names = array_keys($standings);

        $position_counts = [];
        $points_sums     = [];
        foreach ($team_names as $team) {
            $position_counts[$team] = array_fill(1, $n_teams, 0);
            $points_sums[$team]     = [];
        }

        for ($i = 0; $i < $n_sims; $i++) {
            $result = sim_run_single($standings, $remaining, $strengths, $avg_home, $avg_away);

            foreach ($result as $team => $r) {
                if (!isset($position_counts[$team])) continue;
                $pos = $r['position'];
                if (isset($position_counts[$team][$pos])) {
                    $position_counts[$team][$pos]++;
                }
                $points_sums[$team][] = $r['points'];
            }
        }

        // Aggregate per team
        $aggregated = [];
        foreach ($team_names as $team) {
            $pts_list = $points_sums[$team] ?? [];
            sort($pts_list);
            $n = count($pts_list);

            $avg_pos    = 0;
            $total_pos  = array_sum($position_counts[$team] ?? []);
            if ($total_pos > 0) {
                foreach ($position_counts[$team] as $pos => $count) {
                    $avg_pos += $pos * $count;
                }
                $avg_pos /= $total_pos;
            }

            $aggregated[$team] = [
                'team'                => $team,
                'current_points'      => $standings[$team]['points'],
                'current_position'    => $standings[$team]['position'],
                'current_played'      => $standings[$team]['played'],
                'avg_final_position'  => round($avg_pos, 2),
                'avg_final_points'    => $n > 0 ? round(array_sum($pts_list) / $n, 1) : 0,
                'min_final_points'    => $n > 0 ? $pts_list[0] : 0,
                'max_final_points'    => $n > 0 ? $pts_list[$n - 1] : 0,
                'p10_final_points'    => $n > 0 ? $pts_list[max(0, (int) ($n * 0.10))] : 0,
                'p90_final_points'    => $n > 0 ? $pts_list[min($n - 1, (int) ($n * 0.90))] : 0,
                'pos_dist'            => $position_counts[$team] ?? [],
                'n_teams'             => $n_teams,
            ];
        }

        uasort($aggregated, function ($a, $b) {
            return $a['avg_final_position'] <=> $b['avg_final_position'];
        });

        // Matchweek list for UI
        $mws = array_unique(array_column($all_matches, 'matchweek'));
        sort($mws, SORT_NUMERIC);

        // First matchweek with any unplayed fixture
        $next_unplayed = null;
        foreach ($all_matches as $m) {
            if (!is_numeric($m['home_goals']) || !is_numeric($m['away_goals'])) {
                $mw = (int) $m['matchweek'];
                if ($next_unplayed === null || $mw < $next_unplayed) {
                    $next_unplayed = $mw;
                }
            }
        }

        return [
            'teams'            => $aggregated,
            'standings'        => $standings,
            'sim_from_mw'      => (int) $sim_from_mw,
            'sim_to_mw'        => $sim_to_mw !== null ? (int) $sim_to_mw : null,
            'n_sims'           => $n_sims,
            'n_remaining'      => count($remaining),
            'avg_home_goals'   => $avg_home,
            'avg_away_goals'   => $avg_away,
            'n_completed'      => $n_completed,
            'matchweeks'       => $mws,
            'next_unplayed_mw' => $next_unplayed,
        ];
    }
}
