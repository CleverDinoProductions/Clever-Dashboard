# Accounts and preferences

The account pages use the shared SQLite account system in
[`lib/auth.php`](../lib/auth.php). PHP 8 with PDO SQLite is required.

## First installation

Serve the repository root and open `/account/setup.php` (or `/admin/admin.php`,
which redirects there when no users exist). Create the first administrator.
Setup returns HTTP 404 once any account exists. Later users register at
`/account/register.php` and join Members by default.

Usernames accept 3–50 letters, numbers, or underscores; passwords require at least
10 characters and registration requires a valid email address. The login page
accepts username or email. Passwords are hashed by the shared authentication code.

## Pages

| File | Purpose |
| --- | --- |
| `setup.php` | One-time creation of the first administrator. |
| `register.php` | Member registration. |
| `login.php` | Sign-in and return to a local destination. |
| `logout.php` | Revoke this browser's persistent sign-in and end its session. |
| `index.php` | Signed-in profile, football preferences, and saved simulated results. |
| `account.css` | Account and administration page styling. |

Preferences include season, league, view, favourite team, accent colour, and
compact navigation. Explicit football URL destinations take precedence over
account defaults; unavailable destinations are resolved by the football router.
Saved result sets store custom fixture outcomes, exclusions, scoring and deductions
per user, league and season. The account page can open or delete these sets.

## Storage and sessions

`CLEVER_ACCOUNTS_DB` overrides `data/accounts.sqlite3` at the repository root.
The account library creates the directory and migrates tables on first use. PHP
needs write access to the directory as well as the database. Back up this database
to preserve users, groups, preferences, login-token hashes and saved result sets.

Sign-in lasts 30 days on each browser. Cookies use HttpOnly, SameSite=Lax and
Secure on HTTPS. The database stores a token hash; logout revokes the current
browser token. Account deletion, disabling or a password change invalidates
persistent sign-ins. Do not delete the account database to reset a single user.

For roles and user management see [administration](../admin/README.md).
For regression commands see [shared tests](../tests/README.md).
