# Shared PHP Includes

These files provide layout, table rendering, team details, and simulation components shared by the competition tabs.

## Files

- `header.php` and `footer.php` — common page shell and navigation.
- `table-view.php` — league table query, controls, and rendering.
- `table-view-date-helper.php` — resolves the first match date for a matchweek.
- `team-info.php` — team metadata used by dashboard views.
- `team-tracker-helpers.php` — shared team-tracking calculations and output helpers.
- `match-projection-widget.php` — reusable match projection UI.
- `season-comparison.php` — cross-season comparison component.
- `simulation-engine.php` and `simulation-view.php` — shared season simulation logic and presentation.

Tab files should configure and call these components instead of copying shared logic. Include them through paths based on `__DIR__` where possible so commands and web requests behave consistently from different working directories.
