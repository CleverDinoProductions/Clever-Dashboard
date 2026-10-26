# Shared regional tab implementations

These files serve tier 6–8 divisions for both 2025/26 and 2026/27. They depend on
`shared/regional.php` supplying `$regionalCode`, `$regionalTable`, `$regionalName`,
`$regionalRules` and the router's current context. They are not standalone pages.

| Route key | Implementation |
| --- | --- |
| `table` / `table-2` | `table.php` / `table2.php`: shared regular/deep-dive table rendering. |
| `matches` | `matches.php`: fixtures and result/team filters. |
| `compare` / `compare-seasons` | Matchweek and season comparisons. |
| `playoffs` | `playoffs.php`: imported rounds beyond the configured regular-season cutoff. |
| `blocks-overview`, `blocks-dynamic`, `blocks-1` … `blocks-5` | `blocks.php`: one implementation selected by route key. |
| `whatifs` / `simulation` | Scenario and season simulation views. |

`team-tracker`, `team-tracker-2` and `team-tracker-3` come from the National League
directory; `cross-season` comes from `shared/cross-season.php`.

Regional block ranges follow promotion, playoff and relegation rules, with
mid-table and relegation-battle bands between them. They have variable sizes,
unlike the fixed four-team bands in the original league directories. The dynamic
view adds remaining games and points projections. Playoff rounds use the chosen
season's regular matchweek cutoff and show an empty state if none were imported.

Keep calculations based on selected competition/season rules, and register new
public keys in `football-stats/index.php`. For import setup and route examples,
see the [regional overview](../README.md).
