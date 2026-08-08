<?php
/**
 * World Cup 2026 – Monte Carlo Tournament Simulation
 *
 * Uses a Poisson goal model to estimate each team's probability of reaching
 * every knockout round, based on current group-stage performance. Also tracks
 * the full knockout bracket: round-by-round matchup frequencies and each
 * team's single most likely path to the title.
 *
 * Requires: $world_cup_db (PDO), sim_poisson_random() from simulation-engine.php
 */
require_once __DIR__ . '/../../includes/simulation-engine.php';

// ── Parameters ────────────────────────────────────────────────────────────────
$n_sims_allowed = [500, 1000, 5000, 10000];
$n_sims = isset($_GET['n_sims']) && in_array((int)$_GET['n_sims'], $n_sims_allowed, true)
    ? (int)$_GET['n_sims'] : 1000;

// ── Fetch group standings ─────────────────────────────────────────────────────
$groups_raw = $world_cup_db->query(
    "SELECT group_name, team_name, position, played, won, drawn, lost, gf, ga, gd, points
     FROM wc_groups ORDER BY group_name, position"
)->fetchAll(PDO::FETCH_ASSOC);

$groupData  = [];
$has_data   = !empty($groups_raw);
$has_played = false;

foreach ($groups_raw as $row) {
    $groupData[$row['group_name']][] = $row;
    if ((int)$row['played'] > 0) $has_played = true;
}

// ── FIFA rankings (November 2025 seedings) ────────────────────────────────────
// Maps team name → approximate FIFA rank at the time of the WC 2026 draw.
// Used to seed pre-tournament strength when no match data is available, and
// blended with actual performance as group-stage matches are played.
// Consistent with the pot-1 tiers already defined across the other WC tabs.
$wc_fifa_rankings = [
    // Pot 1 elite (ranks 1–12 per project convention)
    'Spain'               => 1,
    'Argentina'           => 2,
    'France'              => 3,
    'England'             => 4,
    'Brazil'              => 5,
    'Portugal'            => 6,
    'Netherlands'         => 7,
    'Belgium'             => 8,
    'Germany'             => 9,
    'Croatia'             => 10,
    'Morocco'             => 11,
    'Colombia'            => 12,
    // Ranks 13–24
    'Uruguay'             => 13,
    'Japan'               => 14,
    'United States'       => 15,
    'USA'                 => 15,
    'Senegal'             => 16,
    'Switzerland'         => 17,
    'Denmark'             => 18,
    'South Korea'         => 19,
    'Korea Republic'      => 19,
    'Austria'             => 20,
    'Mexico'              => 21,
    'Ecuador'             => 22,
    'Serbia'              => 23,
    'Australia'           => 24,
    // Ranks 25–36
    'Canada'              => 25,
    'Poland'              => 26,
    'Turkey'              => 27,
    'Ivory Coast'         => 28,
    "Côte d'Ivoire"       => 28,
    'Czech Republic'      => 29,
    'Czechia'             => 29,
    'Egypt'               => 30,
    'Ukraine'             => 31,
    'Venezuela'           => 32,
    'Nigeria'             => 33,
    'Paraguay'            => 34,
    'Cameroon'            => 35,
    'Iran'                => 36,
    // Ranks 37–48
    'Saudi Arabia'        => 37,
    'Hungary'             => 38,
    'Tunisia'             => 39,
    'Algeria'             => 40,
    'Costa Rica'          => 41,
    'Panama'              => 42,
    'Honduras'            => 43,
    'Guatemala'           => 44,
    'Jamaica'             => 45,
    'Bolivia'             => 46,
    'New Zealand'         => 47,
    'Qatar'               => 48,
    'Iraq'                => 48,
    'Indonesia'           => 48,
];

// ── Round encoding (shared across the whole file) ─────────────────────────────
// 0 = group exit, 1 = R32 exit, 2 = R16 exit, 3 = QF exit, 4 = SF exit,
// 5 = finalist (runner-up), 6 = champion.
// "round_num" passed into wc_sim_match()/bracket loop refers to the round a
// team ADVANCES INTO if they win the match at that bracket level, i.e. the
// match being played at bracket level $round_num - 1 determines whether a
// team reaches $round_num.
$wc_round_names = [
    1 => 'Round of 32',
    2 => 'Round of 16',
    3 => 'Quarter-Final',
    4 => 'Semi-Final',
    5 => 'Final',
    6 => 'Champion',
];

// ── Simulation helpers ────────────────────────────────────────────────────────

if (!function_exists('wc_fifa_strength')) {
    /**
     * Convert a FIFA rank (1 = best, 48 = weakest qualifier) into
     * attack/defense ratings for the Poisson model.
     *
     * At rank 1:  attack = 1.75, defense = 0.50 (dominant)
     * At rank 48: attack = 0.50, defense = 1.75 (weak)
     * Mid-table team is roughly attack ≈ defense ≈ 1.12
     */
    function wc_fifa_strength(int $rank): array {
        $rank    = max(1, min(48, $rank));
        $t       = ($rank - 1) / 47;          // 0.0 (rank 1) → 1.0 (rank 48)
        $attack  = 1.75 - $t * 1.25;          // 1.75 → 0.50
        $defense = 0.50 + $t * 1.25;          // 0.50 → 1.75
        return [
            'attack'  => round($attack,  4),
            'defense' => round($defense, 4),
        ];
    }
}

