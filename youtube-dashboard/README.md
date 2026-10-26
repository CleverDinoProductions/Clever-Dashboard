# YouTube upload dashboard

A PHP/SQLite dashboard backed by cached public YouTube RSS uploads. It monitors
upload dates, gaps, title-based locations, search and trends. No YouTube API key
is used, and the fetcher is separate from web requests.

## Setup and first fetch

Requirements: PHP 8 with SQLite3, SimpleXML and cURL extensions, and network
access to YouTube RSS feeds for imports. No Python packages are required.
From the repository root:

```sh
php youtube-dashboard/setup-db.php
php youtube-dashboard/fetch-youtube.php
php -S localhost:8000
```

Open <http://localhost:8000/youtube-dashboard/>. The default tab is `timeline`, with channel and 7/30/90-day filters, result counts
and an empty state. Summary cards describe the whole library, independent of
filters. The feed status shows the oldest recorded channel check and flags checks
older than 24 hours; page render time is displayed separately. Page reloads do not
run the fetcher, and forms are not interrupted by automatic page refreshes.

See [tab routes](tabs/README.md) for the other views.

## Configuration and database

Administrators can add, rename or remove monitored channels at
`/admin/admin.php?section=youtube`, using one `Channel name | Channel ID` per line.
`config.php`, the PHP fetcher and the legacy Python fetcher share `channels.json`.
The directory must be writable by PHP to save changes atomically, and fetchers
must be able to read the file. An empty list stops fetching; removing a channel
preserves previously imported videos. Changes apply on the next fetcher run.
Back up `channels.json` along with your database.

`config.php` defines the shared `DB_PATH`, timezone and display constants used by
PHP, including the fetcher. The database path is anchored to this directory, so
the PHP fetcher can run from any working directory. Location keywords are defined
in `fetch-youtube.php`.

`setup-db.php` creates `videos`, `channels`, `upload_gaps` and lookup indexes.
The PHP fetcher also creates this schema and its indexes when needed.
`includes/db.php` opens SQLite3 with a five-second busy timeout and WAL mode;
the PHP process needs database and directory write access.

## Refreshing and interpreting data

Run `php /absolute/path/to/youtube-dashboard/fetch-youtube.php` to insert unseen
video IDs and update channel check metadata. Use that command in cron or another
scheduler. `run-fetcher.bat` runs the PHP fetcher beside the batch file and creates
its log directory; ensure `php` is on PATH (or use its full executable path).
The fetcher is CLI-only and never runs during web requests. It applies connection
and request timeouts and returns exit code 1 if any channel fails. A failed channel
is rolled back and its last successful check time is preserved; other channels
continue importing. Successful empty feeds still update check metadata.
Configured channel IDs must start with `UC` and contain 24 ID characters in
total. The feed channel ID may omit the `UC` prefix, as YouTube RSS does;
every entry must match the full configured channel ID exactly. Video IDs
must contain 11 letters, digits, underscores or hyphens and are case sensitive.
Invalid IDs reject the feed before any database changes.

RSS exposes recent uploads, not full channel history. Entries are imported oldest
first so gaps can use earlier entries in the same feed. Gaps use elapsed whole
days against the previous locally recorded upload. Missing history can affect
them; existing gaps are not rebuilt on refresh. The original `fetch-youtube.py`
remains as a legacy reference and is no longer used by the documented workflow.
Locations are simple title keyword matches, not verified filming
locations. The dashboard does not measure views, watch time or algorithm impact.

## Troubleshooting and maintenance

If tables are missing, run setup against the same file PHP opens. If data is
missing, check the configured channel IDs, feed access and fetcher output.
`check-db.php` prints table counts and recent records for diagnosis; keep it
restricted in deployed installations. Back up the database before manual repairs.

For UI changes see [includes](includes/README.md), [assets](assets/README.md) and
[tabs](tabs/README.md). Run the offline fetcher regression checks with:

```sh
php youtube-dashboard/tests/fetcher-test.php
```

The tests use an in-memory database and fixture feeds without contacting YouTube.
Syntax-check changed PHP and verify populated and empty views locally.
