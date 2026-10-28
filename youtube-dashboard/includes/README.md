# YouTube layout and database helpers

`index.php` loads these files around the selected tab:

- `db.php` loads `../config.php`, opens `DB_PATH` using SQLite3, sets a 5000 ms
  busy timeout and enables WAL mode. It exposes the global `$db` connection.
- `query($sql, $params)` prepares SQL, binds supplied parameters and returns a
  `SQLite3Result`. Fetch rows with `fetchArray(SQLITE3_ASSOC)` or `SQLITE3_NUM`.
- `getValue($sql, $params)` returns the first column of the first row, or `null`.
- `header.php` and `footer.php` render the shared shell and navigation.

These are include files, not independent page routes. `db.php` does not initialize
application tables; use [setup](../README.md#setup-and-first-fetch) first. WAL mode
requires a writable database directory as well as access to the database itself.
Bind search input rather than interpolating it into SQL, and escape returned text
when rendering HTML. Update navigation alongside the router when adding a view.

`filters.php` sets up request-local scoped read views and shared filter controls;
`tags.php` derives and deduplicates title hashtags/topics while excluding known
location keywords. Neither changes persistent data.
