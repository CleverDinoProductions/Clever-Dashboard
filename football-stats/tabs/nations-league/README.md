# UEFA Nations League

Open `?tab=nations-league`. The default is 2026/27, League A, Groups & Standings.
The `tournament` parameter accepts `2018-2019`, `2020-2021`, `2022-2023`,
`2024-2025`, and `2026-2027`. `league` accepts `league-a` through `league-d`.
The tournament form works without JavaScript and keeps the league and view;
unavailable views fall back to groups. League links preserve the selected edition.

Views: `groups`, `matches`, `format`, League A `finals`, and edition-appropriate
`playoffs`. Finals includes quarter-finals where applicable. Fixtures & Results
shows every stage for the selected league and edition. Promotion/relegation
play-offs appear for both participating leagues, including League D.

## Fetching data

`fetch-worldfootball.php` syncs Nations League fixtures from TheSportsDB's
`eventsseason.php?id=4490&s=EDITION` endpoint before the existing World Cup sync.
It reuses the English fetcher's existing key through `includes/sportsdb-config.php`,
which reads the legacy key assignment without executing the English importer.
The credential remains in its original file and is never duplicated. Set `THESPORTSDB_API_KEY` to override the existing key for both fetchers.
Do not include or execute `fetch-englishfootball.php` to load its configuration.

Run from any working directory:

```sh
# All supported Nations League editions, followed by the existing World Cup sync
php /path/to/football-stats/fetch-worldfootball.php

# Only Nations League, leaving World Cup data alone
php /path/to/football-stats/fetch-worldfootball.php --nations-league-only

# A selected historic or current edition
php /path/to/football-stats/fetch-worldfootball.php --nations-league-only --tournament=2024-2025
```

The dashboard's **Update Nations League** control starts the selected edition's
sync for users with data-update permission. The POST endpoint requires CSRF
verification. Reload after the background sync finishes. Starting the background
process does not guarantee a successful provider response; the last-sync timestamp
only changes after data is successfully imported. The fetcher is CLI-only.

The sync and tabs use the same `CLEVER_FOOTBALL_DB` connection from `config.php`.
World Cup uses `CLEVER_WORLD_CUP_DB`. The sync creates `schema.sql` tables and
upgrades older `nl_matches` tables automatically. Dashboard reads never create
or modify schema. Run the fetcher with a writable database and PHP 8 / PDO SQLite.

TheSportsDB does not currently supply a Nations League standings table.
Standings are calculated from cached **finished group matches**, with three points
for a win and one for a draw. Head-to-head criteria are applied recursively to
tied teams before overall goal criteria. Disciplinary deductions, disciplinary
points and access-ranking tie-breaks are not supplied; unresolved ties use
alphabetical order and the view clearly labels calculated standings. Cached
provider coverage may be incomplete. Live, postponed, cancelled and knockout
fixtures never contribute to the group standings. Zero scores, unplayed null
scores, and penalty shootouts remain distinct.

Imports upsert provider event IDs scoped to their edition, preserving cached
fixtures when a response is partial, empty, malformed, or unavailable. Successful
imports and standings rebuilds are transactional. Unknown teams, other competitions
and mismatched seasons are skipped and counted rather than assigned to a league.
The last-sync summary shows skipped event counts. Missing data has an empty state.

## Group allocation sources

`groups.json` supplies edition-specific UEFA group memberships because provider
fixtures do not reliably carry division/group identifiers. Country aliases are
normalised to common TheSportsDB names. It contains no fixture results. The
2022/23 groups exclude suspended Russia. The 2024/25 and 2026/27 allocations
resolve the draw's relegation play-out placeholders using the completed play-outs.

- [2018/19 UEFA draw](https://www.uefa.com/uefanationsleague/news/0253-0d81efc3a33f-dd2083a5720d-1000--uefa-nations-league-2018-19-league-phase-draw/)
- [2020/21 UEFA draw](https://www.uefa.com/uefanationsleague/news/025b-0ed780291c53-e59793c52ce2-1000--nations-league-draw-in-full/)
- [2022/23 UEFA draw](https://www.uefa.com/uefanationsleague/news/0270-13f91f78ee94-3556f02bf7f9-1000--nations-league-draw-in-full/)
- [2024/25 UEFA draw](https://www.uefa.com/uefanationsleague/news/028a-1a1579c12dec-5cadac35d0a0-1000--nations-league-draw-live-from-18-00-cet/)
- [2026/27 UEFA groups after the play-outs](https://www.uefa.com/uefanationsleague/news/02a2-1fe8e0405d9d-cc8f1ee7068b-1000--draw-for-the-league-phase-of-the-2026-27-uefa-nations-league/)
- [TheSportsDB API documentation](https://www.thesportsdb.com/documentation)

## Checks

```sh
php football-stats/tests/nations-league-test.php
php football-stats/tests/nations-league-sync-test.php
```

The sync tests inject provider-shaped fixture responses and use in-memory SQLite, requiring
no provider credentials or network requests.
