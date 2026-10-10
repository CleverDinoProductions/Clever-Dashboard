<?php
/** Derive outlook targets from typed Admin zones, independently of their labels. */
function match_projection_parse(array $simulation, array $zones): ?array
{
    $teams = array_values($simulation['teams'] ?? []);
    if (!$teams) return null;
    usort($teams, static fn($a, $b) => ($a['avg_final_position'] ?? PHP_INT_MAX) <=> ($b['avg_final_position'] ?? PHP_INT_MAX));
    $targets = [];
    foreach ($zones as $zone) {
        $key = $zone['key'] ?? '';
        if ($key === 'relegation') {
            $position = (int)$zone['from'] - 1;
            $label = 'Safety';
            $key = 'safety';
        } elseif (in_array($key, ['champions-league', 'europa-league', 'conference-league', 'automatic-promotion', 'playoffs', 'custom'], true)) {
            $position = (int)$zone['to'];
            $label = $zone['name'];
        } else {
            continue;
        }
        // Missing teams cannot support a target at the configured cut line.
        if ($position < 1 || $position > count($teams)) continue;
        $team = $teams[$position - 1];
        $targets[] = ['key' => $key, 'label' => $label, 'position' => $position,
            'target' => (int)ceil((float)($team['avg_final_points'] ?? 0)), 'team' => $team['team'] ?? ''];
    }
    $rows = [];
    foreach ($teams as $team) {
        $currentPoints = (int)($team['current_points'] ?? 0);
        $rows[] = ['team' => $team['team'] ?? 'Unknown team', 'current_points' => $currentPoints,
            'projected_points' => (float)($team['avg_final_points'] ?? 0),
            'projected_position' => (float)($team['avg_final_position'] ?? 0),
            'points_needed' => array_map(static fn($target) => max(0, $target['target'] - $currentPoints), $targets)];
    }
    return ['targets' => $targets, 'rows' => $rows, 'iterations' => (int)($simulation['n_sims'] ?? 0)];
}

function match_projection_render(array $simulation, array $zones): void
{
    $projection = match_projection_parse($simulation, $zones);
    if ($projection === null) return;
    $escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    ?>
    <section class="my-6 overflow-hidden rounded-2xl border border-slate-700/80 bg-slate-950 text-slate-100 shadow-2xl shadow-black/20" aria-labelledby="match-projection-title">
        <div class="border-b border-slate-800 bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 px-5 py-5 sm:px-7">
            <p class="mb-1 text-xs font-bold uppercase tracking-[0.22em] text-indigo-300">Simulation outlook</p>
            <h3 id="match-projection-title" class="m-0 text-2xl font-black tracking-tight text-white">Projected points and targets</h3>
            <p class="mt-2 text-sm leading-6 text-slate-400">Estimated points targets at the configured league cut lines. These model estimates do not guarantee qualification or safety.</p>
            <span class="text-xs font-semibold text-indigo-200"><?= number_format($projection['iterations']) ?> simulations</span>
        </div>
        <?php if ($projection['targets']): ?>
        <div class="grid gap-px bg-slate-800 md:grid-cols-2">
            <?php foreach ($projection['targets'] as $target): ?>
            <article class="bg-indigo-950/35 p-5 sm:p-7">
                <p class="m-0 text-xs font-bold uppercase tracking-wide text-indigo-300"><?= $escape($target['label']) ?> line · position <?= $target['position'] ?></p>
                <p class="my-2 text-5xl font-black tabular-nums text-white"><?= $target['target'] ?><span class="ml-2 text-lg font-bold text-indigo-300">pts</span></p>
                <p class="m-0 text-sm text-slate-400">Modelled from <?= $escape($target['team']) ?> at this cut line.</p>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="px-5 py-3 text-sm text-slate-400">No qualification, promotion, playoff or relegation targets are configured for this league and season.</p>
        <?php endif; ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[680px] border-collapse text-left text-sm">
                <thead class="bg-slate-900/80 text-xs uppercase tracking-wider text-slate-400">
                    <tr><th class="px-5 py-3">Team</th><th class="px-4 py-3 text-center">Now</th><th class="px-4 py-3 text-center">Projected</th>
                    <?php foreach ($projection['targets'] as $target): ?><th class="px-4 py-3 text-center text-indigo-300">To <?= $escape($target['label']) ?></th><?php endforeach; ?></tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                <?php foreach ($projection['rows'] as $row): ?>
                    <tr class="bg-slate-950 hover:bg-slate-900/80">
                        <th scope="row" class="px-5 py-3 font-bold"><?= $escape($row['team']) ?><span class="ml-2 text-xs font-normal text-slate-400">#<?= number_format($row['projected_position'], 1) ?></span></th>
                        <td class="px-4 py-3 text-center tabular-nums text-slate-400"><?= $row['current_points'] ?></td>
                        <td class="px-4 py-3 text-center font-bold tabular-nums"><?= number_format($row['projected_points'], 1) ?></td>
                        <?php foreach ($row['points_needed'] as $needed): ?><td class="px-4 py-3 text-center"><span class="rounded-full bg-indigo-400/10 px-2.5 py-1 font-bold tabular-nums text-indigo-300"><?= $needed === 0 ? 'Met' : '+' . $needed ?></span></td><?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php
}
