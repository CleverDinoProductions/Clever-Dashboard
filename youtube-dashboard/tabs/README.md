# YouTube views

Open views through `/youtube-dashboard/?tab=KEY`. `index.php` allow-lists the eight
keys below and falls back to `timeline` for unknown keys. Tabs expect the shared
configuration, database helpers, header and footer loaded by that router.

| Key / PHP file | Data shown |
| --- | --- |
| `timeline` | Recent cached uploads. |
| `channels` | Monitored channels, last upload/check information and gap averages. |
| `analytics` | Cached totals and channel/location summaries. |
| `gaps` | Largest recorded upload gaps and warning/critical counts. |
| `locations` | Title-keyword location counts and recent matching uploads. |
| `frequency` | Channel gap statistics and upload weekday/hour distributions. |
| `trends` | Monthly upload counts and gap averages, up to 12 months. |
| `search` | Title search using the `q` parameter. |

Example: `/youtube-dashboard/?tab=search&q=London`.

Results describe the local cache. RSS history and the gap calculation limitations
are explained in the [application guide](../README.md). Do not interpret location
labels as verified locations or upload statistics as audience analytics.

Add new keys to `index.php` and navigation in `includes/header.php`. Bind user
values through `query()` parameters and escape text at the HTML boundary. Check
empty results, search characters and narrow-screen tables after a view change.
