# Shared league views

These router-loaded PHP files reuse league tools across competitions. They require
`$db` and the current season/league/subtab context from `football-stats/index.php`;
do not request them directly as standalone URLs.

## Regional dispatcher

`regional.php` resolves the selected slug using `lib/english-pyramid.php`, checks
for imported live/archive data and renders an empty state when none is available.
Tables and most tools load from `../regional/2025-2026/`; team trackers reuse the
National League files. All regional route registrations for both selectable
seasons point here. Paths are selected from configured mappings, not arbitrary
user-supplied file names. See the [regional guide](../regional/README.md).

## Rules Across Seasons

`cross-season.php` renders `subtab=cross-season` for original and regional leagues.
It calls `includes/cross-season-analysis.php` with selected fixture history.
The form supports team/seasons, opponent group, venue, results, matchweek bounds,
custom scoring, exclusions, simulated outcomes and per-season deductions.

Opponent halves reconstruct standings before kickoff using actual results and
standard 3/1/0 scoring. Known kickoff times use an estimated two-hour completion
window; a day lacking precise times uses its opening baseline. Same-time games
share a baseline, and ambiguous opening/unknown-date/halfway ties remain
unclassified. Historical deductions are not reconstructed without effective dates.
Custom scoring and simulated results change comparison totals, not classification.

Use the [cross-season regression check](../../tests/cross-season-analysis-test.php)
when changing classification, ordering or inclusion rules. Related rendering and
filter checks are listed in [tests](../../tests/README.md).

## Own Table

`own-table.php` serves every registered English league and season through
`subtab=own-table`, under Tables & Results. Select two or more clubs and optional
inclusive dates, then build a mini league from their scored regular-season
head-to-head fixtures. Selected clubs without results remain visible. The view
uses the competition's configured win/draw/loss points, without whole-season
points deductions. Standings sort by points, goal difference, goals scored and
club name; column links change the displayed order while preserving ranks.
Counted fixtures can be inspected below the table. Filters are held in GET
parameters, so a result can be bookmarked or shared.

Calculation and rendering regression checks: `php football-stats/tests/own-table-test.php`.

## Calendar Year

`calendar-year.php` is registered as `subtab=calendar-year` for every English
league under Tables & Results. `calendar_year` selects an imported year (default:
current London calendar year). Results use actual match dates from 1 January
through 31 December across all imported seasons, including promoted/relegated
clubs. Only scored regular-season fixtures count, using the originating season's
matchweek cutoff and scoring rules. Season-wide deductions are not applied.
The view includes an auditable counted-match list and warns about import coverage.

Regression checks: `php football-stats/tests/calendar-year-test.php`.
