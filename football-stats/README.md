# Football Stats Dashboard

A PHP and Python-powered dashboard for football statistics, league tables, match results, and advanced analytics for the Premier League, Championship, and World Cup.

## Features
- Live and historical league tables (snapshots by matchweek)
- Match results and analytics
- Comparison tools for any two matchweeks or live vs. snapshot
- Team-specific breakdowns (e.g., Leeds United)
- Relegation and block-based analysis
- World Cup group and knockout stages

## Directory Structure
- `fetch-worldfootball.py` — Fetches data from football-data.org API, updates database, recalculates snapshots
- `football-stats.sqlite3` — SQLite database storing all football data
- `index.php` — Main entry point and dashboard config
- `includes/` — Shared PHP includes (table rendering, helpers, etc.)
- `tabs/` — All dashboard tab views, organized by competition and season
    - `premier-league/2025-2026/` — Premier League tabs for 2025-2026
    - `championship/2025-2026/` — Championship tabs for 2025-2026
    - `world-cup/` — World Cup tabs
- `assets/` — CSS and static assets

## Data Flow
1. Python script fetches and processes data from the API
2. Data is stored in SQLite (matches, league tables, snapshots, etc.)
3. PHP dashboard reads from SQLite and renders interactive views

## Setup
1. Install dependencies: Python 3, PHP, SQLite
2. Run `python3 fetch-worldfootball.py` to fetch/update data
3. Serve the project with a PHP server (e.g., `php -S localhost:8000`)

---

For details on each tab, see the README in each tab directory.