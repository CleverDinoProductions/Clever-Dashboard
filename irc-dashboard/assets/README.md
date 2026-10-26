# IRC presentation assets

`style.css` provides the shared dashboard layout, navigation, tables, cards and
responsive styling. The header loads it directly; no bundler or asset build is
required. Several historical tabs also have inline styles, so check their rules
when consolidating presentation here.

Verify both recent and historical views at desktop and mobile widths after CSS
changes. Wide data tables should remain usable and status colours readable.
Styles do not change queue classification or anonymize log data. View routes are
documented in [tabs](../tabs/README.md).
