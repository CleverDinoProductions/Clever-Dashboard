(function () {
    'use strict';
    function calculate(points, remaining, wins, draws, losses, scoring) {
        const values = [wins, draws, losses];
        if (!values.every(value => Number.isInteger(value) && value >= 0) || wins + draws + losses > remaining) return null;
        const games = wins + draws + losses;
        return { games, left: remaining - games, added: wins * scoring.win + draws * scoring.draw + losses * scoring.loss,
            points: points + wins * scoring.win + draws * scoring.draw + losses * scoring.loss };
    }
    if (typeof module !== 'undefined') module.exports = calculate;
    if (typeof document === 'undefined') return;
    document.querySelectorAll('.tt-calculator').forEach(form => {
        const output = form.querySelector('[role="status"]');
        const update = () => {
            const results = ['wins', 'draws', 'losses'].map(name => form.elements[name].value === '' ? NaN : Number(form.elements[name].value));
            const remaining = Number(form.dataset.remaining);
            const value = calculate(Number(form.dataset.points), remaining, ...results, {
                win: Number(form.dataset.win), draw: Number(form.dataset.draw), loss: Number(form.dataset.loss)
            });
            if (!value) {
                output.textContent = `Enter whole, non-negative results totalling no more than ${remaining} games.`;
                return;
            }
            const played = Number(form.dataset.played) + value.games;
            output.textContent = `${value.games} games modelled · ${value.added} points added · ${value.points} total points · ${value.left} games left` +
                (played ? ` · ${(value.points / played).toFixed(2)} overall PPG` : '');
        };
        form.addEventListener('input', update);
        form.addEventListener('submit', event => event.preventDefault());
        form.addEventListener('reset', () => setTimeout(update, 0));
        update();
    });
}());
