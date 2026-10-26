# Dashboard Tabs

Tab implementations are organized by competition and season. `football-stats/index.php` is the allow-listed router and maps query parameters to these files; tab paths should not be accepted directly from user input.

## Current layout

- `division-one/2025-2026/` — Division One views.
- `premier-league/2025-2026/` — Premier League views.
- `championship/2025-2026/` — Championship views, including promotion/playoff analysis.
- `league-one/2025-2026/` — League One views.
- `league-two/2025-2026/` — League Two views.
- `national-league/2025-2026/` — National League views.
- `regional/2025-2026/` — shared tier 6–8 implementations, used for both selectable seasons.
- `shared/regional.php` — dispatches regional views and handles missing imported data.
- `shared/cross-season.php` — Rules Across Seasons lab shared by league tabs.
- `nations-league/` — edition-aware groups, matches, format, finals, and playoffs.
- `world-cup/` — World Cup group, knockout, standings, prediction, and simulation views.

League directories generally provide regular and deep-dive tables, matches, comparisons, blocks, team trackers, what-if analysis, and simulations. A file is reachable in the UI only after it has also been registered in the router's season/league configuration.

## Routing

Use `tab` for the season or international competition, `league` for its division,
and `subtab` for the view. File names and route keys can differ: the deep-dive
table is `table2.php` but its public key is `table-2`.

```text
/football-stats/?tab=2025-2026&league=premier-league&subtab=table-2
/football-stats/?tab=2025-2026&league=championship&subtab=cross-season
/football-stats/?tab=nations-league&tournament=2024-2025&league=league-a&subtab=groups
```

The six original league directories are registered for 2025/26. Regional divisions
are registered for both 2025/26 and 2026/27 and reuse implementations under
`regional/2025-2026/`; a new season does not require copying those files. Regional
trackers reuse the National League tracker views. Competition slugs and provider
codes are defined in `lib/english-pyramid.php` at the repository root.

See [Nations League setup](nations-league/README.md) for edition and data-sync
options and [block analysis](../BLOCKS_README.md) for regional block behavior.

## Detailed guides

- [Regional division setup and shared implementations](regional/README.md).
- [Shared dispatcher and cross-season rules lab](shared/README.md).
- [League One](league-one/README.md), [League Two](league-two/README.md) and
  [National League](national-league/README.md) view references.
- [World Cup](world-cup/README.md) and [Nations League](nations-league/README.md).
