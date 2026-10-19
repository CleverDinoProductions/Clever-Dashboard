# UEFA Nations League

Open `?tab=nations-league`. The default is 2026/27, League A, Groups & Standings.
The `tournament` parameter accepts `2018-2019`, `2020-2021`, `2022-2023`,
`2024-2025`, and `2026-2027`. `league` accepts `league-a` through `league-d`.
The tournament form works without JavaScript and keeps the league and view;
unavailable views fall back to groups. League links preserve the selected edition.

Views: `groups`, `matches`, `format`, League A `finals`, and edition-appropriate
`playoffs`. Finals includes quarter-finals where applicable. Fixtures & Results
shows every stage for the selected league and edition.

No Nations League provider is configured in this repository. Missing tables or
empty editions show an explicit empty state; no standings or results are invented.
To supply data, apply `schema.sql` to the configured `CLEVER_FOOTBALL_DB`, then
import standings and matches into `nl_standings` and `nl_matches`. This is an
optional operational step, never performed during web requests. Store dates in
ISO 8601 UTC, use globally unique match IDs, and use `groups`, `finals`, or
`playoffs` stage keys (with a descriptive `round_name`). Scores are nullable for
unplayed fixtures. Import UEFA's official positions and tie-break ordering.
