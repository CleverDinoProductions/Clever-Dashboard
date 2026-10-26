# YouTube upload dashboard

A PHP/SQLite dashboard backed by cached public YouTube RSS uploads. It monitors
upload dates, gaps, title-based locations, search and trends. No YouTube API key
is used, and the fetcher is separate from web requests.

## Setup and first fetch

Requirements: PHP 8 with SQLite3, Python 3 with `feedparser` and `python-dateutil`,
and network access to YouTube RSS feeds for imports. From the repository root:

```sh
python3 -m venv /tmp/clever-youtube-venv
/tmp/clever-youtube-venv/bin/pip install feedparser python-dateutil
cd youtube-dashboard
php setup-db.php
/tmp/clever-youtube-venv/bin/python fetch-youtube.py
cd ..
php -S localhost:8000
```

Open <http://localhost:8000/youtube-dashboard/>. The default tab is `timeline`;
see [tab routes](tabs/README.md) for all views. The temporary virtual environment
above is for local use; use a persistent installation path for a scheduled job.

## Configuration and database

Administrators can add, rename or remove monitored channels at
`/admin/admin.php?section=youtube`, using one `Channel name | Channel ID` per line.
PHP's `config.php` and the Python fetcher both read `channels.json` beside this
file. The directory must be writable by PHP to save changes atomically, and the
fetcher must be able to read the file. An empty list stops fetching; removing a
channel preserves previously imported videos. Changes apply on the next fetcher
run. Back up `channels.json` along with your database.

`config.php` defines PHP's `DB_PATH`, timezone and display constants. The Python
fetcher's `DB_PATH` and `LOCATIONS` remain independent settings.
Some views also contain literal display thresholds;
check the relevant tab when changing warning/gap settings.

PHP defaults to `youtube-dashboard.sqlite3` beside `config.php`. Python uses that
filename relative to its working directory, so run it from this directory.
`setup-db.php` creates `videos`, `channels`, `upload_gaps` and lookup indexes.
The fetcher can create its own basic schema, but the PHP setup adds the indexes.
`includes/db.php` opens SQLite3 with a five-second busy timeout and WAL mode;
the PHP process needs database and directory write access.

## Refreshing and interpreting data

Rerun `fetch-youtube.py` from this directory to insert unseen video IDs and update
channel check metadata. Schedule that same working directory/interpreter pair
with your scheduler. `run-fetcher.bat` is a Windows example with a hard-coded
XAMPP path and `logs/fetch.log` output; update the path and create its log directory
before using it.

RSS exposes recent uploads, not full channel history. Gaps are calculated against
older videos already in the local database when a new row is inserted; missing
history and arrival order can affect them, and existing gaps are not rebuilt on
every refresh. Locations are simple title keyword matches, not verified filming
locations. The dashboard does not measure views, watch time or algorithm impact.

## Troubleshooting and maintenance

If tables are missing, run setup against the same file PHP opens. If data is
missing, check the configured channel IDs, feed access and fetcher output.
`check-db.php` prints table counts and recent records for diagnosis; keep it
restricted in deployed installations. Back up the database before manual repairs.

For UI changes see [includes](includes/README.md), [assets](assets/README.md) and
[tabs](tabs/README.md). There is no dedicated automated YouTube test suite;
syntax-check changed PHP and verify populated and empty views locally.
