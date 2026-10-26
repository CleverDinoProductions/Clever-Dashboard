# World Cup Tabs

These PHP files provide the World Cup section of the Football Stats Dashboard. Open it with `?tab=world-cup&subtab=groups` and replace `groups` with another registered subtab.

## Views

- `groups.php` — group-stage standings and results.
- `knockout.php` — knockout bracket and results.
- `predictions.php` — predictions and analytics.
- `standings.php` — overall standings.
- `simulation.php` — tournament simulation.

---

The main router allow-lists each view. World Cup data uses `CLEVER_WORLD_CUP_DB` when set, otherwise an existing
`world-cup-stats.sqlite3`, falling back to the selected football database.
See [`config.php`](../../config.php) and the [database setup guide](../../README.md#setup).
