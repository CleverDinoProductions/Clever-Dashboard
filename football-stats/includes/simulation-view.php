<?php
/**
 * Shared rendering for the league simulation tab.
 *
 * Expected variables in scope when included:
 *   $db              – PDO instance
 *   $league_config   – array with keys:
 *                        comp_code, season_label, total_games, n_teams,
 *                        league_name, zones, prob_columns
 *   $currentMainTab, $currentLeague, $currentSubTab
 */

require_once __DIR__ . '/simulation-engine.php';

$comp_code    = $league_config['comp_code'];
$season_label = $league_config['season_label'];

// ── Read URL parameters ──────────────────────────────────────────────────────

// Get matchweeks available for this season
$mw_stmt = $db->prepare(
    'SELECT DISTINCT matchweek FROM matches
     WHERE competition_code = ? AND season_label = ?
     ORDER BY matchweek ASC'
);
$mw_stmt->execute([$comp_code, $season_label]);
$all_mws = $mw_stmt->fetchAll(PDO::FETCH_COLUMN);

// Default to the first unplayed matchweek
$nu_stmt = $db->prepare(
    "SELECT MIN(matchweek) AS min_mw FROM matches
     WHERE competition_code = ? AND season_label = ?
       AND (home_goals IS NULL OR away_goals IS NULL)"
);
$nu_stmt->execute([$comp_code, $season_label]);
$nu_row     = $nu_stmt->fetch(PDO::FETCH_ASSOC);
$default_mw = $nu_row['min_mw'] ?? (empty($all_mws) ? 1 : (int) max($all_mws));

$sim_from = isset($_GET['sim_from']) && is_numeric($_GET['sim_from'])
    ? max(1, (int) $_GET['sim_from'])
    : (int) $default_mw;

$n_sims_allowed = [500, 1000, 10000, 100000];
$n_sims = isset($_GET['n_sims']) && in_array((int) $_GET['n_sims'], $n_sims_allowed, true)
    ? (int) $_GET['n_sims']
    : 1000;

// ── Run simulation ───────────────────────────────────────────────────────────
ini_set('max_execution_time', 60);
$sim = sim_run_monte_carlo($db, $comp_code, $season_label, $sim_from, $n_sims);
$teams = $sim['teams'];

// ── Helper: build URL preserving tab context ─────────────────────────────────
function sim_view_url($sim_from, $n_sims) {
    global $currentMainTab, $currentLeague, $currentSubTab;
    return '?' . http_build_query([
        'tab'      => $currentMainTab  ?? '2025-2026',
        'league'   => $currentLeague   ?? 'premier-league',
        'subtab'   => $currentSubTab   ?? 'simulation',
        'sim_from' => $sim_from,
        'n_sims'   => $n_sims,
    ]);
}

// ── Helper: probability formatted as percentage string ───────────────────────
function sim_pct($count, $total) {
    if ($total === 0) return '–';
    $v = $count / $total * 100;
    if ($v === 0.0)    return '0%';
    if ($v >= 99.95)   return '>99%';
    if ($v < 0.05)     return '<0.1%';
    return number_format($v, 1) . '%';
}

// ── Helper: get zone config for a position ───────────────────────────────────
function sim_zone_for_pos($pos, array $zones) {
    foreach ($zones as $z) {
        if ($pos >= $z['from'] && $pos <= $z['to']) return $z;
    }
    return ['name' => 'Unknown', 'color' => '#888', 'bg' => 'transparent', 'emoji' => ''];
}

// ── Helper: calculate zone probability across simulations ────────────────────
function sim_zone_prob(array $pos_dist, int $from, int $to, int $n_sims) {
    $count = 0;
    for ($p = $from; $p <= $to; $p++) {
        $count += $pos_dist[$p] ?? 0;
    }
    return $count;
}

$zones        = $league_config['zones'];
$prob_columns = $league_config['prob_columns'];
$n_teams      = $league_config['n_teams'];
?>

