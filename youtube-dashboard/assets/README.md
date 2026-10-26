# YouTube presentation assets

`style.css` defines the dashboard shell, navigation, statistics cards, tables,
channel statuses and responsive presentation. It is loaded by the shared header;
there is no asset compilation step or package installation for the PHP UI.

Keep shared visual changes in this stylesheet. Verify timeline, channel cards,
search results and wide tables at desktop and mobile widths. Preserve visible
focus styles and readable status colours. Data thresholds live in configuration
and tab queries; changing CSS does not change warning calculations.

See [view routes](../tabs/README.md) for the pages to check.
