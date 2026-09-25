# Championship 2025/26 Tabs

These PHP views power the Championship section selected with `?tab=2025-2026&league=championship`.


## Views

- `table.php` and `table2.php` — regular and deep-dive league tables.
- `matches.php` — match list and results.
- `compare.php` and `compare-seasons.php` — matchweek and season comparisons.
- `blocks-overview.php`, `blocks-dynamic.php`, and `blocks-1.php` through `blocks-5.php` — block analysis.
- `team-tracker.php`, `team-tracker-2.php`, and `team-tracker-3.php` — alternate team-focused views.
- `whatifs.php` and `simulation.php` — projections and simulations.
- `playoffs.php` — the routed playoff view.
- `promotion.php`, `playoff.php`, and `relegation.php` — additional specialist views retained in this directory but not currently registered in the main router.

---

When adding a routed view, register an explicit key and file path in `football-stats/index.php`.
