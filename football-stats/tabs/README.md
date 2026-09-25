# Dashboard Tabs

Tab implementations are organized by competition and season. `football-stats/index.php` is the allow-listed router and maps query parameters to these files; tab paths should not be accepted directly from user input.

## Current layout

- `division-one/2025-2026/` — Division One views.
- `premier-league/2025-2026/` — Premier League views.
- `championship/2025-2026/` — Championship views, including promotion/playoff analysis.
- `league-one/2025-2026/` — League One views.
- `league-two/2025-2026/` — League Two views.
- `national-league/2025-2026/` — National League views.
- `world-cup/` — World Cup group, knockout, standings, prediction, and simulation views.

League directories generally provide regular and deep-dive tables, matches, comparisons, blocks, team trackers, what-if analysis, and simulations. A file is reachable in the UI only after it has also been registered in the router's season/league configuration.
