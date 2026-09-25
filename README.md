# Clever Dashboard

Clever Dashboard is a collection of PHP dashboards and maintenance scripts served from a single landing page. The repository currently contains football analytics, IRC activity reporting, YouTube upload monitoring, and a small administration area.

## Applications

| Path | Purpose |
| --- | --- |
| `/` | Landing page linking to the available dashboards and administration tools. |
| `/football-stats/` | League tables, fixtures, comparisons, team trackers, simulations, and World Cup views. |
| `/irc-dashboard/` | Read-only aggregate reporting for a The Lounge/IRC SQLite log database. |
| `/youtube-dashboard/` | Channel upload history, trends, gaps, locations, and search. |
| `/admin/` | Session-protected database and log administration pages. |
| `/CMRegister/` | Registration/login processing used by the site. |

## Requirements

- PHP 8 with the PDO SQLite extension
- SQLite 3
- Python 3 for import and synchronization jobs
- The Python packages used by a selected fetcher (notably `requests`, `feedparser`, and `python-dateutil`)

## Local setup

1. Clone the repository and enter its root directory.
2. Review each application's `config.php` and replace local paths and placeholder credentials. Do not commit production credentials or API keys.
3. Initialize or populate the database needed by the dashboard you want to use. See [`football-stats/README.md`](football-stats/README.md) for the football data workflow; the YouTube database can be initialized with `php youtube-dashboard/setup-db.php`.
4. Start PHP's development server from the repository root:

   ```sh
   php -S localhost:8000
   ```

5. Open <http://localhost:8000>. Sub-applications can also be opened directly, for example <http://localhost:8000/football-stats/>.

The built-in PHP server is intended for local development only. In production, configure the web server document root to this repository, restrict access to administrative and diagnostic scripts, and keep writable databases outside public access where possible.

## Repository layout

```text
.
├── index.php                 # Site landing page
├── config.php                # Root administration session configuration
├── admin/                    # Administration interface
├── CMRegister/               # Registration/login handlers
├── football-stats/           # Football analytics application and importers
├── irc-dashboard/            # IRC aggregate analytics application
└── youtube-dashboard/        # YouTube monitoring application and fetcher
```

## Development checks

There is no repository-wide build step. Useful checks are:

```sh
# Syntax-check all PHP files
find . -name '*.php' -print0 | xargs -0 -n1 php -l

# Run the football regression tests
php football-stats/tests/competition-rules-test.php
php football-stats/tests/table-view-movement-test.php
php football-stats/tests/table-view-team-crests-test.php
```

Python importers contact third-party services and may require credentials and network access. Review their configuration before running them.

## Security notes

- Replace all example passwords and move secrets to environment variables or non-versioned configuration before deployment.
- Treat database files and imported logs as private operational data.
- Keep the IRC dashboard's database connection read-only and expose only aggregate results.
- Protect `/admin/`, debug utilities, importers, and setup scripts at the web-server level.
