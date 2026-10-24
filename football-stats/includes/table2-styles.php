<style>
.table2-hint, .table2-guide { color: #c5cbd5; font-size: .875rem; line-height: 1.6; }
.table2-scroll { max-height: 72vh; overflow: auto; border: 1px solid #525b6b; border-radius: 10px; isolation: isolate; }
.table2-scroll:focus-visible, .table2-guide summary:focus-visible { outline: 3px solid #a5b4fc; outline-offset: 3px; }
.table2-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .875rem; font-variant-numeric: tabular-nums; }
.table2-table caption { padding: 12px 16px; text-align: left; color: #e2e8f0; background: #202630; font-weight: 600; }
.table2-table th, .table2-table td { padding: 12px; text-align: right; white-space: nowrap; border-bottom: 1px solid #454d5a; }
.table2-table thead th { position: sticky; top: 0; z-index: 3; background: #252d3a; color: #f1f5f9; vertical-align: bottom; }
.table2-table .table2-groups th { position: static; background: #343f52; text-align: center; font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; }
.table2-table tbody th { top: auto; z-index: 1; color: #f1f5f9; font-weight: 500; }
.table2-table .table2-team { position: sticky; left: 0; text-align: left; background: #252d3a; min-width: 190px; max-width: 260px; box-shadow: 2px 0 0 #525b6b; }
.table2-table thead .table2-team { z-index: 4; }
.table2-table .team-cell { white-space: normal; }
.table2-table .table2-team-name { display: block; }
.table2-table small { display: block; color: #b7c2d3; font-size: .7rem; font-weight: normal; }
.table2-table tbody tr:nth-child(even) td { background-color: #ffffff06; }
.table2-table tbody tr:hover td, .table2-table tbody tr:hover th { background-color: #38465b; }
.table2-table .table2-points { font-weight: 800; color: #f8fafc; }
.table2-table .table2-projection { font-weight: 700; color: #c7d2fe; }
.table2-table .table2-reached { color: #8ce8bb; }
.table2-table .table2-unreachable { color: #ffb4b4; font-size: .8rem; }
.table2-table :is(th, td):is(:nth-child(8), :nth-child(10), :nth-child(12), :nth-child(14)) { border-left: 1px solid #647084; }
.table2-table .table2-empty { padding: 36px 16px; text-align: center; color: #c5cbd5; }
.table2-guide { margin-top: 16px; }
.table2-guide summary { cursor: pointer; color: #c7d2fe; }
@media (max-width: 600px) {
    .table2-table th, .table2-table td { padding: 10px 8px; }
    .table2-table .table2-team { min-width: 145px; max-width: 175px; }
    .table2-table .team-crest { width: 20px; height: 20px; margin-right: 6px; }
}
.table2-table .row-zone > :first-child { border-left: 4px solid var(--default-zone-color, transparent); }
.table2-table .row-zone > :last-child { border-right: 4px solid var(--season-zone-color, transparent); }
</style>
