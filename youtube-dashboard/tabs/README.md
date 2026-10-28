# YouTube views

Open views through `/youtube-dashboard/?tab=KEY`. `index.php` allow-lists the eleven
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
| `tags` | Clickable title hashtags and derived non-location topic labels. |
| `calendar` | Daily UTC upload counts for a selected month. |
| `health` | Last successful channel checks and latest cached uploads. |
| `search` | Title search using the `q` parameter. |

Example: `/youtube-dashboard/?tab=search&q=London`.

Results describe the local cache. RSS history and the gap calculation limitations
are explained in the [application guide](../README.md). Do not interpret location
labels as verified locations or upload statistics as audience analytics.

Add new keys to `index.php` and navigation in `includes/header.php`. Bind user
values through `query()` parameters and escape text at the HTML boundary. Check
empty results, search characters and narrow-screen tables after a view change.

All views read request-local `dashboard_videos`, `dashboard_channels` and
`dashboard_upload_gaps` views created by `includes/filters.php`. These temporary
SQLite views do not change stored data or the importer. Scope new video queries
through them to keep totals consistent. Use `dashboard_url()` for links that
preserve channel/period, and `dashboard_hidden_filters()` for tab-specific forms.
Use original tables only for deliberately global choices, location exclusions,
and feed health's latest-ever upload. Channel filters use IDs, not names.
