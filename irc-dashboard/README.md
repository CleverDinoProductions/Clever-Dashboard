# IRC activity dashboard

PHP views over an existing The Lounge SQLite log database, including channel
activity, invitation/interview history, inferred interview windows and timezones.
The dashboard connection enforces `PRAGMA query_only = 1`; import scripts use
separate connections and can write.

## Setup

Requirements: PHP 8 with PDO SQLite and SQLite JSON functions (`json_extract`),
an existing compatible log database and filesystem read access for PHP. This
application does not provide a fresh-log-database setup script.

1. Set `$dbPath` in `config.php` to the existing The Lounge SQLite file.
2. Serve the repository root with `php -S localhost:8000` for development.
3. Open <http://localhost:8000/irc-dashboard/>; the default view is `general`.

Queries expect a `messages` table with `time` in Unix **milliseconds**, `channel`,
`type` and a JSON `msg` payload. Views read fields including `$.text`,
`$.from.nick`, `$.from.mode` and `$.users`. Queue views assume particular bot
messages, channels and mode-event formats; adapt those assumptions to another
network. Copying an unrelated SQLite file will not supply this schema.

## Views and interpretation

See [tabs](tabs/README.md) for routes and filters and
[shared includes](includes/README.md) for queue classification. Queries largely
use UTC. Window/interview classifications are inferred from logged events and
can be incomplete when logs or bot snapshots are missing.

Although many queries aggregate activity, several pages display nicknames or
message details. Review actual rendered views before exposing operational logs;
read-only access does not anonymize results. Keep diagnostics and source databases
behind deployment access controls.

## Import and diagnostic tools

| Script | Purpose and configuration to review |
| --- | --- |
| `backfill-logs-thelounge.py` | Import The Lounge text logs; set `DB_PATH`, `LOUNGE_LOGS`, `NETWORK_UUID` and date bounds. |
| `backfill-logs-znc.py` | Import daily ZNC logs; set `DB_PATH`, `ZNC_LOGS`, date bounds and network assumptions. |
| `backfill-thelounge-debug.py` | Diagnostic variant of the historical Lounge import workflow. |
| `import-text-logs.php` | Historical gap repair with its own Windows paths, network UUID and gap start. |
| `check-schema.php` / `check-log-format.php` | Inspect configured schema/sample rows or historical text lines; paths are independent of `config.php`. |
| `debug-mousebot.php` / `debug-windows.php` | Inspect bot events or window-query behavior using dashboard configuration. |
| `create-indexes.php` | Attempts to add channel/time and type/channel/time indexes. |

These scripts contain installation-specific paths and historical date ranges;
they are not general argument-driven import commands. Python backfills use the
standard library. Back up the target database and validate a disposable copy
before applying an import to live logs. `create-indexes.php` currently includes
the read-only dashboard configuration, so its writes fail under `query_only`;
index maintenance needs a separate authorized writable connection, without
weakening the dashboard connection.

## Troubleshooting

For missing-file errors check the absolute path and service-user permissions.
For `no such table` or malformed JSON errors check schema/payload compatibility.
For missing windows or invitations check channel names, bot aliases and event
formats before importing more rows. There is no dedicated IRC automated test
suite; syntax-check changed PHP and inspect representative populated/empty views
against a non-production log copy. Diagnostics may print raw log contents.
