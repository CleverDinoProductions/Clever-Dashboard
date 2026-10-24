<style>
.standings-hint, .standings-guide { color: #c5cbd5; font-size: .875rem; line-height: 1.6; }
.standings-scroll { max-height: 72vh; overflow: auto; border: 1px solid #525b6b; border-radius: 10px; isolation: isolate; }
.standings-scroll:focus-visible, .standings-guide summary:focus-visible { outline: 3px solid #a5b4fc; outline-offset: 3px; }
.readable-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .875rem; font-variant-numeric: tabular-nums; }
.readable-table caption { padding: 12px 16px; text-align: left; color: #e2e8f0; background: #202630; font-weight: 600; }
.readable-table th, .readable-table td { padding: 12px; text-align: right; white-space: nowrap; border-bottom: 1px solid #454d5a; }
.readable-table thead th { position: sticky; top: 0; z-index: 3; background: #252d3a; color: #f1f5f9; vertical-align: bottom; }
.readable-table .standings-groups th { position: static; background: #343f52; text-align: center; font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; }
.readable-table tbody th { top: auto; z-index: 1; color: #f1f5f9; font-weight: 500; }
.readable-table .standings-team { position: sticky; left: 0; text-align: left; background: #252d3a; min-width: 190px; max-width: 260px; box-shadow: 2px 0 0 #525b6b; }
.readable-table thead .standings-team { z-index: 4; }
.readable-table .team-cell { white-space: normal; }
.readable-table .standings-team-name { display: block; }
.readable-table small { display: block; color: #b7c2d3; font-size: .7rem; font-weight: normal; }
.readable-table tbody tr:nth-child(even) td { background-color: #ffffff06; }
.readable-table tbody tr:hover td, .readable-table tbody tr:hover th { background-color: #38465b; }
.readable-table .standings-points { font-weight: 800; color: #f8fafc; }
.readable-table .standings-projection { font-weight: 700; color: #c7d2fe; }
.readable-table .standings-reached { color: #8ce8bb; }
.readable-table .standings-unreachable { color: #ffb4b4; font-size: .8rem; }
.table2-table :is(th, td):is(:nth-child(8), :nth-child(10), :nth-child(12), :nth-child(14)) { border-left: 1px solid #647084; }
.readable-table .standings-group-start { border-left: 1px solid #647084; }
.readable-table .standings-positive { color: #8ce8bb; }
.readable-table .standings-negative { color: #ffb4b4; }
.readable-table .standings-empty { padding: 36px 16px; text-align: center; color: #c5cbd5; }
.standings-guide { margin-top: 16px; }
.standings-guide summary { cursor: pointer; color: #c7d2fe; }
@media (max-width: 600px) {
    .readable-table th, .readable-table td { padding: 10px 8px; }
    .readable-table .standings-team { min-width: 145px; max-width: 175px; }
    .readable-table .team-crest { width: 20px; height: 20px; margin-right: 6px; }
}
.readable-table .row-zone > :first-child { border-left: 4px solid var(--default-zone-color, transparent); }
.readable-table .row-zone > :last-child { border-right: 4px solid var(--season-zone-color, transparent); }
</style>
