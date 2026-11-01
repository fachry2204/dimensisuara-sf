(() => {
    const payload = document.getElementById('aa-chart-data');
    if (!payload) return;
    if (!window.ApexCharts) {
        document.querySelectorAll('.aa-chart').forEach(el => { el.textContent = 'Grafik belum dapat dimuat. Data tetap tersedia pada tabel analitik.'; });
        return;
    }
    const data = JSON.parse(payload.textContent);
    const metric = document.getElementById('aa-metric');
    const money = value => 'Rp ' + Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    const compact = value => Number(value).toLocaleString('id-ID', { notation: 'compact', maximumFractionDigits: 1 });
    const escape = value => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const palette = () => ({ text: getComputedStyle(document.body).getPropertyValue('--sf-muted').trim() || '#64748b', border: getComputedStyle(document.body).getPropertyValue('--sf-border').trim() || '#cbd5e1', dark: document.documentElement.dataset.bsTheme === 'dark' });
    const series = () => {
        const key = metric.value;
        const result = [{ name: 'Periode ini', data: data.series.map(row => row[key]) }];
        if (data.compare !== 'none') result.push({ name: data.compare === 'year' ? 'Tahun sebelumnya' : 'Periode sebelumnya', data: data.series.map(row => row['previous_' + key]) });
        return result;
    };
    const tone = palette();
    const trend = new ApexCharts(document.getElementById('aa-trend'), {
        chart: { type: 'area', height: 300, toolbar: { show: false }, foreColor: tone.text, fontFamily: 'inherit', animations: { enabled: false } },
        theme: { mode: tone.dark ? 'dark' : 'light' }, series: series(), colors: ['#8050ee','#f97316'],
        stroke: { width: [3,2], curve: 'straight', dashArray: [0,5] }, dataLabels: { enabled: false },
        fill: { type: 'gradient', gradient: { opacityFrom: .22, opacityTo: .01 } },
        grid: { borderColor: tone.border, strokeDashArray: 3 },
        xaxis: { categories: data.series.map(row => row.month), tickAmount: Math.min(11,Math.max(1,data.series.length-1)), labels: { rotate: -35, hideOverlappingLabels: true } },
        yaxis: { labels: { formatter: compact } },
        tooltip: { y: { formatter: value => metric.value === 'revenue' ? money(value) : Number(value).toLocaleString('id-ID') } },
        noData: { text: 'Belum ada data pada periode ini' }, legend: { position: 'bottom' }
    });
    trend.render();
    metric.addEventListener('change', () => trend.updateOptions({ series: series(), yaxis: { labels: { formatter: value => (metric.value === 'revenue' ? 'Rp ' : '') + compact(value) } } }));
    const positive = data.platforms.filter(row => row.revenue > 0);
    const slices = positive.slice(0,5);
    if (positive.length > 5) slices.push({ name: 'Lainnya', revenue: positive.slice(5).reduce((sum,row) => sum + row.revenue,0) });
    const donut = new ApexCharts(document.getElementById('aa-platform-chart'), {
        chart: { type: 'donut', height: 300, foreColor: tone.text, fontFamily: 'inherit', animations: { enabled: false } },
        theme: { mode: tone.dark ? 'dark' : 'light' }, series: slices.map(row => row.revenue), labels: slices.map(row => escape(row.name)),
        colors: ['#7041ed','#f97316','#14b8a6','#3b82f6','#a855f7','#94a3b8'], stroke: { width: 2 },
        legend: { position: 'bottom' }, dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '72%', labels: { show: true, value: { formatter: money }, total: { show: true, label: 'Kontribusi positif', fontSize: '11px', formatter: () => money(slices.reduce((sum,row) => sum+row.revenue,0)) } } } } },
        tooltip: { y: { formatter: money } }, noData: { text: 'Belum ada pendapatan positif' }
    });
    donut.render();
    new MutationObserver(() => {
        const tone = palette();
        const theme = { chart: { foreColor: tone.text }, theme: { mode: tone.dark ? 'dark' : 'light' } };
        trend.updateOptions({ ...theme, grid: { borderColor: tone.border } });
        donut.updateOptions(theme);
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
})();