<style>
.sim-controls { display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end; margin-bottom:20px; }
.sim-control-group { display:flex; flex-direction:column; gap:4px; }
.sim-label { color:#8e9297; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.sim-select { padding:9px 12px; border-radius:8px; background:#2f3136; border:1px solid rgba(255,255,255,0.1);
              color:#dcddde; font-size:13px; cursor:pointer; min-width:160px; }
.sim-select:hover { border-color:rgba(255,255,255,0.25); }

.sim-meta { display:flex; flex-wrap:wrap; gap:12px; padding:12px 16px; background:rgba(255,255,255,0.03);
            border:1px solid rgba(255,255,255,0.07); border-radius:8px; margin-bottom:20px;
            font-size:13px; color:#b9bbbe; }
.sim-meta-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px;
                   border-radius:999px; background:rgba(88,101,242,0.18);
                   border:1px solid rgba(88,101,242,0.35); color:#c7d2fe; font-weight:600; font-size:12px; }

.sim-table { width:100%; border-collapse:collapse; font-size:13px; }
.sim-table th { background:#222; color:#fff; padding:9px 10px; white-space:nowrap;
                border-bottom:2px solid #444; position:sticky; top:0; z-index:10; }
.sim-table td { padding:8px 10px; border-bottom:1px solid #2a2a2a; vertical-align:middle; }
.sim-table tr:hover td { background:rgba(255,255,255,0.03); }
.sim-table .team-cell { display:flex; align-items:center; gap:8px; text-align:left; }
.sim-table .team-crest { width:22px; height:22px; object-fit:contain; }

.pos-dist-bar { display:flex; width:140px; height:14px; border-radius:4px; overflow:hidden;
                background:#333; gap:0; }
.pos-dist-seg { height:100%; transition:opacity .15s; }
.pos-dist-seg:hover { opacity:.8; }

.sim-legend { display:flex; flex-wrap:wrap; gap:12px; margin-top:20px; font-size:12px; }
.sim-legend-item { display:flex; align-items:center; gap:6px; }
.sim-legend-dot { width:12px; height:12px; border-radius:3px; flex-shrink:0; }

.prob-high   { color:#43b581; font-weight:600; }
.prob-medium { color:#faa61a; font-weight:600; }
.prob-low    { color:#f04747; font-weight:600; }
.prob-zero   { color:#555; }
</style>

<div class="panel">
    <h2>🎲 <?= htmlspecialchars($league_config['league_name'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($league_config['season_label'], ENT_QUOTES, 'UTF-8') ?> – Season Simulation</h2>
    <p class="update-info" style="margin-top:6px;">
        Monte Carlo simulation using a Poisson goal model based on each team's attack and defensive strength.
    </p>

    <!-- Controls -->
    <form method="get" style="margin-top:16px;">
        <?php foreach (['tab','league','subtab'] as $p): ?>
            <?php if (isset($_GET[$p])): ?>
                <input type="hidden" name="<?= htmlspecialchars($p) ?>" value="<?= htmlspecialchars($_GET[$p]) ?>">
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="sim-controls">
            <div class="sim-control-group">
                <label class="sim-label" for="sim-from-select">Simulate from matchweek</label>
                <select id="sim-from-select" name="sim_from" class="sim-select" onchange="this.form.submit()">
                    <?php foreach ($all_mws as $mw): ?>
                        <option value="<?= (int)$mw ?>" <?= ($sim_from == $mw) ? 'selected' : '' ?>>
                            MW<?= (int)$mw ?>
                            <?php if ($mw == $sim['next_unplayed_mw']): ?> (next unplayed)<?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if (empty($all_mws)): ?>
                        <option value="1" selected>MW1</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="sim-control-group">
                <label class="sim-label" for="sim-nsims-select">Simulations</label>
                <select id="sim-nsims-select" name="n_sims" class="sim-select" onchange="this.form.submit()">
                    <?php foreach ($n_sims_allowed as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($n_sims === $opt) ? 'selected' : '' ?>><?= number_format($opt) ?> runs</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>

    <!-- Simulation metadata -->
    <div class="sim-meta">
        <span class="sim-meta-badge">🎲 Monte Carlo</span>
        <span>Simulating from <strong>MW<?= $sim['sim_from_mw'] ?></strong></span>
        <span><strong><?= $sim['n_remaining'] ?></strong> matches to simulate</span>
        <span><strong><?= number_format($sim['n_sims']) ?></strong> iterations</span>
        <span>League avg: <strong><?= number_format($sim['avg_home_goals'], 2) ?></strong> home goals,
              <strong><?= number_format($sim['avg_away_goals'], 2) ?></strong> away goals per match</span>
        <?php if ($sim['n_completed'] === 0): ?>
            <span style="color:#faa61a;">⚠️ No completed matches – using neutral team strengths</span>
        <?php endif; ?>
    </div>

    <!-- Results table -->
    <div class="table-container">
    <table class="sim-table">
        <thead>
            <tr>
                <th title="Average simulated final position">Avg Pos</th>
                <th style="text-align:left;">Team</th>
                <th title="Points at simulation start matchweek">Pts Now</th>
                <th title="Average simulated final points">Avg Pts</th>
                <th title="10th–90th percentile points range">Pts Range</th>
                <?php foreach ($prob_columns as $col): ?>
                    <th style="color:<?= htmlspecialchars($col['color']) ?>;" title="<?= htmlspecialchars($col['label']) ?>">
                        <?= htmlspecialchars($col['label']) ?>
                    </th>
                <?php endforeach; ?>
                <th title="Probability distribution across all positions">Distribution</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $rank = 1;
        foreach ($teams as $team_name => $t):
            $zone        = sim_zone_for_pos((int) round($t['avg_final_position']), $zones);
            $row_bg      = $zone['bg'];
            $current_pos = $t['current_position'];

            // Position change indicator
            $avg_pos_rounded = (int) round($t['avg_final_position']);
            if ($current_pos < $avg_pos_rounded) {
                $pos_arrow = '<span style="color:#f04747;font-size:11px;" title="Likely to drop">▼</span>';
            } elseif ($current_pos > $avg_pos_rounded) {
                $pos_arrow = '<span style="color:#43b581;font-size:11px;" title="Likely to rise">▲</span>';
            } else {
                $pos_arrow = '';
            }
        ?>
        <tr style="<?= $row_bg !== 'transparent' ? "background:$row_bg;" : '' ?>">
            <td style="font-weight:600; color:<?= htmlspecialchars($zone['color']) ?>; text-align:center;">
                <?= number_format($t['avg_final_position'], 1) ?><?= $pos_arrow ?>
            </td>
            <td>
                <div class="team-cell">
                    <span style="color:#888; font-size:11px; min-width:18px;"><?= $t['current_position'] ?></span>
                    <span><?= htmlspecialchars($team_name, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </td>
            <td style="text-align:center; font-weight:600;"><?= $t['current_points'] ?></td>
            <td style="text-align:center; font-weight:700; color:<?= htmlspecialchars($zone['color']) ?>;">
                <?= $t['avg_final_points'] ?>
            </td>
            <td style="text-align:center; color:#888; font-size:12px;">
                <?= $t['p10_final_points'] ?>–<?= $t['p90_final_points'] ?>
            </td>

            <?php foreach ($prob_columns as $col): ?>
            <?php
                $count = sim_zone_prob($t['pos_dist'], $col['from'], $col['to'], $n_sims);
                $pct_str = sim_pct($count, $n_sims);
                $pct_val = $n_sims > 0 ? $count / $n_sims * 100 : 0;
                if ($pct_val >= 60)     $pct_class = 'prob-high';
                elseif ($pct_val >= 20) $pct_class = 'prob-medium';
                elseif ($pct_val <= 0)  $pct_class = 'prob-zero';
                else                    $pct_class = 'prob-low';
            ?>
            <td style="text-align:center;">
                <span class="<?= $pct_class ?>"><?= htmlspecialchars($pct_str) ?></span>
            </td>
            <?php endforeach; ?>

            <!-- Distribution bar: one segment per zone, width = P(zone) -->
            <td style="text-align:center;">
                <div class="pos-dist-bar">
                <?php foreach ($zones as $z): ?>
                <?php
                    $z_count = sim_zone_prob($t['pos_dist'], $z['from'], $z['to'], $n_sims);
                    $z_pct   = $n_sims > 0 ? $z_count / $n_sims * 100 : 0;
                    if ($z_pct < 0.5) continue;
                    $z_label_pct = number_format($z_pct, 1);
                ?>
                <div class="pos-dist-seg"
                     style="width:<?= $z_pct ?>%; background:<?= htmlspecialchars($z['color']) ?>;"
                     title="<?= htmlspecialchars($z['name']) ?>: <?= $z_label_pct ?>%"></div>
                <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <?php $rank++; endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- Legend -->
    <div class="sim-legend">
        <?php foreach ($zones as $z): ?>
        <div class="sim-legend-item">
            <div class="sim-legend-dot" style="background:<?= htmlspecialchars($z['color']) ?>;"></div>
            <span><?= htmlspecialchars($z['emoji'] . ' ' . $z['name'] . ' (' . $z['from'] . ($z['to'] > $z['from'] ? '–' . $z['to'] : '') . ')', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php endforeach; ?>
        <div class="sim-legend-item" style="margin-left:auto; color:#888; font-size:11px;">
            💡 Hover distribution bar segments for zone probabilities
        </div>
    </div>

    <div style="margin-top:16px; padding:12px 16px; background:#40444b; border-radius:8px; border-left:4px solid #5865F2;">
        <strong style="color:#FFCD00;">Methodology:</strong>
        <span style="color:#dcddde; font-size:13px;">
            Each simulation uses a Poisson goal model. Expected goals per match are derived from each team's
            attack and defensive strength (goals scored/conceded per game) relative to the league average.
            Home advantage is reflected through separate home and away goal averages.
            The "Pts Range" column shows the 10th–90th percentile interval across all simulations.
        </span>
    </div>
</div>
