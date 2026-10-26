# Football Stats Dashboard

This PHP and SQLite application presents English football, World Cup, and Nations League data through tables, match lists, matchweek comparisons, block analysis, team trackers, what-if tools, and simulations. Python and PHP maintenance scripts fetch or rebuild the underlying data.

## Supported views

- 2025/26 Division One, Premier League, Championship, League One, League Two, and National League views
- Tier 6–8 regional leagues in both 2025/26 and 2026/27
- Regular and deep-dive tables, matches, snapshot and season comparisons
- Rules Across Seasons lab and league-specific custom-rule team groups
- Four-team block analysis and live block movement
- Team trackers, what-if projections, and season simulations
- Custom-rule tables with per-team points deductions and deterministic outcomes for unplayed fixtures
- Playoff views for supported lower divisions
- World Cup groups, knockout bracket, standings, predictions, and simulation
- Nations League edition selection, calculated group standings, fixtures, format, finals, and playoffs

See [`tabs/README.md`](tabs/README.md) for the view layout and [`includes/README.md`](includes/README.md) for shared rendering components.

## Requirements

- PHP 8 with PDO SQLite and SQLite3
- Python 3.10+ for `sync-matches.py`
- SQLite 3
- `requests` for legacy Python API clients; `sync-matches.py` uses the standard library

## Setup

From the repository root:

```sh
# Create the core schema in the football application directory
cd football-stats
php setup-db.php
cd ..

# Serve the whole repository
php -S localhost:8000
```

Then open <http://localhost:8000/football-stats/>. The setup script creates only
core tables; it does not import fixtures or create all provider-specific live
tables. Import the competitions you need before expecting populated views.

`config.php` resolves the dashboard connections as follows:

| Connection | Environment override | Default lookup order |
| --- | --- | --- |
| English football and Nations League | `CLEVER_FOOTBALL_DB` | `football-stats.sqlite3`, then bundled `football-stats.db` |
| World Cup | `CLEVER_WORLD_CUP_DB` | `world-cup-stats.sqlite3`, then the selected football database |

Set overrides to absolute paths for deployments using external databases.
`setup-db.php` and `fetch-englishfootball.php` use a working-directory-relative
`football-stats.sqlite3`; `sync-matches.py` writes that filename beside its script.
These tools do not follow `CLEVER_FOOTBALL_DB`. Ensure imports and the dashboard
use the same file. Back up runtime databases before making changes.

## Updating match data

The repository contains several provider-specific and historical maintenance tools. Inspect a script before using it: some legacy fetchers contain provider configuration and may target a narrower set of competitions.

For merged English-league fixtures, `sync-matches.py` is the preferred command. It accepts `PL`, `ELC`, `L1`, `L2`, or `NL` plus a season:

```sh
export FOOTBALL_DATA_TOKEN='...'
export API_FOOTBALL_KEY='...'
python3 football-stats/sync-matches.py PL 2025-2026
```

At least one provider variable is required. National League (`NL`) needs
`API_FOOTBALL_KEY`, as football-data.org is not mapped for that competition. Rows are merged by competition, season, clubs, and date rather than replacing the other provider's data. The command then reconstructs matchweek 0 and completed-matchweek snapshots.

Other utilities include `backfill-mw0.php`, `populate-sample-data.php`, `update-el-data.php`, `update-wc-data.php`, and the `fetch-*` scripts. Back up a populated database before running a mutating utility.

## Project structure

```text
football-stats/
├── index.php              # Season/competition/tab router
├── config.php             # SQLite connections
├── config/                # Competition rules
├── assets/                # Dashboard styles and browser scripts
├── includes/              # Shared renderers and simulation helpers
├── tabs/                  # League and international competition views
├── tests/                 # Standalone PHP and Node.js regression tests
├── setup-db.php           # Core schema setup
└── sync-matches.py        # Multi-provider match merger
```

## Tests

Run the standalone regression scripts from the repository root:

```sh
for test in tests/*-test.php football-stats/tests/*-test.php; do
    php "$test" || break
done
for test in football-stats/tests/*-test.js; do
    node "$test" || break
done
```

## Security

Do not commit API credentials. Move provider tokens out of legacy scripts and into environment variables before deployment, restrict setup/debug scripts from public access, and back up SQLite files before schema or import operations.

## Admin competition rules

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

## English pyramid tiers 6–8

Both selectable seasons include National League North/South (tier 6), the four regional premier divisions (tier 7), and eight regional feeder divisions (tier 8). Each division has every National League tab: regular/deep tables, fixtures, playoffs, matchweek and season comparisons, the cross-season rules lab, blocks overview/live blocks/all five block details, all three team trackers, what-if scenarios, and simulations. Section headings show tier numbers in brackets. Admin competition settings and account league preferences include these divisions.

`fetch-englishfootball.php` creates their live tables and discovers TheSportsDB IDs using exact league names, alternate names, and explicit provider aliases for the Southern Premier divisions. After updating those mappings, rerun `php fetch-englishfootball.php` from the `football-stats` directory to import previously skipped divisions. Unavailable divisions are reported and never populated with another division's results. No standings are fabricated. Regional defaults use 24 clubs/46 games at tier 6 and 22 clubs/42 games at tiers 7–8, with four relegation places; adjust season-specific rules in Admin for exceptions, reprieves, or changed allocations.

### League-specific Big club groups

Big 3, 6, 9 and 12 use the first N clubs in each competition's Transfermarkt
**all-time points table**. These are stored snapshots, not squad market values or
live standings. Source URLs and snapshot dates are in
`lib/transfermarkt-rankings.php`; Transfermarkt's historical coverage varies by
competition. Verified snapshots currently cover PL, ELC, L1, L2, NL, NLN and NLS.
Other divisions start empty rather than inheriting Premier League clubs.

In Admin → Dashboard → Custom Rules team groups, select a league to view or edit
its memberships. Each group (including Typical top half) has independent league
settings. Legacy global memberships remain Premier League overrides only.
Table custom rules, match filters and the cross-season rules lab all resolve
memberships for the selected league. Refresh snapshots by updating the ordered
club list and checked date after verifying the linked source.

## Nations League

The main navigation includes UEFA Nations League alongside World Cup, with
Leagues A–D, edition-specific views, and historic tournament selection.
The global fetcher now imports Nations League fixtures with the same TheSportsDB
key as the English fetcher, and calculates group standings from finished results.
See [Nations League sync and setup](tabs/nations-league/README.md).
