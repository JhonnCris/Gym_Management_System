(function () {
    const configJson = document.getElementById('adminReportsConfig');
    let config = {};

    if (configJson) {
        try {
            config = JSON.parse(configJson.textContent || '{}');
        } catch (error) {
            console.warn('Unable to parse admin report config:', error);
            config = {};
        }
    }

    const periodFilter = document.getElementById('reportPeriodFilter');
    const exportModal = document.getElementById('reportExportModal');
    const exportTitle = document.getElementById('reportExportTitle');
    const exportSubtitle = document.getElementById('reportExportSubtitle');
    const exportFormat = document.getElementById('reportFormatSelect');
    const exportFileName = document.getElementById('reportFileName');
    const confirmExport = document.getElementById('confirmReportExport');
    const cancelExport = document.getElementById('cancelReportExport');

    const periodMeta = {
        weekly: 'Weekly',
        monthly: 'Monthly',
        quarterly: 'Quarterly',
        yearly: 'Yearly',
    };

    let currentExportKey = null;

    function escapePdfText(value) {
        return String(value).replace(/\\/g, '\\\\').replace(/\(/g, '\\(').replace(/\)/g, '\\)');
    }

    function createBlobUrl(data, type) {
        const blob = new Blob([data], { type });
        const url = window.URL.createObjectURL(blob);
        return url;
    }

    function downloadFile(filename, data, type) {
        const url = createBlobUrl(data, type);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(() => window.URL.revokeObjectURL(url), 0);
    }

    function formatCurrency(value) {
        return `PHP ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function buildInsights(type) {
        if (type === 'growth') {
            const rows = config.growthRows || [];
            const best = rows.reduce((carry, row) => (Number(row.revenue || 0) > Number(carry.revenue || 0) ? row : carry), rows[0] || {});
            const totalMembers = rows.reduce((carry, row) => carry + Number(row.members || 0), 0);

            return [
                `Highest recorded revenue in the visible dataset: ${best.label || 'N/A'} at ${formatCurrency(best.revenue)}.`,
                `Total signups represented in this export window: ${totalMembers}.`,
                'Trend read: revenue and membership activity should be reviewed together when planning promotions, coach allocation, and retention offers.',
            ];
        }

        if (type === 'peak-hours') {
            const rows = config.peakHoursRows || [];
            const best = rows.reduce((carry, row) => (Number(row.visits || 0) > Number(carry.visits || 0) ? row : carry), rows[0] || {});

            return [
                `Peak operating window: ${best.label || 'N/A'} with ${Number(best.visits || 0)} visits.`,
                'Operational note: this timeslot should receive priority for front desk coverage, cleaning rotations, and equipment readiness.',
                'Member flow insight: compare this export with class schedules to identify whether traffic is driven by programs or open-gym demand.',
            ];
        }

        const rows = config.distributionRows || [];
        const total = rows.reduce((carry, row) => carry + Number(row.count || 0), 0) || 1;
        const best = rows.reduce((carry, row) => (Number(row.count || 0) > Number(carry.count || 0) ? row : carry), rows[0] || {});
        const share = ((Number(best.count || 0) / total) * 100).toFixed(1);

        return [
            `Largest active membership segment: ${best.label || 'N/A'} at ${share}% of tracked members.`,
            'Portfolio insight: plan-heavy concentration can increase renewal risk if pricing or benefits are not diversified.',
            'Recommended follow-up: compare this mix against attendance and payment success to refine plan positioning.',
        ];
    }

    function buildDataRows(type) {
        if (type === 'growth') {
            return (config.growthRows || []).map((row) => ({
                Month: row.label || 'N/A',
                Signups: Number(row.members || 0),
                Revenue: formatCurrency(row.revenue),
            }));
        }

        if (type === 'peak-hours') {
            return (config.peakHoursRows || []).map((row) => ({
                Hour: row.label || 'N/A',
                Visits: Number(row.visits || 0),
            }));
        }

        return (config.distributionRows || []).map((row) => ({
            Plan: row.label || 'N/A',
            Members: Number(row.count || 0),
            Share: `${((Number(row.count || 0) / ((config.distributionRows || []).reduce((carry, item) => carry + Number(item.count || 0), 0) || 1)) * 100).toFixed(1)}%`,
        }));
    }

    function buildReportLines(type) {
        const period = periodMeta[periodFilter?.value || 'monthly'] || 'Monthly';
        const titles = {
            growth: 'Membership & Revenue Growth',
            'peak-hours': 'Peak Hours Analysis',
            'membership-distribution': 'Membership Distribution',
        };
        const lines = [
            `${config.gymName || 'Gym System'} Export Report`,
            `${titles[type]} - ${period} View`,
            `Generated: ${config.generatedAt || new Date().toLocaleString()}`,
            '',
            'Executive Summary',
            ...buildInsights(type),
            '',
            'Detailed Data',
        ];

        const rows = buildDataRows(type);
        rows.forEach((row) => {
            const values = Object.values(row).join(' | ');
            lines.push(values);
        });

        lines.push('', 'Prepared By', '- System: WeDumbell reporting module', '- Review status: Auto-generated operational export');
        return lines;
    }

    function buildCsv(type) {
        const rows = buildDataRows(type);
        if (!rows.length) {
            return '';
        }

        const headers = Object.keys(rows[0]);
        const lines = [headers.join(',')];
        rows.forEach((row) => {
            lines.push(headers.map((header) => `"${String(row[header] || '').replace(/"/g, '""')}"`).join(','));
        });

        return lines.join('\r\n');
    }

    function buildSimplePdf(type) {
        const lines = buildReportLines(type).slice(0, 38);
        const contents = ['BT', '/F1 10 Tf', '50 760 Td'];
        lines.forEach((line, index) => {
            if (index > 0) {
                contents.push('T*');
            }
            contents.push(`(${escapePdfText(line)}) Tj`);
        });
        contents.push('ET');
        const stream = contents.join('\n');
        const streamLength = new TextEncoder().encode(stream).length;

        const objects = [
            `1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj`,
            `2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj`,
            `3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>
endobj`,
            `4 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj`,
            `5 0 obj
<< /Length ${streamLength} >>
stream
${stream}
endstream
endobj`,
        ];

        let pdf = '%PDF-1.3\n';
        const offsets = [pdf.length];
        objects.forEach((object) => {
            pdf += object + '\n';
            offsets.push(pdf.length);
        });

        const xrefStart = pdf.length;
        pdf += 'xref\n0 ' + (objects.length + 1) + '\n0000000000 65535 f \n';
        offsets.slice(0, -1).forEach((offset) => {
            pdf += `${String(offset).padStart(10, '0')} 00000 n \n`;
        });
        pdf += `trailer\n<< /Size ${objects.length + 1} /Root 1 0 R >>\nstartxref\n${xrefStart}\n%%EOF`;

        return pdf;
    }

    function showChartError(canvasId, message) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            return;
        }

        const container = canvas.closest('.chart-container');
        if (!container) {
            return;
        }

        canvas.style.display = 'none';

        const errorMessage = document.createElement('div');
        errorMessage.className = 'chart-error-message';
        errorMessage.textContent = message || 'Unable to load chart.';
        container.appendChild(errorMessage);
    }

    function createChart(canvasId, config) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            return null;
        }

        if (typeof Chart === 'undefined') {
            showChartError(canvasId, 'Chart.js is not loaded.');
            return null;
        }

        const context = canvas.getContext('2d');
        if (!context) {
            showChartError(canvasId, 'Unable to initialize chart canvas.');
            return null;
        }

        try {
            return new Chart(context, config);
        } catch (error) {
            console.error('Chart render failed for', canvasId, error);
            showChartError(canvasId, 'Chart failed to render.');
            return null;
        }
    }

    function initializeReportCharts() {
        if (typeof Chart === 'undefined') {
            ['membershipGrowthChart', 'revenueOverviewChart', 'peakHoursChart', 'membershipDistributionChart'].forEach((id) => {
                showChartError(id, 'Chart.js did not load.');
            });
            return;
        }

        const growthLabels = (config.growthRows || []).map((row) => row.label || 'N/A');
        const growthMembers = (config.growthRows || []).map((row) => Number(row.members || 0));
        const growthRevenue = (config.growthRows || []).map((row) => Number(row.revenue || 0));

        createChart('membershipGrowthChart', {
            type: 'line',
            data: {
                labels: growthLabels,
                datasets: [
                    {
                        label: 'Members',
                        data: growthMembers,
                        borderColor: '#2d66ff',
                        backgroundColor: 'rgba(45, 102, 255, 0.16)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#2d66ff',
                        borderWidth: 2,
                    },
                    {
                        label: 'Revenue',
                        data: growthRevenue,
                        borderColor: '#14b8a6',
                        backgroundColor: 'rgba(20, 184, 166, 0.14)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#14b8a6',
                        borderWidth: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#4a4a4a',
                            boxWidth: 12,
                            boxHeight: 12,
                        },
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: '#4f5b75',
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(229, 232, 240, 0.9)',
                        },
                        ticks: {
                            color: '#4f5b75',
                        },
                    },
                },
            },
        });

        createChart('revenueOverviewChart', {
            type: 'bar',
            data: {
                labels: growthLabels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: growthRevenue,
                        backgroundColor: '#14b8a6',
                        borderRadius: 12,
                        maxBarThickness: 28,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `₱${Number(context.parsed.y || 0).toLocaleString()}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: '#4f5b75',
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(229, 232, 240, 0.9)',
                        },
                        ticks: {
                            color: '#4f5b75',
                            callback: (value) => `₱${value.toLocaleString()}`,
                        },
                    },
                },
            },
        });

        const peakLabels = (config.peakHoursRows || []).map((row) => row.label || 'N/A');
        const peakVisits = (config.peakHoursRows || []).map((row) => Number(row.visits || 0));

        createChart('peakHoursChart', {
            type: 'bar',
            data: {
                labels: peakLabels,
                datasets: [
                    {
                        label: 'Visits',
                        data: peakVisits,
                        backgroundColor: '#2d66ff',
                        borderRadius: 12,
                        maxBarThickness: 28,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.parsed.y || 0} visits`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: '#4f5b75',
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(229, 232, 240, 0.9)',
                        },
                        ticks: {
                            color: '#4f5b75',
                        },
                    },
                },
            },
        });

        const distributionLabels = (config.distributionRows || []).map((row) => row.label || 'N/A');
        const distributionCounts = (config.distributionRows || []).map((row) => Number(row.count || 0));

        createChart('membershipDistributionChart', {
            type: 'doughnut',
            data: {
                labels: distributionLabels,
                datasets: [
                    {
                        data: distributionCounts,
                        backgroundColor: ['#2d66ff', '#14b8a6', '#fb923c', '#f97316', '#6366f1'],
                        hoverOffset: 8,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#4f5b75',
                        },
                    },
                },
            },
        });
    }

    function openExportModal(type) {
        currentExportKey = type;
        const periodLabel = periodMeta[periodFilter?.value || 'monthly'] || 'Monthly';
        exportTitle.textContent = `Export ${type.replaceAll('-', ' ')} Report`;
        exportSubtitle.textContent = `Selected period: ${periodLabel}. Choose CSV or PDF and then download.`;
        exportFileName.value = `${type}-${(periodFilter?.value || 'monthly')}-report`;
        exportModal.classList.add('show');
        exportModal.setAttribute('aria-hidden', 'false');
    }

    function closeExportModal() {
        currentExportKey = null;
        exportModal.classList.remove('show');
        exportModal.setAttribute('aria-hidden', 'true');
    }

    function exportCurrentReport() {
        if (!currentExportKey) {
            return;
        }

        const format = exportFormat.value || 'csv';
        const filename = `${exportFileName.value || currentExportKey}.${format}`;

        if (format === 'csv') {
            const csv = buildCsv(currentExportKey);
            downloadFile(filename, csv, 'text/csv;charset=utf-8;');
        } else if (format === 'pdf') {
            const pdf = buildSimplePdf(currentExportKey);
            downloadFile(filename, pdf, 'application/pdf');
        }

        if (window.AdminNotifications) {
            window.AdminNotifications.add({
                title: 'Report ready',
                message: `${exportFormat.value.toUpperCase()} export for ${currentExportKey.replaceAll('-', ' ')} is downloading.`,
                type: 'success',
            });
        }

        closeExportModal();
    }

    document.querySelectorAll('[data-report-export]').forEach((button) => {
        button.addEventListener('click', () => {
            const exportKey = button.dataset.reportExport;
            if (!exportKey) {
                return;
            }

            openExportModal(exportKey);
        });
    });

    confirmExport.addEventListener('click', exportCurrentReport);
    cancelExport.addEventListener('click', closeExportModal);
    exportModal.addEventListener('click', (event) => {
        if (event.target === exportModal) {
            closeExportModal();
        }
    });

    window.addEventListener('DOMContentLoaded', initializeReportCharts);

    if (document.readyState === 'interactive' || document.readyState === 'complete') {
        initializeReportCharts();
    }
})();
