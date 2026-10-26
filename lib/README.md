# Shared application libraries

These PHP modules provide account and football configuration services. They are
included by page controllers and tests, not called as HTTP endpoints. Use paths
based on `__DIR__` when requiring them.

| Module | Responsibility |
| --- | --- |
| `auth.php` | Account migrations, sessions, persistent tokens, login/permission guards, CSRF, preferences and movement settings. |
| `football-settings.php` | Football settings connection/migrations, team metadata, deductions, dashboard copy and per-league team groups. |
| `competition-settings.php` | Rule validation, database overrides, effective rule resolution and position-zone display selection. |
| `english-pyramid.php` | Tier 6–8 slugs, codes, labels and exact provider aliases. |
| `transfermarkt-rankings.php` | Ordered, dated all-time-points snapshots used by Big 3/6/9/12 groups. |
| `saved-results.php` | Account-owned saved custom fixture outcome sets and their allowed state keys. |

## Persistence boundaries

Accounts, group membership, preferences, login tokens and saved simulated results
belong to `CLEVER_ACCOUNTS_DB` (default `data/accounts.sqlite3`). Football team
metadata, deductions, dashboard settings and competition overrides belong to
`CLEVER_FOOTBALL_DB`. See the [football connection guide](../football-stats/README.md#setup)
for canonical and legacy path selection.

`clever_accounts_db()` and `clever_football_db()` open connections and run their
respective migrations. Calling a connection helper can therefore create or alter
tables. Effective competition-rule reads tolerate older databases without an
override table and keep bundled defaults. Use a temporary database when testing
write paths; these libraries do not share the IRC read-only connection.

Rule resolution is bundled defaults, saved league defaults, bundled season
exceptions, then saved season overrides. Overrides contain complete settings.
Saving one season does not rewrite imported fixtures or standings.

Big-club groups resolve per competition. Verified Transfermarkt snapshots cover
PL, ELC, L1, L2, NL, NLN and NLS; other divisions start empty. When updating a
snapshot, verify its linked all-time-points source and update the checked date.
Legacy global memberships apply only to Premier League overrides.

Saved result sets require applied custom outcomes, are scoped to their owner,
league and season, and contain only the allow-listed calculation state. They do
not store Monte Carlo probability distributions. Use the shared permission guards
and CSRF helpers in controllers rather than duplicating their checks.

Related guides: [accounts](../account/README.md), [admin](../admin/README.md),
[shared regression tests](../tests/README.md).
