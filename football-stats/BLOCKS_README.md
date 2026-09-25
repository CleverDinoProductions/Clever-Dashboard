# Block Analysis

The block views divide a league table into consecutive four-team position bands. They make it easier to discuss movement between competitive zones without encoding a separate page for every promotion, qualification, mid-table, or relegation scenario.

## Available views

Each supported English competition has the following 2025/26 views:

- `blocks-overview.php` — introduction and summary of the position bands.
- `blocks-dynamic.php` — live block analysis based on the current table.
- `blocks-1.php` through `blocks-5.php` — detailed views for the first five four-team bands.

The Championship, League One, League Two, and National League contain more than 20 teams, so the overview/dynamic views should be used when analysis needs to cover positions beyond the first five blocks.

## URLs

Block tabs use the standard football router parameters. For example:

```text
/football-stats/?tab=2025-2026&league=premier-league&subtab=blocks-overview
/football-stats/?tab=2025-2026&league=premier-league&subtab=blocks-dynamic
/football-stats/?tab=2025-2026&league=premier-league&subtab=blocks-1
```

Change `league` to `division-one`, `championship`, `league-one`, `league-two`, or `national-league` for another configured competition.

## Implementation

The tab files obtain standings from the shared dashboard database and reuse common table/team helpers from `includes/`. Navigation and shared visual rules live in `includes/header.php` and `assets/style.css`. The main `index.php` explicitly allow-lists the block files for each league.

When changing the feature:

1. Keep position ranges and labels consistent across the overview, dynamic, and detailed views.
2. Use the configured competition rules rather than assuming every league has 20 teams.
3. Put shared calculations in `includes/` rather than duplicating them in each league directory.
4. Register any new public subtab in the relevant league configuration in `index.php`.
5. Verify current and historical matchweek data, including matchweek 0, before relying on movement calculations.
