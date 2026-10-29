(() => {
    const panel = document.querySelector('.own-table');
    if (!panel) return;
    const clubs = [...panel.querySelectorAll('[name="own_clubs[]"]')];
    const count = panel.querySelector('#own-selection-count');
    const update = () => { count.textContent = `${clubs.filter(input => input.checked).length} clubs selected`; };
    panel.querySelectorAll('[data-own-select]').forEach(button => button.addEventListener('click', () => {
        clubs.forEach(input => { input.checked = button.dataset.ownSelect === 'all'; });
        update();
    }));
    clubs.forEach(input => input.addEventListener('change', update));
})();
