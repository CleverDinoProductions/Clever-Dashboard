# Clever Dashboard

Clever Dashboard is a collection of PHP dashboards and maintenance scripts served from a single landing page. The repository currently contains football analytics, IRC activity reporting, YouTube upload monitoring, and a small administration area.

## Applications

| Path | Purpose |
| --- | --- |
| `/` | Landing page linking to the available dashboards and administration tools. |
| `/football-stats/` | League tables, fixtures, comparisons, team trackers, simulations, regional leagues, World Cup, and Nations League views. |
| `/irc-dashboard/` | Read-only activity and queue reporting for a The Lounge/IRC SQLite log database. |
| `/youtube-dashboard/` | Channel upload history, trends, gaps, locations, and search. |
| `/admin/` | Account and football administration; the log viewer needs separate web-server protection. |
| `/account/` | Account setup, registration, login, preferences, and saved simulated results. |
| `/CMRegister/` | Legacy plain-text registration/login endpoints backed by shared accounts. |

## Documentation

| Area | Guide |
| --- | --- |
| Accounts and preferences | [Account setup and storage](account/README.md) |
| Administration | [Permissions, configuration and diagnostics](admin/README.md) |
| Shared libraries | [Module responsibilities and persistence](lib/README.md) |
| Football | [Setup and data workflows](football-stats/README.md), [tab routing](football-stats/tabs/README.md), [competition rules](football-stats/config/README.md) |
| IRC | [Log schema, configuration and maintenance](irc-dashboard/README.md) |
| YouTube | [RSS fetcher, storage and data limitations](youtube-dashboard/README.md) |
| Tests | [Account/admin checks](tests/README.md), [football PHP/Node.js checks](football-stats/tests/README.md) |
| Legacy clients | [Plain-text registration/login endpoints](CMRegister/README.md) |

## Requirements

- PHP 8 with both PDO SQLite (`pdo_sqlite`) and SQLite3 (`sqlite3`) extensions
- SQLite 3
- Python 3 for import and synchronization jobs
- The Python packages used by a selected fetcher (for example `requests`)

## Local setup

1. Clone the repository and enter its root directory.
2. Review each application's `config.php` and replace local paths and placeholder credentials. Do not commit production credentials or API keys.
3. Initialize or populate the database needed by the dashboard you want to use. See [`football-stats/README.md`](football-stats/README.md) for the football data workflow; the YouTube database can be initialized with `php youtube-dashboard/setup-db.php`.
4. Start PHP's development server from the repository root:

   ```sh
   php -S localhost:8000
   ```

5. Open <http://localhost:8000>. Sub-applications can also be opened directly, for example <http://localhost:8000/football-stats/>.

### YouTube and IRC data

The YouTube fetcher uses PHP with SQLite3, SimpleXML and cURL. Run it from any
working directory:

```sh
php youtube-dashboard/fetch-youtube.php
```

It reads the shared database path and channel list from
`youtube-dashboard/config.php`. Optional API backfilling and an admin fetch button
are documented in [the YouTube guide](youtube-dashboard/README.md#api-backfilling-and-admin-fetch).
RSS feeds supply recent uploads, so the RSS source
does not backfill a channel’s complete history.

For IRC, set `$dbPath` in `irc-dashboard/config.php` to an existing The Lounge
SQLite log database readable by PHP. Dashboard queries enforce SQLite
`query_only` mode. Backfill and import scripts are separate maintenance tools;
review their paths and target database before running them.

### Accounts and administration

On a new installation, visit `/admin/admin.php`. You will be redirected to a
one-time setup form that creates the first user in the **Administrators** group.
After that, visitors can register as standard **Members** at `/account/register.php`.
Administrators can create access groups, manage users and group membership, and
edit football team metadata, points deductions, and dashboard copy from the
admin panel. Built-in **Football Editors**, **Analysts**, and **Data Operators**
groups provide clearer starting roles in addition to Members and Administrators;
group permissions can be changed in the admin panel. Football editors can use
the football configuration sections without receiving account administration
access, while data operators can run the protected data refresh actions.

Signed-in users can choose their default competition, league, view, favourite
team, accent colour, and navigation density from **My account**. Preferences are
stored per account and applied whenever the football dashboard is opened without
an explicit destination in its URL.

Signing in automatically keeps the account signed in on that browser for 30
days, including after browser restarts or PHP session cleanup. The persistent
cookie is HttpOnly, SameSite=Lax, and Secure on HTTPS; only a hash of its random
token is stored in the account database. Signing out revokes that browser's
token. Disabling or deleting an account, or changing its password, revokes its
persistent sign-ins. Other browsers retain their own independent sign-ins.

Account data is stored in `data/accounts.sqlite3` by default. Set
`CLEVER_ACCOUNTS_DB` to place it outside the web root. Set `CLEVER_FOOTBALL_DB`
to point the configuration editor at a non-default football database. Both
locations must be writable by the PHP process and should be backed up.
Set `CLEVER_WORLD_CUP_DB` when World Cup data is stored separately. If the
canonical SQLite files are absent, the football dashboard now uses the bundled
legacy database instead of attempting to create a database during a web request.

The built-in PHP server is intended for local development only. In production, configure the web server document root to this repository, restrict access to administrative and diagnostic scripts, and keep writable databases outside public access where possible.

## Repository layout

```text
.
├── index.php                 # Site landing page
├── config.php                # Root administration session configuration
├── admin/                    # Administration interface
├── account/                  # Account setup, sign-in, and preferences
├── lib/                      # Shared authentication, settings, and saved results
├── tests/                    # Account, admin, and shared-feature regression tests
├── CMRegister/               # Legacy login compatibility
├── football-stats/           # Football analytics application and importers
├── irc-dashboard/            # IRC aggregate analytics application
└── youtube-dashboard/        # YouTube monitoring application and fetcher
```

## Development checks

There is no repository-wide build step. Useful checks are:

```sh
# Syntax-check all PHP files
find . -name '*.php' -print0 | xargs -0 -n1 php -l

# Run all standalone PHP regression tests (stop on the first failure)
(
    set -eu
    for test in tests/*-test.php football-stats/tests/*-test.php; do php "$test"; done
)

# Run browser-logic regression tests with Node.js
(
    set -eu
    for test in football-stats/tests/*-test.js; do node "$test"; done
)
```

Node.js is needed only for the JavaScript regression tests.

Python importers contact third-party services and may require credentials and network access. Review their configuration before running them.

## Security notes

- Replace all example passwords and move secrets to environment variables or non-versioned configuration before deployment.
- Treat database files and imported logs as private operational data.
- Keep the IRC dashboard connection read-only; review rendered nicknames and message details before exposing log-derived pages.
- Protect `/admin/`, debug utilities, importers, and setup scripts at the web-server level.
