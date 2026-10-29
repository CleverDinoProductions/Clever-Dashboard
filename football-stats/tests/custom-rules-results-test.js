const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');

const source = fs.readFileSync(__dirname + '/../includes/table-view.php', 'utf8');
const start = source.indexOf('                        function matchesResultRule(');
const end = source.indexOf("                        Array.prototype.forEach.call(panel.querySelectorAll('[data-team-rule]')", start);
assert.ok(start !== -1 && end > start);
const context = {};
vm.createContext(context);
vm.runInContext(source.slice(start, end), context);

// Exercise both team perspectives, including a scoreless draw and a scoring draw.
for (const [home, away] of [[2, 0], [0, 2], [0, 0], [1, 1], [2, 1]]) {
  for (const side of ['home', 'away']) {
    const goalsFor = side === 'home' ? home : away;
    const goalsAgainst = side === 'home' ? away : home;
    const result = goalsFor > goalsAgainst ? 'win' : goalsFor === goalsAgainst ? 'draw' : 'loss';
    const box = {dataset: {resultSide: side, result, goalsFor: String(goalsFor), goalsAgainst: String(goalsAgainst)}};
    const expected = {
      win: goalsFor > goalsAgainst,
      'win,draw': goalsFor >= goalsAgainst,
      draw: goalsFor === goalsAgainst,
      'win,loss': goalsFor !== goalsAgainst,
      'draw,loss': goalsFor <= goalsAgainst,
      loss: goalsFor < goalsAgainst,
      shutout: goalsAgainst === 0,
      scoreless: goalsFor === 0,
      team: true,
      all: true,
    };
    for (const [rule, matches] of Object.entries(expected)) {
      assert.equal(context.matchesResultRule(box, rule), matches, `${home}-${away} ${side} ${rule}`);
      for (const venue of ['home', 'away']) {
        assert.equal(context.matchesResultRule(box, venue + '_' + rule), venue === side && matches);
      }
    }
    assert.equal(context.matchesResultRule(box, 'home'), side === 'home');
    assert.equal(context.matchesResultRule(box, 'away'), side === 'away');
  }
}
assert.equal(context.matchesResultRule({dataset: {}}, 'shutout'), false);
assert.equal(context.matchesResultRule({dataset: {}}, 'scoreless'), false);
console.log('Custom rules result behavior tests passed.');
