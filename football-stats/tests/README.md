# Football regression tests

The suite uses standalone PHP scripts and Node.js scripts; no Composer, PHPUnit,
npm install or browser driver is required. Use PHP 8 with PDO SQLite and SQLite3,
Node.js for `*-test.js`, and a writable temporary directory. Tests use fixture
arrays, temporary/in-memory SQLite databases and lightweight DOM mocks where
appropriate. Provider-shaped Nations League responses are injected without API
keys or network fetches.

## Run

From the repository root, run an individual case or the whole football suite:

```sh
php football-stats/tests/competition-rules-test.php
node football-stats/tests/team-tracker-scenarios-test.js

# Subshell exits on failure and preserves a nonzero status for CI
(
    set -eu
    for test in football-stats/tests/*-test.php; do php "$test"; done
    for test in football-stats/tests/*-test.js; do node "$test"; done
)
```

Failures throw PHP exceptions or Node assertions and exit nonzero. Run scripts
as separate processes; combining them with `require` can cause helper-name clashes.

## Coverage map

| Area | Tests / filename prefixes |
| --- | --- |
| Rule resolution, scoring and simulations | `competition-rules`, `competition-settings`, `simulation-config`, `result-points`. |
| Table controls, metrics and rendering | `table2-metrics`, `league-table-rendering`, `configured-table-views`, `table-view-movement`, `table-view-team-crests`. |
| Match/custom-rule filters | `matches-team-filters`, `matches-result-filters`, `custom-rules-team-filter`, `custom-rules-presets`. |
| Historical comparison | `cross-season-analysis`, `season-comparison`. |
| Trackers and browser calculators | `team-tracker-analysis`, `team-tracker-progress`, `team-tracker-view`, `team-tracker-scenarios`. |
| Navigation | `grouped-navigation` in PHP and JavaScript. |
| International and regional support | `nations-league`, `nations-league-sync`, `regional-tiers`, `regional-provider`. |
| Connection selection | `database-config`. |

Add fixture-based tests for new behavior without importing live provider data.
These checks do not replace manual browser checks of responsive layouts, actual
provider coverage or deployment permissions. Account/admin integration checks
live in the [root tests directory](../../tests/README.md).
