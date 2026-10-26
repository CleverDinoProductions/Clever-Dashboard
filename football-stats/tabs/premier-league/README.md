# Premier League tab directory

The registered implementation is documented in
[2025/26 views](2025-2026/README.md). Public routes use
`/football-stats/?tab=2025-2026&league=premier-league&subtab=table`.

Season directories organize source files; creating a directory does not register
a season or view. Add explicit mappings in `football-stats/index.php` and reuse
shared components where possible. Historical data selection inside a view is
separate from the router's season registration. See the
[tab routing guide](../README.md#routing) and
[football setup](../../README.md#setup) before adding a season.
