# IRC shared layout and queue analysis

`header.php` and `footer.php` provide the dashboard shell and navigation around
router-loaded tabs. Shared presentation lives in `../assets/style.css`.

`queue-analytics.php` defines `QueueAnalytics`, constructed with the dashboard
PDO connection. It classifies queue exits using queue position, voice-mode events,
interaction and disconnect evidence, and supplies queue-related metrics. Its
voice channel is hard-coded in the class and must match the installation.

Timestamps passed to queue classification use Unix seconds; database event times
use milliseconds, so the helper converts its lookup bounds. Preserve that unit
boundary when changing callers. Confidence values are heuristic classifications,
not independently verified interview outcomes; missing snapshots and events can
change the result.

Use the router-provided read-only connection. When changing event interpretation,
check actual payload formats against a private test copy of logs and verify empty
queues, first-position exits, disconnects and absent mode events. See
[application setup and data limitations](../README.md).
