// Back Office : icônes Lucide et ouverture / fermeture de la sidebar sur mobile.
lucide.createIcons();
document.querySelector('[data-menu]')?.addEventListener('click', () => document.querySelector('[data-sidebar]')?.classList.toggle('-translate-x-full'));
