# Regional English leagues (tiers 6–8)

The router exposes National League North/South, the four tier-7 premier divisions,
and eight tier-8 feeders for both 2025/26 and 2026/27. Canonical slugs, provider
codes and labels live in [`lib/english-pyramid.php`](../../../lib/english-pyramid.php).

Example routes:

```text
/football-stats/?tab=2025-2026&league=national-league-north&subtab=table
/football-stats/?tab=2026-2027&league=southern-league-central&subtab=playoffs
```

All routes first load [`shared/regional.php`](../shared/regional.php), which supplies
competition context and handles missing imported data. The
[`2025-2026/`](2025-2026/README.md) directory contains implementations shared across
both selectable seasons; its directory name is not a runtime season restriction.

## Data and rules

Run the English fetcher from `football-stats/` after reviewing its configuration:

```sh
cd football-stats
php fetch-englishfootball.php
```

This writes `football-stats.sqlite3` in that working directory and contacts
TheSportsDB. It discovers regional IDs using exact league names, alternate names
and explicit provider aliases. Unavailable divisions are reported and left empty;
coverage depends on the provider. Do not substitute a nearby division's results.
See [football setup](../../README.md#setup) before running imports against an
installation using `CLEVER_FOOTBALL_DB`.

Regional defaults use 24 clubs/46 games at tier 6 and 22/42 at tiers 7–8, with four
relegation places. Set season-specific exceptions in Admin for changed allocations
or reprieves. Empty data is an import/coverage condition, not simulated standings.
The [regional tests](../../tests/README.md) cover routing and provider mapping.
