# fetch-worldfootball.py

This Python script fetches football data from the football-data.org API and updates the local SQLite database. It recalculates league table snapshots, imports match results, and ensures the latest snapshot mirrors the current live table.

## Key Functions
- Fetches Premier League, Championship, and World Cup data
- Recalculates league_table_snapshots from match results
- Inserts pre-season (matchweek 0) and current matchweek snapshots
- Handles API rate limits and data consistency

Run this script regularly to keep your dashboard up to date.