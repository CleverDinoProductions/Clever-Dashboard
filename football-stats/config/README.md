# Competition rule defaults

`competition-rules.php` returns bundled rule arrays keyed by competition code.
It contains global defaults, per-league defaults and season exceptions. It does
not open a database or contain provider credentials.

## Effective settings

Consumers use `lib/competition-settings.php` at the repository root to resolve:

1. Bundled global/per-league defaults, with generated regional defaults.
2. Saved league defaults (`season_label = ''`).
3. Bundled exceptions for the requested season.
4. Saved overrides for that season.

Database overrides are stored as complete rule sets in
`competition_rule_overrides` in the configured football database. Use
[Admin → Competition rules](../../admin/README.md) for installation-specific edits;
changing this source file changes the fallback for installations without overrides.
A saved season does not track later changes to league defaults. Reset deletes
that saved override and restores normal fallback resolution.

## Fields and zones

Rules define club count, regular-season cutoff, games per club, win/draw/loss
points, halfway games/safety target, Table 2 targets, quarter boundaries and
position zones. Regional defaults are generated from
[`lib/english-pyramid.php`](../../lib/english-pyramid.php): tier 6 uses 24 clubs/46
games, tiers 7–8 use 22/42. Actual allocations can require season overrides.

Zones have a type, label, inclusive `from`/`to` positions and colour. They may
overlap: Title at 1 and Champions League at 1–4 can both count in simulations.
Row colour and row edge preferences are independent. For each row, the first
matching zone selected for that display property wins; otherwise the first
matching zone is used. An explicitly empty zone list disables position highlights.
The table's left edge reflects league defaults, while fill/right edge reflect
the selected season. Simulation targets use all configured zones.

Validation checks numeric bounds, season labels, quarter ordering, zone ranges
and targets. Rules affect presentation/calculation; they do not rewrite imported
fixture or standing rows. See [football tests](../tests/README.md) for rule,
rendering and simulation regression coverage.
