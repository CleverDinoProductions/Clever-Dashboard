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
- `simulation-engine.php` and `simulation-view.php` — shared season simulation logic and presentation. `simulation-config.php` loads Admin competition rules for the selected season; probabilities, distribution colours and outlook targets use those typed position zones. The outlook shows projected points for every league, with qualification, automatic promotion, playoff and safety targets where configured.

Tab files should configure and call these components instead of copying shared logic. Include them through paths based on `__DIR__` where possible so commands and web requests behave consistently from different working directories.

Named simulated match result sets are stored per account by `lib/saved-results.php`.
After applying custom match outcomes, use **Saved simulated results** to save a new
set or replace the current tab's custom rules with a saved set. Saves are restricted
to their league and season, include exclusions, scoring and deductions, and remain
separate even when names match. The account page lists all sets with links that
open them in a new browser tab. This saves custom fixture outcomes, not Monte Carlo
probability output. Run `php tests/saved-results-test.php` from the repository root.