if (!function_exists('wc_sim_strengths')) {
    /**
     * Build attack/defense strength map for all teams.
     *
     * When played = 0  → 100% FIFA prior.
     * When played = 1  → 1/3 actual + 2/3 FIFA.
     * When played = 2  → 2/3 actual + 1/3 FIFA.
     * When played ≥ 3  → 100% actual performance (FIFA prior discarded).
     *
     * attack > 1 = scores more than average
     * defense > 1 = concedes more than average (i.e. weak defence)
     */
    function wc_sim_strengths(array $groups_raw, float $avg_goals, array $fifa_rankings): array {
        $s = [];
        foreach ($groups_raw as $t) {
            $p    = (int)$t['played'];
            $name = $t['team_name'];

            // Actual-performance rating (only meaningful when p >= 1)
            if ($p >= 1) {
                $act_atk = ($t['gf'] / $p) / max($avg_goals, 0.1);
                $act_def = ($t['ga'] / $p) / max($avg_goals, 0.1);
            } else {
                $act_atk = $act_def = 1.0;
            }

            // FIFA-prior rating
            $rank     = $fifa_rankings[$name] ?? 36; // default: low-mid tier
            $prior    = wc_fifa_strength($rank);

            // Blend: weight actual linearly over the first 3 matches
            $w_actual = min($p / 3.0, 1.0);
            $w_prior  = 1.0 - $w_actual;

            $atk = $w_actual * $act_atk + $w_prior * $prior['attack'];
            $def = $w_actual * $act_def + $w_prior * $prior['defense'];

            $s[$name] = [
                'attack'   => max(0.2, min(5.0, $atk)),
                'defense'  => max(0.2, min(5.0, $def)),
                'fifa_rank' => $rank,
            ];
        }
        return $s;
    }
}

if (!function_exists('wc_sim_match')) {
    /**
     * Simulate one match. Knockout ties are broken by a weighted penalty
     * coin-flip (slight edge to the team with the better attack rating).
     * Returns [$goals_a, $goals_b].
     */
    function wc_sim_match(string $a, string $b, array $s, float $avg): array {
        $sa = $s[$a] ?? ['attack' => 1.0, 'defense' => 1.0];
        $sb = $s[$b] ?? ['attack' => 1.0, 'defense' => 1.0];

        $exp_a = max(0.1, min(8.0, $sa['attack'] * $sb['defense'] * $avg));
        $exp_b = max(0.1, min(8.0, $sb['attack'] * $sa['defense'] * $avg));

        $ga = sim_poisson_random($exp_a);
        $gb = sim_poisson_random($exp_b);

        if ($ga === $gb) {
            // Knockout penalty shootout: slight edge to the team with better attack
            $total = $sa['attack'] + $sb['attack'];
            if (mt_rand() / mt_getrandmax() < ($sa['attack'] / $total)) $ga++;
            else $gb++;
        }

        return [$ga, $gb];
    }
}

if (!function_exists('wc_sim_group')) {
    /**
     * Simulate remaining group-stage rounds for one 4-team group.
     *
     * Round-robin schedule (0-indexed team positions as sorted by current standings):
     *   Round 1: (0v1, 2v3)   Round 2: (0v2, 1v3)   Round 3: (0v3, 1v2)
     *
     * Returns team_name => ['pos'=>1..4, 'pts'=>int, 'gd'=>int, 'gf'=>int]
     */
    function wc_sim_group(array $teams, array $s, float $avg): array {
        static $sched = [
            [[0,1],[2,3]],
            [[0,2],[1,3]],
            [[0,3],[1,2]],
        ];

        $played = (int)($teams[0]['played'] ?? 0);
        $n      = array_column($teams, 'team_name');
        $pts    = array_map(fn($t) => (int)$t['points'], $teams);
        $gd     = array_map(fn($t) => (int)$t['gd'],     $teams);
        $gf     = array_map(fn($t) => (int)$t['gf'],     $teams);

        for ($r = $played; $r < 3; $r++) {
            foreach ($sched[$r] as [$ia, $ib]) {
                if (!isset($n[$ia], $n[$ib])) continue;
                [$ga, $gb] = wc_sim_match($n[$ia], $n[$ib], $s, $avg);
                $gf[$ia] += $ga; $gd[$ia] += $ga - $gb;
                $gf[$ib] += $gb; $gd[$ib] += $gb - $ga;
                if      ($ga > $gb) { $pts[$ia] += 3; }
                elseif  ($gb > $ga) { $pts[$ib] += 3; }
                else                { $pts[$ia]++;  $pts[$ib]++; }
            }
        }

        $idx = range(0, count($n) - 1);
        usort($idx, fn($a, $b) =>
            $pts[$b] !== $pts[$a] ? $pts[$b] - $pts[$a]
          : ($gd[$b] !== $gd[$a]  ? $gd[$b]  - $gd[$a]
          : $gf[$b] - $gf[$a])
        );

        $result = [];
        foreach ($idx as $rank => $i) {
            $result[$n[$i]] = ['pos' => $rank + 1, 'pts' => $pts[$i], 'gd' => $gd[$i], 'gf' => $gf[$i]];
        }
        return $result;
    }
}

