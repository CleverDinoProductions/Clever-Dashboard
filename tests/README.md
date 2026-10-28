# Account and administration regression tests

Standalone PHP tests cover shared services and their account/admin integration.
Requirements are PHP 8 with PDO SQLite, a writable temporary directory and the
repository source files. No Composer or PHPUnit installation is needed.

From the repository root:

```sh
php tests/account-system-test.php

(
    set -eu
    for test in tests/*-test.php; do php "$test"; done
)
```

Run each file in its own process. Tests configure temporary account/football
paths or in-memory SQLite fixtures before loading shared services; do not replace
those paths with production databases. An assertion failure throws an exception
and returns a nonzero status. The subshell above stops at the first failure.

| Test | Behavior checked |
| --- | --- |
| `account-system-test.php` | Registration validation, role defaults, preferences, sign-in cookies, revocation and account lifecycle. |
| `admin-youtube-test.php` | YouTube fetch controls, CSRF fields and API key visibility. |
| `admin-page-test.php` | Account administration remains usable when the football database cannot open. |
| `admin-competition-rules-test.php` | Rules panel output and editable settings. |
| `movement-preferences-test.php` | Per-account/per-mode movement preferences. |
| `saved-results-test.php` | Account-owned, league/season-scoped saved simulated result state. |
| `team-groups-test.php` | League-specific team group settings and fallback behavior. |

Football rendering, provider-sync fixtures and browser-logic checks live in
[`football-stats/tests/`](../football-stats/tests/README.md). For syntax checking:

```sh
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

YouTube importer regression checks are documented in
[`youtube-dashboard/README.md`](../youtube-dashboard/README.md). IRC manual checks
and data setup are described in its README.

The HTTP sign-in regression test also requires Python 3 and permission to start
PHP's built-in server on a temporary loopback port:

```sh
python3 tests/login-session-test.py
```

It uses a temporary account database and session directory. It checks login return
routes, session persistence, cache headers with PHP's cache limiter disabled,
and recovery from stale/missing tokens and deleted sessions.
