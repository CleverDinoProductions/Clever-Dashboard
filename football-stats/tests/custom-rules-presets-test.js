const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(__dirname + '/../includes/table-view.php', 'utf8');
const start = source.indexOf('                        var bigSixTeams');
const end = source.indexOf('                        function addTeamSearch', start);
const code = source.slice(start, end).replace(/<\?php[\s\S]*?\?>/g, 'fixtureStandings');
const groups = {big_three: ['Manchester United', 'Arsenal', 'Liverpool'], big_six: ['Manchester United', 'Arsenal', 'Liverpool', 'Chelsea', 'Tottenham Hotspur', 'Manchester City'], big_nine: ['Everton', 'Newcastle United', 'Aston Villa'], big_twelve: ['Blackburn Rovers'], typical_top_half: ['Brighton & Hove Albion']};
function run(standings) {
  const context = {configuredTeamGroups: groups, fixtureStandings: standings};
  vm.createContext(context); vm.runInContext(code, context); return context;
}
let c = run([{team_name: 'Chelsea', position: 4, played: 10}, {team_name: 'Arsenal', position: 1, played: 10}, {team_name: 'Liverpool', position: 2, played: 10}, {team_name: 'Everton', position: 3, played: 10}]);
assert.equal(c.matchesTeamPreset('big-three', 'Man Utd'), true);
assert.equal(c.matchesTeamPreset('big-six', 'Spurs'), true);
assert.equal(c.matchesTeamPreset('big-nine', 'Newcastle FC'), true);
assert.equal(c.matchesTeamPreset('outside-big-nine', 'Newcastle'), false);
assert.equal(c.matchesTeamPreset('typical-top-half', 'Brighton'), true);
assert.equal(c.matchesTeamPreset('current-top-half', 'Arsenal FC'), true);
assert.equal(c.matchesTeamPreset('current-top-half', 'Chelsea'), false);
assert.equal(c.matchesTeamPreset('current-bottom-half', 'Chelsea'), true);
assert.equal(c.matchesTeamPreset('none', 'Arsenal'), false);
assert.equal(c.matchesTeamPreset('all', 'Arsenal'), true);
c = run([{team_name: 'Arsenal', position: 1, played: 0}]);
assert.equal(c.hasStandings, false);
assert.equal(c.matchesTeamPreset('current-top-half', 'Arsenal'), false);
console.log('Custom rules preset behavior tests passed.');
