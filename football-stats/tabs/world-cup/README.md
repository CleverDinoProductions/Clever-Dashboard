# World Cup Tabs

These PHP files provide the World Cup section of the Football Stats Dashboard. Open it with `?tab=world-cup&subtab=groups` and replace `groups` with another registered subtab.

## Views

- `groups.php` — group-stage standings and results.
- `knockout.php` — knockout bracket and results.
- `predictions.php` — predictions and analytics.
- `standings.php` — overall standings.
- `simulation.php` — tournament simulation.

---

The main router allow-lists each view. World Cup data uses the separate `world-cup-stats.sqlite3` connection configured in `football-stats/config.php`.
