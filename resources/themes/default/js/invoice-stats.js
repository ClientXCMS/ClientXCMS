import Chart from 'chart.js/auto';

const canvas = document.getElementById('invoice-paid-chart');
if (canvas) {
    const locale = document.documentElement.lang || undefined;
    const currency = canvas.dataset.currency || 'EUR';
    const formatAmount = (value) => new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(value));

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: Array.from({ length: 12 }, (_, index) => new Intl.DateTimeFormat(locale, { month: 'short' }).format(new Date(2020, index, 1))),
            datasets: [{ label: canvas.dataset.label + ' (' + currency + ')', data: JSON.parse(canvas.dataset.values || '[]'), backgroundColor: 'rgb(37 99 235 / 0.65)' }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: (value) => formatAmount(value) },
                },
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (context) => context.dataset.label + ': ' + formatAmount(context.parsed.y),
                    },
                },
            },
        },
    });
}
