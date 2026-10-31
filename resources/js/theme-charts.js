(() => {
    const updateCharts = () => {
        if (!window.echarts) return;
        const palette = getComputedStyle(document.body);
        const color = palette.getPropertyValue('--sf-text').trim();
        const muted = palette.getPropertyValue('--sf-muted').trim();
        const border = palette.getPropertyValue('--sf-border').trim();
        const surface = palette.getPropertyValue('--sf-surface').trim();
        document.querySelectorAll('[_echarts_instance_]').forEach(node => {
            const chart = window.echarts.getInstanceByDom(node);
            if (!chart || chart.isDisposed()) return;
            const options = chart.getOption();
            const themed = {
                textStyle: { color },
                tooltip: { backgroundColor: surface, borderColor: border, textStyle: { color } },
            };
            if (options.legend?.length) themed.legend = options.legend.map(() => ({ textStyle: { color: muted } }));
            for (const axis of ['xAxis', 'yAxis']) {
                if (options[axis]?.length) themed[axis] = options[axis].map(() => ({
                    axisLabel: { color: muted },
                    axisLine: { lineStyle: { color: border } },
                    splitLine: { lineStyle: { color: border } },
                }));
            }
            chart.setOption(themed);
        });
    };
    document.addEventListener('DOMContentLoaded', updateCharts);
    window.addEventListener('app:themechange', updateCharts);
})();