if (!function_exists('wc_sim_tournament')) {
    /**
     * Simulate one complete World Cup tournament: group stage + full
     * knockout bracket (R32 → R16 → QF → SF → Final).
     *
     * Returns an array with two parts:
     *   'round'   => team_name => deepest round reached (0..6, see encoding above)
     *   'matches' => list of every knockout match played, each as
     *                ['round' => 1..5, 'a' => team, 'b' => team, 'winner' => team]
     *                round here is the BRACKET LEVEL the match was played at
     *                (1 = R32, 2 = R16, 3 = QF, 4 = SF, 5 = Final).
     */
    function wc_sim_tournament(array $groupData, array $s, float $avg): array {
        // Initialise all teams as group-stage exit
        $round = [];
        foreach ($groupData as $teams) {
            foreach ($teams as $t) $round[$t['team_name']] = 0;
        }

        $qualifiers  = [];
        $third_place = [];

        foreach ($groupData as $teams) {
            if (count($teams) < 4) {
                foreach ($teams as $t) $round[$t['team_name']] = 1;
                continue;
            }
            $res = wc_sim_group($teams, $s, $avg);

            foreach ($res as $team => $r) {
                if ($r['pos'] <= 2) {
                    $qualifiers[] = [
                        'team' => $team, 'pts' => $r['pts'], 'gd' => $r['gd'], 'gf' => $r['gf'],
                        'slot' => $r['pos'] === 1 ? 'winner' : 'runner',
                    ];
                    $round[$team] = 1;
                } elseif ($r['pos'] === 3) {
                    $third_place[] = ['team' => $team, 'pts' => $r['pts'], 'gd' => $r['gd'], 'gf' => $r['gf']];
                }
            }
        }

        // Best 8 third-place teams advance
        usort($third_place, fn($a, $b) =>
            $b['pts'] !== $a['pts'] ? $b['pts'] - $a['pts']
          : ($b['gd'] !== $a['gd']  ? $b['gd']  - $a['gd']
          : $b['gf'] - $a['gf'])
        );
        foreach (array_slice($third_place, 0, 8) as $t) {
            $qualifiers[] = ['team' => $t['team'], 'pts' => $t['pts'], 'gd' => $t['gd'], 'gf' => $t['gf'], 'slot' => 'third'];
            $round[$t['team']] = 1;
        }

        // Sort bracket: group winners (best first), then runners-up, then best 3rd
        usort($qualifiers, function ($a, $b) {
            $ord = ['winner' => 0, 'runner' => 1, 'third' => 2];
            $oa = $ord[$a['slot']]; $ob = $ord[$b['slot']];
            if ($oa !== $ob) return $oa - $ob;
            return $b['pts'] !== $a['pts'] ? $b['pts'] - $a['pts'] : $b['gd'] - $a['gd'];
        });

        $bracket    = array_column($qualifiers, 'team');
        $round_num  = 2; // winners of current round advance to this value
        $bracket_lv = 1; // 1=R32, 2=R16, 3=QF, 4=SF, 5=Final
        $matches    = [];

        while (count($bracket) > 1) {
            $next = [];
            for ($i = 0; $i + 1 < count($bracket); $i += 2) {
                [$ga, $gb] = wc_sim_match($bracket[$i], $bracket[$i + 1], $s, $avg);
                $winner  = $ga > $gb ? $bracket[$i] : $bracket[$i + 1];
                $round[$winner] = $round_num;
                $matches[] = [
                    'round'  => $bracket_lv,
                    'a'      => $bracket[$i],
                    'b'      => $bracket[$i + 1],
                    'winner' => $winner,
                ];
                $next[] = $winner;
            }
            $bracket = $next;
            $round_num++;
            $bracket_lv++;
        }

        return ['round' => $round, 'matches' => $matches];
    }
}

// ── Compute league-average goals per team per match ───────────────────────────
$total_gf = $total_played = 0;
foreach ($groups_raw as $r) {
    $total_gf     += (int)$r['gf'];
    $total_played += (int)$r['played'];
}
// total_gf / total_played = avg goals scored per team per match
$avg_goals = $total_played > 0 ? ($total_gf / $total_played) : 1.20;

$strengths = wc_sim_strengths($groups_raw, $avg_goals, $wc_fifa_rankings);

// ── Run simulations ───────────────────────────────────────────────────────────
ini_set('max_execution_time', 90);

// round_counts[team][0..6] = count of simulations where team's deepest round = that value
$all_team_meta = [];
foreach ($groups_raw as $r) {
    $name = $r['team_name'];
    $all_team_meta[$name] = [
        'group'      => $r['group_name'],
        'pos'        => (int)$r['position'],
        'played'     => (int)$r['played'],
        'fifa_rank'  => $strengths[$name]['fifa_rank'] ?? ($wc_fifa_rankings[$name] ?? 36),
    ];
}

$round_counts = [];
foreach (array_keys($all_team_meta) as $team) {
    $round_counts[$team] = array_fill(0, 7, 0);
}

// Knockout matchup tracking: matchup_counts[bracket_level]["TeamA|TeamB"] = ['count'=>n, 'a_wins'=>n, 'b_wins'=>n]
// Teams within a pairing key are kept in a stable (sorted) order so the same
// pairing accumulates correctly regardless of which side they land on.
$matchup_counts = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];

// Per-team per-round opponent tally, used to build each team's "most likely path":
// path_opponents[team][bracket_level][opponent] = count (only counted in sims
// where the team actually reached that bracket level).
$path_opponents = [];
foreach (array_keys($all_team_meta) as $team) {
    $path_opponents[$team] = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];
}
// path_advance[team][bracket_level] = count of sims where team won that match
$path_advance = [];
foreach (array_keys($all_team_meta) as $team) {
    $path_advance[$team] = array_fill(1, 5, 0);
}

