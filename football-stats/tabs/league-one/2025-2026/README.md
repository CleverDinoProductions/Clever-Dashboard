# League One 2025/26 views

Open `/football-stats/?tab=2025-2026&league=league-one&subtab=table`.
The router supplies the database and page context; do not load individual PHP
tab files directly. This competition uses code `L1` and live table
`league_table_L1` alongside competition/season-scoped fixtures and snapshots.

## Routes and files

| Route key | File / responsibility |
| --- | --- |
| `table` / `table-2` | `table.php` / `table2.php`: regular and deep-dive standings. |
| `matches` | `matches.php`: fixtures, results and filters. |
| `compare` / `compare-seasons` | `compare.php` / `compare-seasons.php`: matchweek and historical season comparisons. |
| `cross-season` | Shared `tabs/shared/cross-season.php`: Rules Across Seasons lab. |
| `playoffs` | `playoffs.php`: imported post-regular-season fixtures and bracket display. |
| `blocks-overview` / `blocks-dynamic` | Overview and live position-band analysis. |
| `blocks-1` … `blocks-5` | Five detailed four-team block views. |
| `team-tracker` / `team-tracker-2` / `team-tracker-3` | Shared tracker experience with league context. |
| `whatifs` / `simulation` | Upcoming-result scenarios and season simulations. |

These playoff views select imported fixtures beyond matchweek 46; a missing
bracket can mean no playoff data has been imported for the selected season.
Tables and simulations also consume configured competition rules. The first
five four-team block details cover positions 1–20; use overview/live blocks to
inspect the rest of a larger table.

Import data using the [football data workflow](../../../README.md#updating-match-data),
confirm the dashboard and importer target the same database, and set season
exceptions through Admin. Register any new public subtab in the explicit allow
list in `football-stats/index.php`.

See [shared components](../../../includes/README.md),
[block analysis](../../../BLOCKS_README.md) and [regression checks](../../../tests/README.md).
