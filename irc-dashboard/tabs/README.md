# IRC view routes

Load tabs through `/irc-dashboard/?tab=KEY`. `index.php` allow-lists these files,
initializes the read-only `$db` connection and wraps output in the shared shell.
Unknown keys resolve to `general`.

| Key | Purpose / query parameters |
| --- | --- |
| `general` | Recent network activity, channels and user/message details. |
| `channels` | Channel statistics; `ch` selects a channel and `days` a comparison period. |
| `invitations` | Bot invitation activity; `timeframe` and `ch` select the scope. |
| `interviews` | Queue/interview statistics; `timeframe` selects the period. |
| `invitation-history` | Historical invitation reporting. |
| `interview-history` | History drill-down using `year`, `month`, `week`, `day`. |
| `windows` | Inferred windows; `year`, `month`, `window`, `method` control selection. |
| `timezones` | Timezone-oriented activity presentation. |

Example: `/irc-dashboard/?tab=channels&ch=%23anonamouse.net&days=30`.
Encode `#` as `%23` in URLs; an unencoded hash starts a browser fragment.

These views depend on The Lounge's millisecond timestamps, JSON payloads and
network-specific bot/channel conventions. They are not independent PHP entry
points. Do not add writes to dashboard queries. Some output contains identities
and message details; follow the deployment guidance in the [application README](../README.md).

For a new view register its key in `index.php` and its navigation link in
`includes/header.php`. Check missing data, date boundaries, UTC conversions and
escaped channel names when modifying filters.