if ($has_data) {
    for ($i = 0; $i < $n_sims; $i++) {
        $sim = wc_sim_tournament($groupData, $strengths, $avg_goals);

        foreach ($sim['round'] as $team => $r) {
            if (isset($round_counts[$team][$r])) $round_counts[$team][$r]++;
        }

        foreach ($sim['matches'] as $m) {
            $lvl = $m['round'];
            $a   = $m['a'];
            $b   = $m['b'];

            // Stable pairing key so "A vs B" and "B vs A" merge together
            if (strcmp($a, $b) <= 0) { $t1 = $a; $t2 = $b; }
            else                     { $t1 = $b; $t2 = $a; }
            $key = $t1 . '|' . $t2;

            if (!isset($matchup_counts[$lvl][$key])) {
                $matchup_counts[$lvl][$key] = ['t1' => $t1, 't2' => $t2, 'count' => 0, 't1_wins' => 0, 't2_wins' => 0];
            }
            $matchup_counts[$lvl][$key]['count']++;
            if ($m['winner'] === $t1) $matchup_counts[$lvl][$key]['t1_wins']++;
            else                      $matchup_counts[$lvl][$key]['t2_wins']++;

            // Per-team path tracking (from both sides of the match)
            if (isset($path_opponents[$a][$lvl])) {
                $path_opponents[$a][$lvl][$b] = ($path_opponents[$a][$lvl][$b] ?? 0) + 1;
                if ($m['winner'] === $a) $path_advance[$a][$lvl]++;
            }
            if (isset($path_opponents[$b][$lvl])) {
                $path_opponents[$b][$lvl][$a] = ($path_opponents[$b][$lvl][$a] ?? 0) + 1;
                if ($m['winner'] === $b) $path_advance[$b][$lvl]++;
            }
        }
    }
}

// ── Build aggregated display data ─────────────────────────────────────────────
$team_display = [];
foreach ($all_team_meta as $team => $meta) {
    $c = $round_counts[$team];
    $tot = $n_sims;
    $team_display[$team] = [
        'team'       => $team,
        'group'      => $meta['group'],
        'pos'        => $meta['pos'],
        'played'     => $meta['played'],
        'fifa_rank'  => $meta['fifa_rank'],
        'p_win'   => $tot > 0 ? $c[6] / $tot : 0,
        'p_final' => $tot > 0 ? ($c[5]+$c[6]) / $tot : 0,
        'p_sf'    => $tot > 0 ? ($c[4]+$c[5]+$c[6]) / $tot : 0,
        'p_qf'    => $tot > 0 ? ($c[3]+$c[4]+$c[5]+$c[6]) / $tot : 0,
        'p_r16'   => $tot > 0 ? ($c[2]+$c[3]+$c[4]+$c[5]+$c[6]) / $tot : 0,
        'p_qual'  => $tot > 0 ? (array_sum($c) - $c[0]) / $tot : 0,
        'counts'  => $c,
    ];
}

uasort($team_display, function ($a, $b) {
    foreach (['p_win','p_final','p_sf','p_qf','p_r16','p_qual'] as $k) {
        $diff = $b[$k] - $a[$k];
        if (abs($diff) > 1e-9) return $diff > 0 ? 1 : -1;
    }
    return 0;
});

/**
 * Build a team's "most likely path": for each bracket level, the most
 * frequent opponent they faced (conditional on reaching that level) and
 * the probability of beating that specific opponent in this simulation set.
 * Stops as soon as a level was never reached.
 */
function wc_build_likely_path(string $team, array $path_opponents, array $path_advance): array {
    $path = [];
    foreach ([1, 2, 3, 4, 5] as $lvl) {
        $opps = $path_opponents[$team][$lvl] ?? [];
        if (empty($opps)) break;
        arsort($opps);
        $top_opp   = array_key_first($opps);
        $appear_ct = $opps[$top_opp];
        $total_ct  = array_sum($opps);
        $path[] = [
            'round'        => $lvl,
            'opponent'     => $top_opp,
            'opp_share'    => $total_ct > 0 ? $appear_ct / $total_ct : 0, // how often this specific opponent shows up vs others at this level
            'level_reach_pct' => $total_ct, // raw count of sims team reached this level (any opponent)
        ];
    }
    return $path;
}

$likely_paths = [];
foreach (array_keys($all_team_meta) as $team) {
    $likely_paths[$team] = wc_build_likely_path($team, $path_opponents, $path_advance);
}

/**
 * Build the most probable single bracket: for each level, pick the highest
 * sim-count matchup. This is illustrative only (a "if chalk mostly holds"
 * snapshot) — it does not attempt to resolve a fully consistent single
 * bracket across levels, since qualifier identity itself varies sim-to-sim.
 */
$top_matchups_by_level = [];
foreach ($matchup_counts as $lvl => $pairs) {
    if (empty($pairs)) { $top_matchups_by_level[$lvl] = []; continue; }
    uasort($pairs, fn($a, $b) => $b['count'] - $a['count']);
    $top_matchups_by_level[$lvl] = array_slice($pairs, 0, 8, true); // up to 8 most common pairings per level
}

// ── Display helpers ───────────────────────────────────────────────────────────
function wc_esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function wc_pct(float $v): string {
    $pct = $v * 100;
    if ($pct <= 0)     return '0%';
    if ($pct >= 99.95) return '>99%';
    if ($pct < 0.1)    return '<0.1%';
    return number_format($pct, 1) . '%';
}

