

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// "/" focuses the search box unless the user is already typing somewhere.
document.addEventListener('keydown', (e) => {
    if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
    const t = e.target;
    if (t.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(t.tagName)) return;
    const box = document.querySelector('[data-search]');
    if (box) { e.preventDefault(); box.focus(); box.select(); }
});
