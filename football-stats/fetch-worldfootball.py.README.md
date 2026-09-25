# `fetch-worldfootball.py`

This legacy Python importer fetches football-data.org standings and matches, writes the local SQLite databases, and rebuilds league-table snapshots. It currently handles Premier League, Championship, and World Cup records.

## Before running

- Install Python 3 and `requests`.
- Back up `football-stats.sqlite3` and `world-cup-stats.sqlite3`.
- Remove the credential embedded in the script and load the football-data.org token from an environment variable before using it in production.
- Review the configured season and competition endpoints; this importer is not a general command-line tool.

Run from this directory with `python3 fetch-worldfootball.py`. For normal English-league fixture merging, prefer the environment-variable-based `sync-matches.py` workflow documented in the main Football Stats README.