function wc_pct_color(float $v): string {
    if ($v >= 0.50) return '#43b581';
    if ($v >= 0.15) return '#faa61a';
    if ($v >  0.00) return '#f04747';
    return '#444';
}

function wc_sim_url(int $n_sims): string {
    return '?' . http_build_query(['tab' => 'world-cup', 'subtab' => 'simulation', 'n_sims' => $n_sims]);
}

// Round distribution colours (index 0..6)
$round_colors = [
    0 => '#3a3a3a',   // Group exit (dark grey)
    1 => '#714b4b',   // R32 exit
    2 => '#7b6020',   // R16 exit
    3 => '#5865F2',   // QF exit (blue)
    4 => '#2d6b8e',   // SF exit (teal)
    5 => '#aaaaaa',   // Finalist (silver)
    6 => '#FFD700',   // Champion (gold)
];
$round_labels = [
    0 => 'Group exit',
    1 => 'R32 exit',
    2 => 'R16 exit',
    3 => 'QF exit',
    4 => 'SF exit',
    5 => 'Finalist',
    6 => 'Champion',
];
?>
<style>
.wc-sim-controls    { display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end; margin-bottom:20px; }
.wc-sim-ctrl-group  { display:flex; flex-direction:column; gap:4px; }
.wc-sim-label       { color:#8e9297; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.wc-sim-select      { padding:9px 12px; border-radius:8px; background:#2f3136; border:1px solid rgba(255,255,255,0.1);
                      color:#dcddde; font-size:13px; cursor:pointer; min-width:160px; }
.wc-sim-select:hover { border-color:rgba(255,255,255,0.25); }

.wc-sim-meta        { display:flex; flex-wrap:wrap; gap:12px; padding:12px 16px;
                      background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07);
                      border-radius:8px; margin-bottom:20px; font-size:13px; color:#b9bbbe; }
.wc-sim-badge       { display:inline-flex; align-items:center; gap:6px; padding:4px 10px;
                      border-radius:999px; background:rgba(88,101,242,0.18);
                      border:1px solid rgba(88,101,242,0.35); color:#c7d2fe; font-weight:600; font-size:12px; }

.wc-sim-tabs        { display:flex; gap:8px; margin-bottom:18px; border-bottom:1px solid rgba(255,255,255,0.08); }
.wc-sim-tab-btn      { padding:9px 16px; background:none; border:none; color:#8e9297; font-size:13px; font-weight:600;
                      cursor:pointer; border-bottom:2px solid transparent; }
.wc-sim-tab-btn.active { color:#fff; border-bottom-color:#5865F2; }
.wc-sim-tab-btn:hover  { color:#dcddde; }
.wc-sim-tabpane      { display:none; }
.wc-sim-tabpane.active { display:block; }

.wc-sim-table       { width:100%; border-collapse:collapse; font-size:13px; }
.wc-sim-table th    { background:#222; color:#fff; padding:9px 10px; white-space:nowrap;
                      border-bottom:2px solid #444; position:sticky; top:0; z-index:10; }
.wc-sim-table td    { padding:8px 10px; border-bottom:1px solid #2a2a2a; vertical-align:middle; }
.wc-sim-table tr:hover td { background:rgba(255,255,255,0.03); }

.wc-rdist           { display:flex; width:130px; height:14px; border-radius:4px; overflow:hidden; background:#1a1a1a; }
.wc-rdist-seg       { height:100%; }

.wc-group-badge     { display:inline-block; padding:2px 6px; border-radius:4px;
                      background:rgba(255,255,255,0.08); color:#99aab5; font-size:11px; font-weight:600; }
.wc-rank-num        { font-weight:700; color:#888; font-size:12px; }

.wc-legend-grid     { display:flex; flex-wrap:wrap; gap:14px; margin-top:16px; font-size:12px; }
.wc-legend-item     { display:flex; align-items:center; gap:6px; }
.wc-legend-dot      { width:14px; height:14px; border-radius:3px; flex-shrink:0; }

.wc-highlight-eng td { background:rgba(67,181,129,0.06) !important; }

/* Bracket round columns */
.wc-bracket-rounds   { display:flex; gap:18px; overflow-x:auto; padding-bottom:8px; }
.wc-bracket-col      { min-width:240px; flex:1; }
.wc-bracket-col-title{ color:#fff; font-size:13px; font-weight:700; text-transform:uppercase;
                      letter-spacing:.5px; margin-bottom:10px; padding-bottom:6px;
                      border-bottom:2px solid rgba(255,255,255,0.1); }
.wc-bracket-card     { background:#2f3136; border:1px solid rgba(255,255,255,0.08); border-radius:8px;
                      padding:10px 12px; margin-bottom:10px; }
.wc-bracket-pair     { display:flex; justify-content:space-between; align-items:center; font-size:13px; }
.wc-bracket-team     { color:#dcddde; }
.wc-bracket-team.fav { color:#43b581; font-weight:700; }
.wc-bracket-pct      { font-size:11px; color:#888; font-weight:600; }
.wc-bracket-freq     { margin-top:6px; font-size:11px; color:#72767d; }

/* Team path lookup */
.wc-path-select      { padding:9px 12px; border-radius:8px; background:#2f3136; border:1px solid rgba(255,255,255,0.1);
                      color:#dcddde; font-size:13px; min-width:220px; margin-bottom:16px; }
.wc-path-step        { display:flex; align-items:center; gap:10px; padding:10px 14px; background:#2f3136;
                      border:1px solid rgba(255,255,255,0.08); border-radius:8px; margin-bottom:8px; }
.wc-path-round-tag    { min-width:110px; font-size:11px; font-weight:700; text-transform:uppercase;
                      color:#99aab5; letter-spacing:.5px; }
.wc-path-arrow       { color:#555; }
.wc-path-empty       { color:#888; font-size:13px; padding:20px; text-align:center; }

/* Methodology note */
.wc-sim-table   { width:100%; border-collapse:collapse; font-size:13px; }

.wc-sim-tab-content-wrap { display:none; }
</style>

<div class="panel">
    <h2>🎲 World Cup 2026 – Monte Carlo Tournament Simulation</h2>
    <p class="update-info" style="margin-top:6px;">
        Simulates the full tournament <?= number_format($n_sims) ?> times using a Poisson goal model,
        including the full knockout bracket (R32 → R16 → QF → SF → Final).
        Team strength is derived from current group-stage performance (goals scored/conceded per match).
    </p>

    <!-- Controls -->
    <form method="get" style="margin-top:16px;">
        <input type="hidden" name="tab"    value="world-cup">
        <input type="hidden" name="subtab" value="simulation">
        <div class="wc-sim-controls">
            <div class="wc-sim-ctrl-group">
                <label class="wc-sim-label" for="wc-nsims">Simulations</label>
                <select id="wc-nsims" name="n_sims" class="wc-sim-select" onchange="this.form.submit()">
                    <?php foreach ($n_sims_allowed as $opt): ?>
                        <option value="<?= $opt ?>" <?= $n_sims === $opt ? 'selected' : '' ?>>
                            <?= number_format($opt) ?> runs
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>

    <!-- Metadata bar -->
    <div class="wc-sim-meta">
        <span class="wc-sim-badge">🎲 Monte Carlo</span>
        <span><strong><?= number_format($n_sims) ?></strong> iterations per team</span>
        <span>Avg goals/team/match: <strong><?= number_format($avg_goals, 2) ?></strong></span>
        <span>48 teams · 12 groups · R32 → R16 → QF → SF → Final</span>
        <?php if (!$has_played): ?>
            <span style="color:#faa61a;">⚡ Pre-tournament: strengths derived entirely from FIFA rankings</span>
        <?php else: ?>
            <span style="color:#43b581;">📊 Strengths blend FIFA rankings + actual group-stage results</span>
        <?php endif; ?>
        <?php if (!$has_data): ?>
            <span style="color:#f04747;">⚠️ No group data loaded — update World Cup data first</span>
        <?php endif; ?>
    </div>

    <?php if (!$has_data): ?>
        <div style="text-align:center; padding:40px; background:#2a2a2a; border-radius:8px;">
            <div style="font-size:48px; margin-bottom:15px;">🌍</div>
            <h3 style="color:#00ff88; margin-bottom:10px;">World Cup Data Required</h3>
            <p style="color:#888;">Use the <strong>Update World Cup Data</strong> button above to load group standings, then return here to run simulations.</p>
        </div>
    <?php else: ?>

    <!-- Sub-tabs: Probabilities / Bracket / Team Path -->
    <div class="wc-sim-tabs">
        <button type="button" class="wc-sim-tab-btn active" data-wcpane="probs">Win Probabilities</button>
        <button type="button" class="wc-sim-tab-btn" data-wcpane="bracket">Most Likely Bracket</button>
        <button type="button" class="wc-sim-tab-btn" data-wcpane="path">Team Path Explorer</button>
    </div>

    <!-- ── PANE 1: Probabilities table (unchanged) ─────────────────────────── -->
    <div class="wc-sim-tabpane active" data-wcpane-content="probs">
    <div class="table-container">
    <table class="wc-sim-table">
        <thead>
            <tr>
                <th style="text-align:center;">#</th>
                <th style="text-align:left;">Team</th>
                <th style="text-align:center;" title="Current group position">Grp</th>
                <th style="text-align:center; color:#99aab5;" title="FIFA ranking used as pre-tournament prior">FIFA</th>
                <th style="text-align:center; color:#43b581;" title="Probability of advancing from group stage">P(Qualify)</th>
                <th style="text-align:center; color:#faa61a;" title="Probability of reaching Round of 16 or further">P(R16+)</th>
                <th style="text-align:center; color:#5865F2;" title="Probability of reaching quarter-final or further">P(QF+)</th>
                <th style="text-align:center; color:#2d9cdb;" title="Probability of reaching semi-final or further">P(SF+)</th>
                <th style="text-align:center; color:#aaaaaa;" title="Probability of reaching the final">P(Final)</th>
                <th style="text-align:center; color:#FFD700; font-weight:700;" title="Probability of winning the World Cup">P(Win)</th>
                <th style="text-align:center;">Distribution</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $rank = 1;
        foreach ($team_display as $team => $t):
            $is_england = ($team === 'England');
            $row_class  = $is_england ? 'wc-highlight-eng' : '';
        ?>
        <tr class="<?= $row_class ?>">
            <td style="text-align:center;" class="wc-rank-num"><?= $rank ?></td>
            <td>
                <span style="font-weight:<?= $is_england ? '700' : '400' ?>; color:<?= $is_england ? '#43b581' : '#dcddde' ?>;">
                    <?= htmlspecialchars($team, ENT_QUOTES, 'UTF-8') ?>
                    <?= $is_england ? ' 🏴󠁧󠁢󠁥󠁮󠁧󠁿' : '' ?>
                </span>
            </td>
            <td style="text-align:center;">
                <span class="wc-group-badge" title="<?= htmlspecialchars($t['group']) ?>">
                    <?= htmlspecialchars(str_replace('Group ', '', $t['group'])) ?>#<?= $t['pos'] ?>
                </span>
            </td>
            <td style="text-align:center; color:#99aab5; font-size:12px;"
                title="FIFA rank <?= (int)$t['fifa_rank'] ?>">
                #<?= (int)$t['fifa_rank'] ?>
            </td>

            <?php foreach ([
                ['p_qual',  '#43b581'],
                ['p_r16',   '#faa61a'],
                ['p_qf',    '#5865F2'],
                ['p_sf',    '#2d9cdb'],
                ['p_final', '#aaaaaa'],
            ] as [$key, $base_color]):
                $v = $t[$key];
                $col = wc_pct_color($v);
            ?>
            <td style="text-align:center;">
                <span style="color:<?= $col ?>; font-weight:<?= $v >= 0.15 ? '600' : '400' ?>;">
                    <?= wc_pct($v) ?>
                </span>
            </td>
            <?php endforeach; ?>

            <!-- Win probability (most prominent column) -->
            <td style="text-align:center;">
                <span style="color:<?= $t['p_win'] > 0 ? '#FFD700' : '#444' ?>; font-weight:700; font-size:14px;">
                    <?= wc_pct($t['p_win']) ?>
                </span>
            </td>

            <!-- Round distribution bar -->
            <td style="text-align:center;">
                <div class="wc-rdist" title="<?php
                    foreach ($round_labels as $ri => $rl) {
                        $cnt = $t['counts'][$ri];
                        if ($cnt > 0) echo wc_esc($rl) . ': ' . round($cnt/$n_sims*100,1) . '% | ';
                    }
                ?>">
                    <?php foreach ($round_colors as $ri => $col):
                        $cnt = $t['counts'][$ri] ?? 0;
                        $w   = $n_sims > 0 ? ($cnt / $n_sims * 100) : 0;
                        if ($w < 0.5) continue;
                    ?>
                    <div class="wc-rdist-seg"
                         style="width:<?= number_format($w, 2) ?>%; background:<?= $col ?>;"
                         title="<?= htmlspecialchars($round_labels[$ri]) ?>: <?= number_format($w, 1) ?>%"></div>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <?php $rank++; endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- Legend -->
    <div class="wc-legend-grid">
        <?php foreach ($round_colors as $ri => $col): ?>
        <div class="wc-legend-item">
            <div class="wc-legend-dot" style="background:<?= $col ?>;"></div>
            <span><?= htmlspecialchars($round_labels[$ri]) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="wc-legend-item" style="margin-left:auto; color:#888; font-size:11px;">
            💡 Hover distribution bars for exact percentages
        </div>
    </div>
    </div><!-- /pane: probs -->

    <!-- ── PANE 2: Most Likely Bracket ──────────────────────────────────────── -->
    <div class="wc-sim-tabpane" data-wcpane-content="bracket">
        <p style="color:#888; font-size:12px; margin-bottom:14px;">
            For each knockout round, the pairings that occurred most often across all <?= number_format($n_sims) ?> simulations,
            with the favourite's win rate <em>in that specific matchup</em>. Because qualifiers themselves vary sim-to-sim,
            this is a snapshot of the most common collisions — not a single deterministic bracket.
        </p>
        <div class="wc-bracket-rounds">
            <?php foreach ([1,2,3,4,5] as $lvl):
                $pairs = $top_matchups_by_level[$lvl];
            ?>
            <div class="wc-bracket-col">
                <div class="wc-bracket-col-title"><?= htmlspecialchars($wc_round_names[$lvl]) ?></div>
                <?php if (empty($pairs)): ?>
                    <div class="wc-path-empty">No matches simulated at this round.</div>
                <?php else: foreach ($pairs as $p):
                    $t1 = $p['t1']; $t2 = $p['t2'];
                    $t1_rate = $p['count'] > 0 ? $p['t1_wins'] / $p['count'] : 0;
                    $t2_rate = $p['count'] > 0 ? $p['t2_wins'] / $p['count'] : 0;
                    $t1_fav  = $t1_rate >= $t2_rate;
                    $freq_pct = $n_sims > 0 ? ($p['count'] / $n_sims * 100) : 0;
                ?>
                <div class="wc-bracket-card">
                    <div class="wc-bracket-pair">
                        <span class="wc-bracket-team <?= $t1_fav ? 'fav' : '' ?>"><?= htmlspecialchars($t1) ?></span>
                        <span class="wc-bracket-pct"><?= wc_pct($t1_rate) ?></span>
                    </div>
                    <div class="wc-bracket-pair" style="margin-top:4px;">
                        <span class="wc-bracket-team <?= !$t1_fav ? 'fav' : '' ?>"><?= htmlspecialchars($t2) ?></span>
                        <span class="wc-bracket-pct"><?= wc_pct($t2_rate) ?></span>
                    </div>
                    <div class="wc-bracket-freq">Occurred in <?= number_format($freq_pct, 1) ?>% of sims</div>
                </div>
                <?php endforeach; endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div><!-- /pane: bracket -->

    <!-- ── PANE 3: Team Path Explorer ───────────────────────────────────────── -->
    <div class="wc-sim-tabpane" data-wcpane-content="path">
        <p style="color:#888; font-size:12px; margin-bottom:10px;">
            Pick a team to see its single most likely route through the knockout stage: the opponent it faces most
            often at each round (conditional on reaching that round), and its win rate against that specific opponent.
        </p>
        <select id="wc-team-path-select" class="wc-path-select">
            <option value="">— Select a team —</option>
            <?php foreach ($team_display as $team => $t): ?>
                <option value="<?= htmlspecialchars($team, ENT_QUOTES, 'UTF-8') ?>" <?= $team === 'England' ? 'selected' : '' ?>>
                    <?= htmlspecialchars($team, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div id="wc-team-path-results">
            <?php
            $data_for_js = [];
            foreach ($likely_paths as $team => $steps) {
                $rows = [];
                foreach ($steps as $step) {
                    $lvl   = $step['round'];
                    $opp   = $step['opponent'];
                    $share = $step['opp_share'];
                    $reach_count = $step['level_reach_pct'];
                    $reach_pct   = $n_sims > 0 ? $reach_count / $n_sims : 0;
                    // win rate of $team vs this specific $opp at this level
                    $key = strcmp($team, $opp) <= 0 ? ($team.'|'.$opp) : ($opp.'|'.$team);
                    $mp  = $matchup_counts[$lvl][$key] ?? null;
                    $win_rate = null;
                    if ($mp) {
                        $win_rate = ($mp['t1'] === $team)
                            ? ($mp['count'] > 0 ? $mp['t1_wins'] / $mp['count'] : 0)
                            : ($mp['count'] > 0 ? $mp['t2_wins'] / $mp['count'] : 0);
                    }
                    $rows[] = [
                        'round'      => $wc_round_names[$lvl],
                        'opponent'   => $opp,
                        'reach_pct'  => round($reach_pct * 100, 1),
                        'opp_share'  => round($share * 100, 1),
                        'win_rate'   => $win_rate !== null ? round($win_rate * 100, 1) : null,
                    ];
                }
                $data_for_js[$team] = $rows;
            }
            ?>
            <script>window.__wcTeamPaths = <?= json_encode($data_for_js, JSON_UNESCAPED_UNICODE) ?>;</script>
            <div class="wc-path-empty">Select a team above to view their projected path.</div>
        </div>
    </div><!-- /pane: path -->

    <!-- Methodology note -->
    <div style="margin-top:20px; padding:14px 16px; background:#40444b; border-radius:8px; border-left:4px solid #5865F2;">
        <strong style="color:#FFCD00;">Methodology:</strong>
        <span style="color:#dcddde; font-size:13px;">
            Each simulation runs the full tournament using a Poisson goal model
            (expected goals = team attack × opponent defence × league average).
            The best 8 third-place teams advance alongside the 24 group qualifiers (top 2 per group).
            Knockout ties are resolved by a weighted penalty coin-flip.
            Every knockout match across all <?= number_format($n_sims) ?> simulations is recorded — who played whom
            and who won — which powers the bracket and team-path views above.
            <?php if (!$has_played): ?>
                <strong style="color:#faa61a;">Pre-tournament mode:</strong>
                team strengths are derived entirely from the November 2025 FIFA rankings
                (rank 1 Spain → attack 1.75 / defence 0.50; rank 48 → attack 0.50 / defence 1.75).
            <?php else: ?>
                Team strengths are a <strong>blended rating</strong>: FIFA rankings seed the prior,
                then actual group-stage goals scored/conceded progressively take over
                (0 games = 100% FIFA; 1 game = ⅓ actual; 2 games = ⅔ actual; 3 games = 100% actual).
            <?php endif; ?>
        </span>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    // Sub-tab switching
    var tabBtns = document.querySelectorAll('.wc-sim-tab-btn');
    var panes   = document.querySelectorAll('.wc-sim-tabpane');
    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var target = btn.getAttribute('data-wcpane');
            tabBtns.forEach(function(b) { b.classList.remove('active'); });
            panes.forEach(function(p) { p.classList.remove('active'); });
            btn.classList.add('active');
            document.querySelector('[data-wcpane-content="' + target + '"]').classList.add('active');
        });
    });

    // Team path explorer
    var select = document.getElementById('wc-team-path-select');
    var results = document.getElementById('wc-team-path-results');
    if (!select || !results) return;

    function renderPath(team) {
        var steps = (window.__wcTeamPaths || {})[team];
        if (!team || !steps || steps.length === 0) {
            results.innerHTML = '<div class="wc-path-empty">' +
                (team ? 'This team did not reach the knockout stage in any simulation.' : 'Select a team above to view their projected path.') +
                '</div>';
            return;
        }
        var html = '';
        steps.forEach(function(s) {
            var winTxt = (s.win_rate !== null) ? (s.win_rate + '% to advance') : '';
            html += '<div class="wc-path-step">' +
                '<div class="wc-path-round-tag">' + s.round + '</div>' +
                '<div style="flex:1;">' +
                    '<div style="color:#dcddde; font-size:13px; font-weight:600;">vs ' + s.opponent + '</div>' +
                    '<div style="color:#888; font-size:11px; margin-top:2px;">' +
                        'Most common opponent in ' + s.opp_share + '% of sims reaching this round &middot; ' +
                        'Team reached this round in ' + s.reach_pct + '% of all sims' +
                    '</div>' +
                '</div>' +
                (winTxt ? '<div style="color:#43b581; font-weight:700; font-size:13px;">' + winTxt + '</div>' : '') +
            '</div>';
        });
        results.innerHTML = html;
    }

    select.addEventListener('change', function() { renderPath(this.value); });
    if (select.value) renderPath(select.value);
})();
</script>
