<script>
(() => {
    const readTheme = () => {
        try { return localStorage.getItem('theme') === 'light' ? 'light' : 'dark'; }
        catch { return 'dark'; }
    };
    const applyTheme = theme => {
        const isLight = theme === 'light';
        document.documentElement.setAttribute('data-bs-theme', isLight ? 'light' : 'dark');
        const toggle = document.getElementById('mode-switcher');
        if (toggle) {
            const label = isLight ? 'Gunakan tema gelap' : 'Gunakan tema terang';
            toggle.setAttribute('aria-pressed', String(isLight));
            toggle.setAttribute('aria-label', label);
            toggle.title = label;
            const icon = toggle.querySelector('i');
            if (icon) icon.textContent = isLight ? 'light_mode' : 'dark_mode';
        }
        window.dispatchEvent(new CustomEvent('app:themechange', { detail: { theme: isLight ? 'light' : 'dark' } }));
    };
    applyTheme(readTheme());
    document.addEventListener('DOMContentLoaded', () => {
        applyTheme(readTheme());
        document.getElementById('mode-switcher')?.addEventListener('click', () => {
            const next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            try { localStorage.setItem('theme', next); } catch {}
            applyTheme(next);
        });
    });
    window.addEventListener('storage', event => {
        if (event.key === 'theme') applyTheme(readTheme());
    });
})();
</script>
