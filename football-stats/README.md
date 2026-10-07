# Football Stats Dashboard

This PHP and SQLite application presents English football and World Cup data through tables, match lists, matchweek comparisons, block analysis, team trackers, what-if tools, and simulations. Python and PHP maintenance scripts fetch or rebuild the underlying data.

## Supported views

- 2025/26 Division One, Premier League, Championship, League One, League Two, and National League views
- Regular and deep-dive tables, matches, snapshot and season comparisons
- Four-team block analysis and live block movement
- Team trackers, what-if projections, and season simulations
- Custom-rule tables with per-team points deductions and deterministic outcomes for unplayed fixtures
- Playoff views for supported lower divisions
- World Cup groups, knockout bracket, standings, predictions, and simulation

See [`tabs/README.md`](tabs/README.md) for the view layout and [`includes/README.md`](includes/README.md) for shared rendering components.

## Requirements

- PHP 8 with PDO SQLite
- Python 3
- SQLite 3
- `requests` for the Python API clients

## Setup

From the repository root:

```sh
# Create the core schema when starting with an empty database
php football-stats/setup-db.php

# Serve the whole repository
php -S localhost:8000
```

Then open <http://localhost:8000/football-stats/>. `config.php` expects `football-stats.sqlite3` and `world-cup-stats.sqlite3` in this directory. Database files are runtime data and should not be treated as source.

## Updating match data

The repository contains several provider-specific and historical maintenance tools. Inspect a script before using it: some legacy fetchers contain provider configuration and may target a narrower set of competitions.

For merged English-league fixtures, `sync-matches.py` is the preferred command. It accepts `PL`, `ELC`, `L1`, `L2`, or `NL` plus a season:

```sh
export FOOTBALL_DATA_TOKEN='...'
export API_FOOTBALL_KEY='...'
python3 football-stats/sync-matches.py PL 2025-2026
```

At least one provider variable is required. Rows are merged by competition, season, clubs, and date rather than replacing the other provider's data. The command then reconstructs matchweek 0 and completed-matchweek snapshots.

Other utilities include `backfill-mw0.php`, `populate-sample-data.php`, `update-el-data.php`, `update-wc-data.php`, and the `fetch-*` scripts. Back up a populated database before running a mutating utility.

## Project structure

```text
football-stats/
├── index.php              # Season/competition/tab router
├── config.php             # SQLite connections
├── config/                # Competition rules
├── assets/                # Dashboard styles
├── includes/              # Shared renderers and simulation helpers
├── tabs/                  # Competition and World Cup views
├── tests/                 # Standalone PHP regression tests
├── setup-db.php           # Core schema setup
└── sync-matches.py        # Multi-provider match merger
```

## Tests

Run the standalone regression scripts from the repository root:

```sh
php football-stats/tests/competition-rules-test.php
php football-stats/tests/table-view-movement-test.php
php football-stats/tests/table-view-team-crests-test.php
```

## Security

Do not commit API credentials. Move provider tokens out of legacy scripts and into environment variables before deployment, restrict setup/debug scripts from public access, and back up SQLite files before schema or import operations.

### Admin competition rules

Open **Admin → Competition rules**, choose a league, enter a season such as
`2026-2027`, and click **Load rules**. Leave the season blank to edit league
defaults. Administrators and Football editors can save these settings; other
members cannot. Saving and resetting use the existing CSRF protection.

The editor supports Champions League, Europa League, Conference League,
automatic promotion, promotion playoffs, relegation, and custom zones. Each zone
has a label, inclusive position range, and colour. Add or remove rows as needed;
removing every row saves an explicitly empty zone list. Position ranges cannot
overlap or exceed the configured club count. The table's left border uses league
defaults; its fill and right border use the selected season's rules. Legends use
the same labels, ranges, and colours.

Other editable settings include club count, regular-season matchweek cutoff,
games per club, first-half boundary, halfway safety points, Table 2 comparison
points targets, and quarter boundaries. The Championship's quarter filters use
the configured boundaries. Matchweek controls, calculated tables, and season
comparisons use the selected season's format. These are presentation/calculation
rules; they do not rewrite imported standings or fixture data.

Overrides are stored in `competition_rule_overrides` in the configured football
SQLite database (`CLEVER_FOOTBALL_DB`). Existing installations create the table
through the settings migration. Reads from older or read-only databases without
the table keep using bundled rules. Resolution order is bundled league defaults,
saved league defaults, bundled season exceptions, then saved season overrides.
A saved override is a complete rule set: later changes to defaults do not change
that saved season. **Reset override** deletes just that saved league/season entry
and restores bundled season exceptions or inherited league defaults. The bundled
Premier League 2025-2026 qualification exception remains available after reset.
