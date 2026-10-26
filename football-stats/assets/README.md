# Football Dashboard Assets

This directory contains the presentation assets for the Football Stats Dashboard.

## Contents

- `style.css` — shared layout, navigation, table, card, block-analysis, and responsive styles.
- `grouped-navigation.css` and `grouped-navigation.js` — grouped dashboard navigation styling and interactions.
- `team-tracker.css` and `team-tracker.js` — team tracker presentation and interactive scenario calculations.

Keep reusable dashboard styling here rather than embedding it in individual tabs. When adding an asset, use paths relative to `/football-stats/` and verify both desktop and mobile layouts.

The grouped navigation and team tracker scripts have standalone Node.js checks in
`../tests/`. Run them using the commands in the [main README](../README.md#tests).
