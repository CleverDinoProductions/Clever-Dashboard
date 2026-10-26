# Administration

Open `/admin/admin.php` after completing [account setup](../account/README.md).
The panel uses the shared account library; the placeholder credentials in the
repository-root `config.php` are not the account panel's login mechanism.

## Permissions

| Built-in group | Initial capabilities |
| --- | --- |
| Administrators | Account/group administration, football configuration, and data updates. |
| Football Editors | Football configuration and data updates. |
| Data Operators | Protected data-update actions. |
| Members / Analysts | Standard account features, without administrative permissions. |

Administrators can edit group permissions. A user's capabilities combine their
group memberships. Football Editors can open the teams, deductions, rules and
dashboard sections; the users, groups and overview sections require administrator
access. Data Operators use update controls in the football dashboard.

## Panel sections

Use `/admin/admin.php?section=SECTION`:

- `users` and `groups`: manage accounts, status, memberships and group capabilities.
- `teams`: edit display names, common names, nicknames, short codes and colours.
- `deductions`: maintain positive points deductions by competition, season and team.
- `rules`: edit league defaults or a complete season override, including scoring,
  club counts, season lengths, comparison targets, quarter boundaries and zones.
- `dashboard`: edit dashboard copy and league-specific custom-rule team groups.

`competition-rules.php` is a panel component, not an independent entry point.
Load it through `admin.php?section=rules`. Leave season blank for league defaults,
or use a consecutive year pair such as `2026-2027`. Reset removes the selected
saved override. See [competition configuration](../football-stats/config/README.md)
for precedence and overlapping zone display rules.

## Databases and diagnostics

Accounts use `CLEVER_ACCOUNTS_DB`; football settings use `CLEVER_FOOTBALL_DB`
with the canonical/legacy fallbacks described in the
[football setup guide](../football-stats/README.md#setup). Settings migrations
need a writable database directory. Account administration remains available if
football configuration cannot be opened; the panel reports that failure.
Panel POST actions verify CSRF tokens.

`view-log.php` displays the last 50 lines of `/var/log/update-stats.log`, polling
every 30 seconds. It relies on web-server access protection and does not call the
shared account guard itself. Restrict this endpoint separately before deployment
and update its configured log path if necessary.

Run the admin checks listed in [tests](../tests/README.md).
