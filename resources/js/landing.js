(() => {
    const toggle = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('#navigation');
    const closeMenu = () => { toggle.setAttribute('aria-expanded', 'false'); toggle.setAttribute('aria-label', 'Buka navigasi'); navigation.classList.remove('is-open'); };
    toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Tutup navigasi' : 'Buka navigasi');
        navigation.classList.toggle('is-open', open);
    });
    navigation.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeMenu(); });
    document.addEventListener('click', event => { if (!event.target.closest('.site-header')) closeMenu(); });
    document.querySelector('#show-releases')?.addEventListener('click', event => {
        const button = event.currentTarget;
        const expanded = button.getAttribute('aria-expanded') !== 'true';
        document.querySelectorAll('#release-grid .release-card').forEach((card, index) => { if (index >= 3) card.hidden = !expanded; });
        button.setAttribute('aria-expanded', String(expanded));
        button.innerHTML = expanded ? 'Tampilkan Lebih Sedikit ↑' : 'Lihat Semua Rilis <span aria-hidden="true">→</span>';
    });
    document.querySelectorAll('[data-dialog]').forEach(button => button.addEventListener('click', () => {
        const dialog = document.getElementById(button.dataset.dialog);
        if (dialog) dialog.showModal();
    }));
    document.querySelectorAll('.release-dialog').forEach(dialog => {
        dialog.querySelector('.dialog-close').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => {
            const rect = dialog.getBoundingClientRect();
            if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
        });
    });
    const observer = new IntersectionObserver(() => {
        const sections = [...document.querySelectorAll('main section[id]')];
        const current = sections.filter(section => section.getBoundingClientRect().top <= innerHeight * .35).pop() || sections[0];
        document.querySelectorAll('.nav-link').forEach(link => link.classList.toggle('active', link.hash === '#' + current.id));
    }, { rootMargin: '-15% 0px -65% 0px' });
    document.querySelectorAll('main section[id]').forEach(section => observer.observe(section));
})();
