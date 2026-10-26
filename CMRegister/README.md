# Legacy account endpoints

These plain-text endpoints support older clients using the shared SQLite account
system. New browser flows should use [`account/`](../account/README.md).
Neither file renders a login or registration form.

| Endpoint | Input field names | Successful response |
| --- | --- | --- |
| `login.php` | `Name`, `Pass` | `yes` |
| `processor.php` | `login`, `pass`, `email` | `Account made!` |

Both accept POST fields, with GET fields retained as a compatibility fallback.
Use POST over HTTPS so credentials do not appear in query strings. These endpoints
do not use the form pages' CSRF-token verification.

Login returns HTTP 400 for missing credentials and a plain-text failure message
for invalid credentials. Registration uses the shared username/email/password
validation, returns HTTP 400 for invalid input and HTTP 409 for duplicate accounts.
New registrations receive the default Members group. Successful login uses the
same session and persistent cookie as the account pages.

Database selection comes from `CLEVER_ACCOUNTS_DB`, falling back to
`data/accounts.sqlite3`. These scripts do not use the root `config.php` password.
