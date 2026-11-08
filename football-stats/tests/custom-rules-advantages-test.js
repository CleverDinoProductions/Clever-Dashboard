const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(__dirname + '/../includes/table-view.php', 'utf8');
const marker = "panel.querySelector('[data-match-apply]').addEventListener('click', function () {";
const start = source.indexOf(marker) + marker.length;
const end = source.indexOf('\n                        });', start);
function apply(values, deductions = []) {
    let destination;
    const context = {
        URL, window: { location: { href: 'http://localhost/?calc_mode=custom_matches&custom_points_advantages=old', assign: url => { destination = new URL(url); } } },
        scoringInputs: [], boxes: [], outcomeSelects: [], deductionInputs: deductions,
        advantageInputs: values.map(([team, value]) => ({ dataset: { pointsAdvantage: team }, value })),
    };
    vm.runInNewContext('(function () {' + source.slice(start, end) + '})()', context);
    return destination;
}
assert.deepEqual(JSON.parse(apply([['A & B', 5], ['C', 0]]).searchParams.get('custom_points_advantages')), {'A & B': 5});
assert.equal(apply([['A', 0]]).searchParams.has('custom_points_advantages'), false);
assert.deepEqual(JSON.parse(apply([['A', -2], ['B', 999], ['C', 3.9], ['D', 'bad']]).searchParams.get('custom_points_advantages')), {B: 100, C: 3});
const combined = apply([['A', 5]], [{dataset: {pointsDeduction: 'A'}, value: 2}]);
assert.deepEqual(JSON.parse(combined.searchParams.get('custom_points_advantages')), {A: 5});
assert.deepEqual(JSON.parse(combined.searchParams.get('custom_points_deductions')), {A: 2});
const resetMarker = "panel.querySelector('[data-match-reset]').addEventListener('click', function () {";
const resetStart = source.indexOf(resetMarker) + resetMarker.length;
const resetEnd = source.indexOf('\n                        });', resetStart);
let resetUrl;
const input = {value: 5};
vm.runInNewContext(source.slice(resetStart, resetEnd), {
    boxes: [], outcomeSelects: [], deductionInputs: [], scoringInputs: [], advantageInputs: [input], URL,
    window: {location: {href: combined.toString(), assign: url => { resetUrl = new URL(url); }}}
});
assert.equal(input.value, 0);
assert.equal(resetUrl.searchParams.has('custom_points_advantages'), false);
console.log('Custom rules points advantage behavior tests passed.');
