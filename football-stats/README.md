# Football Stats Dashboard

This PHP and SQLite application presents English football and World Cup data through tables, match lists, matchweek comparisons, block analysis, team trackers, what-if tools, and simulations. Python and PHP maintenance scripts fetch or rebuild the underlying data.

## Supported views

- 2025/26 Division One, Premier League, Championship, League One, League Two, and National League views
- Regular and deep-dive tables, matches, snapshot and season comparisons
- Four-team block analysis and live block movement
- Team trackers, what-if projections, and season simulations
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
