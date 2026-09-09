<?php
/**
 * Build and render the safety/Europe summary shown above simulation results.
 *
 * The parser deliberately consumes the public simulation result shape instead
 * of querying the database, so it works for matchweek and date simulations.
 */

if (!function_exists('match_projection_parse')) {
    function match_projection_parse(array $simulation, array $zones): ?array
    {
        $teams = array_values($simulation['teams'] ?? []);
        if (empty($teams)) {
            return null;
        }

        $relegationZone = null;
        $europeCutoff = null;
        foreach ($zones as $zone) {
            $name = strtolower((string) ($zone['name'] ?? ''));
            if (strpos($name, 'relegat') !== false) {
                $relegationZone = $zone;
            }
            if (preg_match('/champions league|europa|conference|europe/', $name)) {
                $europeCutoff = max($europeCutoff ?? 0, (int) ($zone['to'] ?? 0));
            }
        }

        if ($relegationZone === null || $europeCutoff === null) {
            return null;
        }

        usort($teams, static function (array $a, array $b): int {
            return ($a['avg_final_position'] ?? PHP_INT_MAX) <=> ($b['avg_final_position'] ?? PHP_INT_MAX);
        });

        $safetyCutoff = max(1, (int) $relegationZone['from'] - 1);
        $teamAtPosition = static function (int $position) use ($teams): ?array {
            $index = min(count($teams) - 1, max(0, $position - 1));
            return $teams[$index] ?? null;
        };

        $safetyTeam = $teamAtPosition($safetyCutoff);
        $europeTeam = $teamAtPosition($europeCutoff);
        if ($safetyTeam === null || $europeTeam === null) {
            return null;
        }

        // Round upwards: a displayed target should never understate the model.
        $safetyTarget = (int) ceil((float) ($safetyTeam['avg_final_points'] ?? 0));
        $europeTarget = (int) ceil((float) ($europeTeam['avg_final_points'] ?? 0));

        $rows = [];
        foreach ($teams as $team) {
            $currentPoints = (int) ($team['current_points'] ?? 0);
            $rows[] = [
                'team' => (string) ($team['team'] ?? 'Unknown team'),
                'current_points' => $currentPoints,
                'projected_points' => (float) ($team['avg_final_points'] ?? 0),
                'projected_position' => (float) ($team['avg_final_position'] ?? 0),
                'to_safety' => max(0, $safetyTarget - $currentPoints),
                'to_europe' => max(0, $europeTarget - $currentPoints),
            ];
        }

        return [
            'safety' => ['position' => $safetyCutoff, 'target' => $safetyTarget, 'team' => $safetyTeam['team'] ?? ''],
            'europe' => ['position' => $europeCutoff, 'target' => $europeTarget, 'team' => $europeTeam['team'] ?? ''],
            'rows' => $rows,
            'iterations' => (int) ($simulation['n_sims'] ?? 0),
        ];
    }
}

if (!function_exists('match_projection_render')) {
    function match_projection_render(array $simulation, array $zones): void
    {
        $projection = match_projection_parse($simulation, $zones);
        if ($projection === null) {
            return;
        }
        $escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        ?>
        <section class="my-6 overflow-hidden rounded-2xl border border-slate-700/80 bg-slate-950 text-slate-100 shadow-2xl shadow-black/20" aria-labelledby="match-projection-title">
            <div class="border-b border-slate-800 bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 px-5 py-5 sm:px-7">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p class="mb-1 text-xs font-bold uppercase tracking-[0.22em] text-indigo-300">Simulation outlook</p>
                        <h3 id="match-projection-title" class="m-0 text-2xl font-black tracking-tight text-white">Points needed: safety vs Europe</h3>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">Live targets inferred from the projected teams at each qualification cut line—not fixed historical benchmarks.</p>
                    </div>
                    <span class="w-fit rounded-full border border-indigo-400/30 bg-indigo-400/10 px-3 py-1.5 text-xs font-semibold text-indigo-200"><?= number_format($projection['iterations']) ?> simulations</span>
                </div>
            </div>

            <div class="grid gap-px bg-slate-800 md:grid-cols-2">
                <article class="bg-emerald-950/35 p-5 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="m-0 text-xs font-bold uppercase tracking-[0.18em] text-emerald-300">Safety line · <?= $projection['safety']['position'] ?>th</p>
                            <p class="my-2 text-5xl font-black tabular-nums text-white"><?= $projection['safety']['target'] ?><span class="ml-2 text-lg font-bold text-emerald-300">pts</span></p>
                        </div>
                        <span class="rounded-xl bg-emerald-400/10 p-3 text-2xl" aria-hidden="true">✓</span>
                    </div>
                    <p class="m-0 text-sm text-emerald-100/70">Modelled from <?= $escape($projection['safety']['team']) ?> at the last safe place.</p>
                </article>
                <article class="bg-indigo-950/35 p-5 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="m-0 text-xs font-bold uppercase tracking-[0.18em] text-indigo-300">Europe line · <?= $projection['europe']['position'] ?>th</p>
                            <p class="my-2 text-5xl font-black tabular-nums text-white"><?= $projection['europe']['target'] ?><span class="ml-2 text-lg font-bold text-indigo-300">pts</span></p>
                        </div>
                        <span class="rounded-xl bg-indigo-400/10 p-3 text-2xl" aria-hidden="true">★</span>
                    </div>
                    <p class="m-0 text-sm text-indigo-100/70">Modelled from <?= $escape($projection['europe']['team']) ?> at the final European place.</p>
                </article>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] border-collapse text-left text-sm">
                    <thead class="bg-slate-900/80 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold sm:px-7">Team</th>
                            <th class="px-4 py-3 text-center font-semibold">Now</th>
                            <th class="px-4 py-3 text-center font-semibold">Projected</th>
                            <th class="px-4 py-3 text-center font-semibold text-emerald-300">To safety</th>
                            <th class="px-5 py-3 text-center font-semibold text-indigo-300 sm:px-7">To Europe</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                    <?php foreach ($projection['rows'] as $row): ?>
                        <tr class="bg-slate-950 transition-colors hover:bg-slate-900/80">
                            <th scope="row" class="px-5 py-3.5 font-bold text-slate-100 sm:px-7"><?= $escape($row['team']) ?><span class="ml-2 text-xs font-normal text-slate-600">#<?= number_format($row['projected_position'], 1) ?></span></th>
                            <td class="px-4 py-3.5 text-center tabular-nums text-slate-400"><?= $row['current_points'] ?></td>
                            <td class="px-4 py-3.5 text-center font-bold tabular-nums text-white"><?= number_format($row['projected_points'], 1) ?></td>
                            <td class="px-4 py-3.5 text-center"><span class="inline-flex min-w-12 justify-center rounded-full bg-emerald-400/10 px-2.5 py-1 font-bold tabular-nums text-emerald-300"><?= $row['to_safety'] === 0 ? 'Met' : '+' . $row['to_safety'] ?></span></td>
                            <td class="px-5 py-3.5 text-center sm:px-7"><span class="inline-flex min-w-12 justify-center rounded-full bg-indigo-400/10 px-2.5 py-1 font-bold tabular-nums text-indigo-300"><?= $row['to_europe'] === 0 ? 'Met' : '+' . $row['to_europe'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php
    }
}
