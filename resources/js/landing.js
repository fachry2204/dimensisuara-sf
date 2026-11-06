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
    document.addEventListener('click', event => {
        if (!event.target.closest('.site-header')) closeMenu();
        const button = event.target.closest('[data-dialog]');
        if (button) {
            document.getElementById(button.dataset.dialog)?.showModal();
            return;
        }
        const dialog = event.target.closest('.release-dialog');
        if (!dialog) return;
        const rect = dialog.getBoundingClientRect();
        if (event.target.closest('.dialog-close') || (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom))) dialog.close();
    });
    const searchForm = document.querySelector('.catalogue-search');
    if (searchForm && window.fetch && window.AbortController) {
        const input = searchForm.querySelector('[name="q"]');
        const results = document.querySelector('#catalogue-results-region');
        const status = document.querySelector('#catalogue-search-status');
        let timer, controller, revision = 0, composing = false, failed = false;
        const invalidate = () => {
            clearTimeout(timer);
            controller?.abort();
            revision++;
        };
        const search = async () => {
            invalidate();
            const current = revision;
            controller = new AbortController();
            const url = new URL(searchForm.action);
            const query = input.value.trim();
            if (query) url.searchParams.set('q', query);
            results.setAttribute('aria-busy', 'true');
            status.textContent = 'Mencari rilisan…';
            failed = false;
            try {
                const response = await fetch(url, {signal: controller.signal, headers: {Accept: 'text/html'}});
                if (!response.ok) throw new Error('Search failed');
                const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                const updated = page.querySelector('#catalogue-results-region');
                if (!updated) throw new Error('Missing results');
                if (current !== revision) return;
                results.innerHTML = updated.innerHTML;
                document.querySelector('.catalogue-description').textContent = page.querySelector('.catalogue-description').textContent;
                searchForm.querySelector('.catalogue-reset').hidden = !query;
                history.replaceState(null, '', url.pathname + url.search);
                status.textContent = query ? (results.querySelector('.catalogue-results')?.textContent || 'Pencarian selesai.') : 'Semua rilisan ditampilkan.';
            } catch (error) {
                if (error.name === 'AbortError' || current !== revision) return;
                failed = true;
                status.textContent = 'Pencarian belum berhasil. Klik Cari Rilis untuk mencoba kembali.';
            } finally {
                if (current === revision) results.setAttribute('aria-busy', 'false');
            }
        };
        input.addEventListener('input', () => {
            invalidate();
            if (!composing) timer = setTimeout(search, 300);
        });
        input.addEventListener('compositionstart', () => { composing = true; invalidate(); });
        input.addEventListener('compositionend', () => { composing = false; timer = setTimeout(search, 300); });
        searchForm.addEventListener('submit', event => {
            if (failed) return;
            event.preventDefault();
            search();
        });
    }
    const observer = new IntersectionObserver(() => {
        const sections = [...document.querySelectorAll('main section[id]')];
        const current = sections.filter(section => section.getBoundingClientRect().top <= innerHeight * .35).pop() || sections[0];
        document.querySelectorAll('.nav-link').forEach(link => link.classList.toggle('active', link.hash === '#' + current.id));
    }, { rootMargin: '-15% 0px -65% 0px' });
    document.querySelectorAll('main section[id]').forEach(section => observer.observe(section));
})();
