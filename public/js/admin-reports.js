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
    const currencySymbol = '\u20b1';

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

    function formatReportCurrency(value, fractionDigits = 0) {
        return `${currencySymbol}${Number(value || 0).toLocaleString(undefined, {
            minimumFractionDigits: fractionDigits,
            maximumFractionDigits: fractionDigits,
        })}`;
    }

    function formatPercentChange(currentValue, previousValue) {
        const current = Number(currentValue || 0);
        const previous = Number(previousValue || 0);

        if (!previous) {
            return '0.0%';
        }

        const change = ((current - previous) / previous) * 100;
        const sign = change > 0 ? '+' : '';
        return `${sign}${change.toFixed(1)}%`;
    }

    function getGrowthSummary() {
        const rows = config.growthRows || [];
        const current = rows[rows.length - 1] || { members: 0, revenue: 0, label: 'N/A' };
        const previous = rows.length >= 2 ? rows[rows.length - 2] : { members: 0, revenue: 0, label: 'N/A' };
        const ytdMembers = rows.reduce((carry, row) => carry + Number(row.members || 0), 0);
        const ytdRevenue = rows.reduce((carry, row) => carry + Number(row.revenue || 0), 0);
        const currentAverage = current.members ? Number(current.revenue || 0) / Number(current.members || 0) : 0;
        const previousAverage = previous.members ? Number(previous.revenue || 0) / Number(previous.members || 0) : 0;
        const ytdAverage = ytdMembers ? ytdRevenue / ytdMembers : 0;

        return {
            rows,
            current,
            previous,
            ytdMembers,
            ytdRevenue,
            membersChange: formatPercentChange(current.members, previous.members),
            revenueChange: formatPercentChange(current.revenue, previous.revenue),
            currentAverage,
            previousAverage,
            ytdAverage,
            averageChange: previousAverage ? formatPercentChange(currentAverage, previousAverage) : '0.0%',
        };
    }

    function buildPlainFullReport() {
        const period = periodMeta[periodFilter?.value || 'monthly'] || 'Monthly';
        const gymName = config.gymName || 'WeDumbell Gym Management System';
        const exportedBy = config.exportedBy || 'Administrator';
        const generatedAt = config.generatedAt || new Date().toLocaleString();
        const growth = getGrowthSummary();

        const lines = [
            `${period.toUpperCase()} GYM MANAGEMENT REPORT`,
            '',
            `Facility Name: ${gymName}`,
            `Reporting Period: ${period}`,
            `Date Submitted: ${generatedAt}`,
            `Prepared By: ${exportedBy}`,
            '',
            '1. Executive Summary',
            '',
            `A comprehensive overview of the gym's performance across all key metrics for the ${period.toLowerCase()} period. This report covers membership growth, revenue trends, operational efficiency, and facility management insights.`,
            '',
            `Key Highlights This ${period}:`,
            '- Membership and revenue data shows steady performance trends',
            '- Peak hours analysis indicates optimal operational windows',
            '- Membership distribution reflects current market positioning',
            '- Attendance tracking demonstrates facility utilization patterns',
            '',
            '2. Membership Metrics',
            '',
            'Total Active Members',
            `Current ${period}: ${Number(growth.current.members || 0).toLocaleString()}`,
            `Previous ${period}: ${Number(growth.previous.members || 0).toLocaleString()}`,
            `Change (%): ${growth.membersChange}`,
            `Year-to-Date (YTD): ${growth.ytdMembers.toLocaleString()}`,
            '',
            'New Sign-ups',
            `Current ${period}: ${Number(growth.current.members || 0).toLocaleString()}`,
            `Previous ${period}: ${Number(growth.previous.members || 0).toLocaleString()}`,
            `Change (%): ${growth.membersChange}`,
            `Year-to-Date (YTD): ${growth.ytdMembers.toLocaleString()}`,
            '',
            'Total Revenue',
            `Current ${period}: ${formatReportCurrency(growth.current.revenue, 0)}`,
            `Previous ${period}: ${formatReportCurrency(growth.previous.revenue, 0)}`,
            `Change (%): ${growth.revenueChange}`,
            `Year-to-Date (YTD): ${formatReportCurrency(growth.ytdRevenue, 0)}`,
            '',
            'Average Revenue per Member',
            `Current ${period}: ${formatReportCurrency(growth.currentAverage, 2)}`,
            `Previous ${period}: ${formatReportCurrency(growth.previousAverage, 2)}`,
            `Change (%): ${growth.averageChange}`,
            `Year-to-Date (YTD): ${formatReportCurrency(growth.ytdAverage, 2)}`,
            '',
            'Membership Notes and Trends:',
            '- Growth Analysis: Recent trends show positive momentum in membership acquisition',
            '- Revenue Performance: Revenue growth aligns with membership expansion',
            '- Market Position: Current data reflects competitive positioning in the local fitness market',
            '',
            '3. Operational and Facility Management',
            '',
            'Equipment Utilization',
            'Status: Good',
            'Notes/Action Taken: All equipment operating within normal parameters',
            '',
            'Facility Cleanliness',
            'Status: Good',
            'Notes/Action Taken: Regular maintenance and cleaning schedules maintained',
            '',
            'Member Satisfaction',
            'Status: Good',
            'Notes/Action Taken: Positive feedback on facility conditions and services',
            '',
            'Staff Performance',
            'Status: Good',
            'Notes/Action Taken: Consistent service delivery and member support',
            '',
            'Safety Compliance',
            'Status: Good',
            'Notes/Action Taken: All safety protocols and procedures followed',
            '',
            '4. Goals for Next Month',
            '',
            '1. Membership Growth: Continue implementing effective marketing strategies to increase member acquisition',
            '2. Revenue Optimization: Focus on maximizing revenue per member through premium services and add-ons',
            '3. Operational Excellence: Maintain high standards of facility management and equipment maintenance',
            '4. Member Experience: Enhance member satisfaction through improved services and amenities',
            '5. Market Expansion: Explore opportunities for business growth and market penetration',
            '',
            `Report generated by ${gymName} Management System on ${generatedAt}`,
            `Prepared by: ${exportedBy}`,
        ];

        return lines.join('\r\n');
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

        if (type === 'attendance') {
            const rows = config.attendanceRows || [];
            const activeInGym = rows.filter((row) => row.status === 'In Gym').length;

            return [
                `Active members currently in the gym: ${activeInGym}.`,
                `Recent attendance rows included in this export: ${rows.length}.`,
                'Attendance detail is especially useful for staffing, capacity planning, and daily reconciliation.',
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

        if (type === 'attendance') {
            return (config.attendanceRows || []).map((row) => ({
                Member: row.member_name || 'N/A',
                'Class / Session': row.class_name || 'N/A',
                'Check-in': row.check_in_time || 'N/A',
                'Check-out': row.check_out_time || 'N/A',
                Status: row.status || 'N/A',
            }));
        }

        return (config.distributionRows || []).map((row) => ({
            Plan: row.label || 'N/A',
            Members: Number(row.count || 0),
            Share: `${((Number(row.count || 0) / ((config.distributionRows || []).reduce((carry, item) => carry + Number(item.count || 0), 0) || 1)) * 100).toFixed(1)}%`,
        }));
    }

    function getReportHeading(type) {
        const headings = {
            'all-reports': 'GYM MANAGEMENT REPORT',
            growth: 'MEMBERSHIP AND REVENUE GROWTH REPORT',
            'peak-hours': 'PEAK HOURS ANALYSIS REPORT',
            'membership-distribution': 'MEMBERSHIP DISTRIBUTION REPORT',
            attendance: 'ATTENDANCE REPORT',
        };

        return headings[type] || 'GYM MANAGEMENT REPORT';
    }

    function buildReportHeader(type) {
        const period = periodMeta[periodFilter?.value || 'monthly'] || 'Monthly';
        const gymName = config.gymName || 'WeDumbell Gym Management System';
        const exportedBy = config.exportedBy || 'Administrator';
        const generatedAt = config.generatedAt || new Date().toLocaleString();

        return {
            period,
            gymName,
            exportedBy,
            generatedAt,
            lines: [
                `${period.toUpperCase()} ${getReportHeading(type)}`,
                '',
                `Facility Name: ${gymName}`,
                `Reporting Period: ${period}`,
                `Date Submitted: ${generatedAt}`,
                `Prepared By: ${exportedBy}`,
                '',
            ],
        };
    }

    function buildGrowthReport() {
        const { period, gymName, exportedBy, generatedAt, lines } = buildReportHeader('growth');
        const growth = getGrowthSummary();

        lines.push(
            '1. Executive Summary',
            '',
            `A focused overview of membership and revenue performance for the ${period.toLowerCase()} period.`,
            '',
            `Key Highlights This ${period}:`,
            `- Current sign-ups recorded: ${Number(growth.current.members || 0).toLocaleString()}`,
            `- Current revenue recorded: ${formatReportCurrency(growth.current.revenue, 0)}`,
            `- Membership change from previous period: ${growth.membersChange}`,
            `- Revenue change from previous period: ${growth.revenueChange}`,
            '',
            '2. Membership Metrics',
            '',
            'Total Active Members',
            `Current ${period}: ${Number(growth.current.members || 0).toLocaleString()}`,
            `Previous ${period}: ${Number(growth.previous.members || 0).toLocaleString()}`,
            `Change (%): ${growth.membersChange}`,
            `Year-to-Date (YTD): ${growth.ytdMembers.toLocaleString()}`,
            '',
            'New Sign-ups',
            `Current ${period}: ${Number(growth.current.members || 0).toLocaleString()}`,
            `Previous ${period}: ${Number(growth.previous.members || 0).toLocaleString()}`,
            `Change (%): ${growth.membersChange}`,
            `Year-to-Date (YTD): ${growth.ytdMembers.toLocaleString()}`,
            '',
            'Total Revenue',
            `Current ${period}: ${formatReportCurrency(growth.current.revenue, 0)}`,
            `Previous ${period}: ${formatReportCurrency(growth.previous.revenue, 0)}`,
            `Change (%): ${growth.revenueChange}`,
            `Year-to-Date (YTD): ${formatReportCurrency(growth.ytdRevenue, 0)}`,
            '',
            'Average Revenue per Member',
            `Current ${period}: ${formatReportCurrency(growth.currentAverage, 2)}`,
            `Previous ${period}: ${formatReportCurrency(growth.previousAverage, 2)}`,
            `Change (%): ${growth.averageChange}`,
            `Year-to-Date (YTD): ${formatReportCurrency(growth.ytdAverage, 2)}`,
            '',
            '3. Membership Notes and Trends',
            '',
            '- Growth Analysis: Recent trends show positive momentum in membership acquisition',
            '- Revenue Performance: Revenue growth aligns with membership expansion',
            '- Market Position: Current data reflects competitive positioning in the local fitness market',
            ''
        );

        if (growth.rows.length) {
            lines.push('4. Detailed Period Data', '');
            growth.rows.forEach((row) => {
                lines.push(
                    `${row.label || 'N/A'}`,
                    `Sign-ups: ${Number(row.members || 0).toLocaleString()}`,
                    `Revenue: ${formatReportCurrency(row.revenue, 0)}`,
                    ''
                );
            });
        }

        lines.push(
            `Report generated by ${gymName} Management System on ${generatedAt}`,
            `Prepared by: ${exportedBy}`
        );

        return lines.join('\r\n');
    }

    function buildPeakHoursReport() {
        const { period, gymName, exportedBy, generatedAt, lines } = buildReportHeader('peak-hours');
        const rows = config.peakHoursRows || [];
        const bestSlot = rows.reduce((carry, row) => (Number(row.visits || 0) > Number(carry.visits || 0) ? row : carry), rows[0] || {});
        const totalVisits = rows.reduce((carry, row) => carry + Number(row.visits || 0), 0);

        lines.push(
            '1. Executive Summary',
            '',
            `A focused overview of facility traffic and member visit patterns for the ${period.toLowerCase()} period.`,
            '',
            `Key Highlights This ${period}:`,
            `- Peak operating window: ${bestSlot.label || 'N/A'}`,
            `- Visits during peak window: ${Number(bestSlot.visits || 0).toLocaleString()}`,
            `- Total tracked visits: ${totalVisits.toLocaleString()}`,
            '- Traffic data supports staffing and facility planning',
            '',
            '2. Peak Hours Summary',
            '',
            `Top Peak Slot: ${bestSlot.label || 'N/A'}`,
            `Peak Slot Visits: ${Number(bestSlot.visits || 0).toLocaleString()}`,
            `Total Visits Recorded: ${totalVisits.toLocaleString()}`,
            '',
            '3. Operational Notes',
            '',
            '- Peak traffic windows should receive priority staffing coverage',
            '- Cleaning rotations should align with high-usage periods',
            '- Equipment readiness should be checked before the busiest slots',
            ''
        );

        if (rows.length) {
            lines.push('4. Detailed Peak Hour Data', '');
            rows.forEach((row) => {
                lines.push(
                    `Hour: ${row.label || 'N/A'}`,
                    `Visits: ${Number(row.visits || 0).toLocaleString()}`,
                    ''
                );
            });
        }

        lines.push(
            `Report generated by ${gymName} Management System on ${generatedAt}`,
            `Prepared by: ${exportedBy}`
        );

        return lines.join('\r\n');
    }

    function buildMembershipDistributionReport() {
        const { period, gymName, exportedBy, generatedAt, lines } = buildReportHeader('membership-distribution');
        const rows = config.distributionRows || [];
        const totalMembers = rows.reduce((carry, row) => carry + Number(row.count || 0), 0);
        const bestPlan = rows.reduce((carry, row) => (Number(row.count || 0) > Number(carry.count || 0) ? row : carry), rows[0] || {});
        const topShare = totalMembers ? ((Number(bestPlan.count || 0) / totalMembers) * 100).toFixed(1) : '0.0';

        lines.push(
            '1. Executive Summary',
            '',
            `A focused overview of membership plan distribution for the ${period.toLowerCase()} period.`,
            '',
            `Key Highlights This ${period}:`,
            `- Total active members tracked: ${totalMembers.toLocaleString()}`,
            `- Leading plan: ${bestPlan.label || 'N/A'}`,
            `- Leading plan share: ${topShare}%`,
            '- Membership mix reflects current product positioning',
            '',
            '2. Distribution Summary',
            '',
            `Total Active Members: ${totalMembers.toLocaleString()}`,
            `Top Membership Plan: ${bestPlan.label || 'N/A'}`,
            `Top Plan Share: ${topShare}%`,
            '',
            '3. Portfolio Notes',
            '',
            '- Membership mix should be reviewed alongside renewals and attendance',
            '- Heavy concentration in one plan may increase renewal sensitivity',
            '- Balanced plan adoption can support more stable revenue streams',
            ''
        );

        if (rows.length) {
            lines.push('4. Detailed Membership Plan Data', '');
            rows.forEach((row) => {
                const share = totalMembers ? ((Number(row.count || 0) / totalMembers) * 100).toFixed(1) : '0.0';
                lines.push(
                    `Plan: ${row.label || 'N/A'}`,
                    `Members: ${Number(row.count || 0).toLocaleString()}`,
                    `Share: ${share}%`,
                    ''
                );
            });
        }

        lines.push(
            `Report generated by ${gymName} Management System on ${generatedAt}`,
            `Prepared by: ${exportedBy}`
        );

        return lines.join('\r\n');
    }

    function buildAttendanceReport() {
        const { period, gymName, exportedBy, generatedAt, lines } = buildReportHeader('attendance');
        const rows = config.attendanceRows || [];
        const activeInGym = rows.filter((row) => row.status === 'In Gym').length;

        lines.push(
            '1. Executive Summary',
            '',
            `A focused overview of attendance activity for the ${period.toLowerCase()} period.`,
            '',
            `Key Highlights This ${period}:`,
            `- Attendance rows tracked: ${rows.length.toLocaleString()}`,
            `- Members currently in the gym: ${activeInGym.toLocaleString()}`,
            '- Attendance data supports daily reconciliation and staffing decisions',
            '',
            '2. Attendance Summary',
            '',
            `Total Attendance Rows: ${rows.length.toLocaleString()}`,
            `Current In-Gym Members: ${activeInGym.toLocaleString()}`,
            ''
        );

        if (rows.length) {
            lines.push('3. Detailed Attendance Data', '');
            rows.forEach((row) => {
                lines.push(
                    `Member: ${row.member_name || 'N/A'}`,
                    `Class or Session: ${row.class_name || 'N/A'}`,
                    `Check-in: ${row.check_in_time || 'N/A'}`,
                    `Check-out: ${row.check_out_time || 'N/A'}`,
                    `Status: ${row.status || 'N/A'}`,
                    ''
                );
            });
        }

        lines.push(
            `Report generated by ${gymName} Management System on ${generatedAt}`,
            `Prepared by: ${exportedBy}`
        );

        return lines.join('\r\n');
    }

    function buildPlainReport(type) {
        if (type === 'all-reports') {
            return buildPlainFullReport();
        }

        if (type === 'growth') {
            return buildGrowthReport();
        }

        if (type === 'peak-hours') {
            return buildPeakHoursReport();
        }

        if (type === 'membership-distribution') {
            return buildMembershipDistributionReport();
        }

        if (type === 'attendance') {
            return buildAttendanceReport();
        }

        return buildPlainFullReport();
    }

    function buildMarkdownReport(type) {
        return buildPlainReport(type);

        const period = periodMeta[periodFilter?.value || 'monthly'] || 'Monthly';
        const gymName = config.gymName || 'WeDumbell Gym Management System';
        const exportedBy = config.exportedBy || 'Administrator';
        const generatedAt = config.generatedAt || new Date().toLocaleString();

        let markdown = `# MONTHLY GYM MANAGEMENT REPORT

| **Facility Name:** | ${gymName} |
| :--- | :--- |
| **Reporting Period:** | ${period} |
| **Date Submitted:** | ${generatedAt} |
| **Prepared By:** | ${exportedBy} |

---

## 1. Executive Summary

`;

        // Add executive summary based on type
        if (type === 'all-reports') {
            markdown += `A comprehensive overview of the gym's performance across all key metrics for the ${period.toLowerCase()} period. This report covers membership growth, revenue trends, operational efficiency, and facility management insights.

**Key Highlights This ${period}:**
* Membership and revenue data shows steady performance trends
* Peak hours analysis indicates optimal operational windows
* Membership distribution reflects current market positioning
* Attendance tracking demonstrates facility utilization patterns

`;
        } else if (type === 'growth') {
            const rows = config.growthRows || [];
            if (rows.length >= 2) {
                const current = rows[rows.length - 1];
                const previous = rows[rows.length - 2];
                const membersChange = previous.members ? ((current.members - previous.members) / previous.members * 100).toFixed(1) : '0.0';
                const revenueChange = previous.revenue ? ((current.revenue - previous.revenue) / previous.revenue * 100).toFixed(1) : '0.0';

                markdown += `Membership and revenue performance analysis for the ${period.toLowerCase()} period, highlighting growth trends and financial performance.

**Key Highlights This ${period}:**
* Current month sign-ups: ${Number(current.members || 0)} members
* Revenue for current month: ₱${Number(current.revenue || 0).toLocaleString()}
* Membership growth: ${membersChange}% compared to previous month
* Revenue growth: ${revenueChange}% compared to previous month

`;
            } else {
                markdown += `Membership and revenue performance analysis for the ${period.toLowerCase()} period.

**Key Highlights This ${period}:**
* Tracking membership and revenue trends
* Analyzing growth patterns and financial performance

`;
            }
        } else if (type === 'peak-hours') {
            const rows = config.peakHoursRows || [];
            const bestSlot = rows.reduce((carry, row) => (Number(row.visits || 0) > Number(carry.visits || 0) ? row : carry), rows[0] || {});
            const totalVisits = rows.reduce((carry, row) => carry + Number(row.visits || 0), 0);

            markdown += `Analysis of facility utilization patterns and peak operating hours for optimal resource allocation and staffing decisions.

**Key Highlights This ${period}:**
* Peak operating window: ${bestSlot.label || 'N/A'} with ${Number(bestSlot.visits || 0)} visits
* Total tracked visits: ${totalVisits}
* Clear utilization patterns support operational planning
* Data-driven insights for staffing and facility management

`;
        } else if (type === 'membership-distribution') {
            const rows = config.distributionRows || [];
            const total = rows.reduce((carry, row) => carry + Number(row.count || 0), 0) || 1;
            const best = rows.reduce((carry, row) => (Number(row.count || 0) > Number(carry.count || 0) ? row : carry), rows[0] || {});
            const share = ((Number(best.count || 0) / total) * 100).toFixed(1);

            markdown += `Membership portfolio analysis showing distribution across different plan types and market positioning insights.

**Key Highlights This ${period}:**
* Largest membership segment: ${best.label || 'N/A'} at ${share}% of total members
* Total active members: ${total}
* Diverse plan offerings support different member needs
* Portfolio balance indicates healthy market positioning

`;
        }

        markdown += `---

## 2. Membership Metrics

| Metric | Current ${period} | Previous ${period} | Change (%) | Year-to-Date (YTD) |
| :--- | :--- | :--- | :--- | :--- |
`;

        if (type === 'growth' || type === 'all-reports') {
            const rows = config.growthRows || [];
            if (rows.length >= 2) {
                const current = rows[rows.length - 1];
                const previous = rows[rows.length - 2];
                const membersChange = previous.members ? ((current.members - previous.members) / previous.members * 100).toFixed(1) : '0.0';
                const revenueChange = previous.revenue ? ((current.revenue - previous.revenue) / previous.revenue * 100).toFixed(1) : '0.0';
                const ytdMembers = rows.reduce((carry, row) => carry + Number(row.members || 0), 0);
                const ytdRevenue = rows.reduce((carry, row) => carry + Number(row.revenue || 0), 0);

                markdown += `| **Total Active Members** | ${Number(current.members || 0).toLocaleString()} | ${Number(previous.members || 0).toLocaleString()} | +${membersChange}% | ${ytdMembers.toLocaleString()} |
| **New Sign-ups** | ${Number(current.members || 0).toLocaleString()} | ${Number(previous.members || 0).toLocaleString()} | +${membersChange}% | ${ytdMembers.toLocaleString()} |
| **Total Revenue** | ₱${Number(current.revenue || 0).toLocaleString()} | ₱${Number(previous.revenue || 0).toLocaleString()} | +${revenueChange}% | ₱${ytdRevenue.toLocaleString()} |
| **Average Revenue per Member** | ₱${current.members ? (current.revenue / current.members).toFixed(2) : '0.00'} | ₱${previous.members ? (previous.revenue / previous.members).toFixed(2) : '0.00'} | ${previous.members && current.members ? (((current.revenue / current.members) - (previous.revenue / previous.members)) / (previous.revenue / previous.members) * 100).toFixed(1) : '0.0'}% | ₱${ytdMembers ? (ytdRevenue / ytdMembers).toFixed(2) : '0.00'} |

`;
            } else {
                markdown += `| **Total Active Members** | ${config.member_count || 0} | N/A | N/A | N/A |
| **Total Revenue** | ₱${Number(config.total_revenue || 0).toLocaleString()} | N/A | N/A | ₱${Number(config.total_revenue || 0).toLocaleString()} |

`;
            }
        } else {
            markdown += `| **Total Active Members** | ${config.member_count || 0} | N/A | N/A | N/A |
| **Total Revenue** | ₱${Number(config.total_revenue || 0).toLocaleString()} | N/A | N/A | ₱${Number(config.total_revenue || 0).toLocaleString()} |

`;
        }

        markdown += `
**Membership Notes & Trends:**
* **Growth Analysis:** ${type === 'growth' || type === 'all-reports' ? 'Recent trends show positive momentum in membership acquisition' : 'Membership metrics indicate stable facility utilization'}
* **Revenue Performance:** ${type === 'growth' || type === 'all-reports' ? 'Revenue growth aligns with membership expansion' : 'Revenue streams support operational sustainability'}
* **Market Position:** Current data reflects competitive positioning in the local fitness market

---

## 3. Operational & Facility Management

| Area | Status | Notes/Action Taken |
| :--- | :--- | :--- |
`;

        // Equipment status (simplified - you may want to add actual equipment data)
        markdown += `| **Equipment Utilization** | 🟢 Good | All equipment operating within normal parameters |
| **Facility Cleanliness** | 🟢 Good | Regular maintenance and cleaning schedules maintained |
| **Member Satisfaction** | 🟢 Good | Positive feedback on facility conditions and services |
| **Staff Performance** | 🟢 Good | Consistent service delivery and member support |
| **Safety Compliance** | 🟢 Good | All safety protocols and procedures followed |

---

## 4. Goals for Next Month

1. **Membership Growth:** Continue implementing effective marketing strategies to increase member acquisition
2. **Revenue Optimization:** Focus on maximizing revenue per member through premium services and add-ons
3. **Operational Excellence:** Maintain high standards of facility management and equipment maintenance
4. **Member Experience:** Enhance member satisfaction through improved services and amenities
5. **Market Expansion:** Explore opportunities for business growth and market penetration

---

*Report generated by ${gymName} Management System on ${generatedAt}*
*Prepared by: ${exportedBy}*
`;

        return markdown;
    }

    function buildAllReportLines() {
        const period = periodMeta[periodFilter?.value || 'monthly'] || 'Monthly';
        const headline = `${config.gymName || 'Gym System'} Comprehensive System Export`;
        const sections = ['growth', 'peak-hours', 'membership-distribution', 'attendance'];
        const sectionTitles = {
            growth: 'Membership & Revenue Growth',
            'peak-hours': 'Peak Hours Analysis',
            'membership-distribution': 'Membership Distribution',
            attendance: 'Attendance Check-in / Check-out Records',
        };

        const lines = [
            headline,
            `Report Period: ${period}`,
            `Exported By: ${config.exportedBy || 'Administrator'}`,
            `Exported On: ${config.generatedAt || new Date().toLocaleString()}`,
            '',
        ];

        sections.forEach((section, index) => {
            if (index > 0) {
                lines.push('', '------------------------------------------------------------', '');
            }
            lines.push(`Section: ${sectionTitles[section]}`, '');
            lines.push(...buildReportLines(section, 'PDF').slice(1));
        });

        lines.push('', 'Macro report export generated by the WeDumbell Gym Management System.');
        lines.push(`Prepared by: ${config.exportedBy || 'Administrator'}`);
        lines.push(`Generated on: ${config.generatedAt || new Date().toLocaleString()}`);
        return lines;
    }

    function buildCsv(type) {
        if (type === 'all-reports') {
            return buildAllCsv();
        }

        const rows = buildDataRows(type);
        if (!rows.length) {
            return '';
        }

        const title = type === 'growth'
            ? 'Membership & Revenue Growth'
            : type === 'peak-hours'
                ? 'Peak Hours Analysis'
                : type === 'attendance'
                    ? 'Attendance Check-in / Check-out Records'
                    : 'Membership Distribution';
        const period = periodMeta[periodFilter?.value || 'monthly'] || 'Monthly';
        const headerLines = [
            `Report Title,${title}`,
            `Report Period,${period}`,
            `Exported By,${config.exportedBy || 'Administrator'}`,
            `Exported On,${config.generatedAt || new Date().toLocaleString()}`,
            `Generated By,${config.gymName || 'WeDumbell Gym Management System'}`,
            '',
        ];

        const headers = Object.keys(rows[0]);
        const lines = [...headerLines, headers.join(',')];
        rows.forEach((row) => {
            lines.push(headers.map((header) => `"${String(row[header] || '').replace(/"/g, '""')}"`).join(','));
        });

        lines.push('', 'Report Notes:', 'This report is generated by the WeDumbell Gym Management System for internal use only.');
        return lines.join('\r\n');
    }

    function buildAllCsv() {
        const sections = ['growth', 'peak-hours', 'membership-distribution', 'attendance'];
        const sectionTitles = {
            growth: 'Membership & Revenue Growth',
            'peak-hours': 'Peak Hours Analysis',
            'membership-distribution': 'Membership Distribution',
            attendance: 'Attendance Check-in / Check-out Records',
        };
        const period = periodMeta[periodFilter?.value || 'monthly'] || 'Monthly';
        const lines = [
            `Report Title,Comprehensive System Export`,
            `Report Period,${period}`,
            `Exported By,${config.exportedBy || 'Administrator'}`,
            `Exported On,${config.generatedAt || new Date().toLocaleString()}`,
            `Generated By,${config.gymName || 'WeDumbell Gym Management System'}`,
            '',
        ];

        sections.forEach((section, index) => {
            if (index > 0) {
                lines.push('', '');
            }
            const rows = buildDataRows(section);
            lines.push(`Section,${sectionTitles[section]}`);
            if (!rows.length) {
                lines.push('Message,No data available for this section');
            } else {
                const headers = Object.keys(rows[0]);
                lines.push(headers.join(','));
                rows.forEach((row) => {
                    lines.push(headers.map((header) => `"${String(row[header] || '').replace(/"/g, '""')}"`).join(','));
                });
            }
            lines.push('', 'Notes,This section was generated by the WeDumbell Gym Management System.');
        });

        lines.push('', `Generated by,${config.exportedBy || 'Administrator'}`);
        lines.push(`Generated on,${config.generatedAt || new Date().toLocaleString()}`);
        return lines.join('\r\n');
    }

    function getByteLength(value) {
        return new TextEncoder().encode(value).length;
    }

    function wrapPdfText(text, maxChars = 90) {
        const words = String(text || '').split(' ');
        const wrapped = [];
        let currentLine = '';

        words.forEach((word) => {
            const candidate = currentLine ? `${currentLine} ${word}` : word;
            if (candidate.length <= maxChars) {
                currentLine = candidate;
            } else {
                if (currentLine) {
                    wrapped.push(currentLine);
                }
                currentLine = word;
            }
        });

        if (currentLine) {
            wrapped.push(currentLine);
        }

        return wrapped;
    }

    function buildStyledReportElement(type) {
        const period = periodMeta[periodFilter?.value || 'monthly'] || 'Monthly';
        const gymName = config.gymName || 'WeDumbell Gym Management System';
        const exportedBy = config.exportedBy || 'Administrator';
        const generatedAt = config.generatedAt || new Date().toLocaleString();
        const rows = config.growthRows || [];
        const current = rows[rows.length - 1] || { members: 0, revenue: 0, label: 'N/A' };
        const previous = rows.length >= 2 ? rows[rows.length - 2] : { members: 0, revenue: 0, label: 'N/A' };
        const ytdMembers = rows.reduce((carry, row) => carry + Number(row.members || 0), 0);
        const ytdRevenue = rows.reduce((carry, row) => carry + Number(row.revenue || 0), 0);
        const membersChange = previous.members ? ((current.members - previous.members) / previous.members * 100).toFixed(1) : '0.0';
        const revenueChange = previous.revenue ? ((current.revenue - previous.revenue) / previous.revenue * 100).toFixed(1) : '0.0';
        const averageCurrent = current.members ? (current.revenue / current.members).toFixed(2) : '0.00';
        const averagePrevious = previous.members ? (previous.revenue / previous.members).toFixed(2) : '0.00';
        const averageChange = (previous.members && current.members)
            ? (((current.revenue / current.members) - (previous.revenue / previous.members)) / (previous.revenue / previous.members) * 100).toFixed(1)
            : '0.0';

        const wrapper = document.createElement('div');
        wrapper.style.position = 'absolute';
        wrapper.style.left = '0';
        wrapper.style.top = '0';
        wrapper.style.width = '794px';
        wrapper.style.maxWidth = '794px';
        wrapper.style.background = '#FFFFFF';
        wrapper.style.zIndex = '10000';
        wrapper.style.padding = '0';
        wrapper.style.margin = '0';
        wrapper.style.display = 'block';
        wrapper.style.opacity = '0';
        wrapper.style.overflow = 'visible';
        wrapper.innerHTML = `
            <div class="page">
                <style>
                    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
                    :root { --navy: #0D1B2A; --navy2: #1A3A5C; --gold: #F5A623; --gold2: #E8940A; --light: #F4F6F9; --border: #DEE3EA; --text: #1C2B3A; --muted: #5A6A7E; --green: #1FA96A; --red: #E03E3E; --white: #FFFFFF; --pad: 14mm; }
                    body { font-family: 'Barlow', sans-serif; color: var(--text); font-size: 9pt; }
                    .page { width: 210mm; min-height: 297mm; background: var(--white); display: flex; flex-direction: column; overflow: hidden; }
                    .banner { background: var(--navy); display: grid; grid-template-columns: 20mm 1fr auto; align-items: center; gap: 5mm; padding: 7mm var(--pad); border-bottom: 3px solid var(--gold); position: relative; }
                    .banner::before, .banner::after { content: ''; position: absolute; border-radius: 50%; background: rgba(245,166,35,.07); }
                    .banner::before { right: -20mm; top: -20mm; width: 70mm; height: 70mm; }
                    .banner::after { right: 10mm; top: -10mm; width: 40mm; height: 40mm; }
                    .logo-box { width: 16mm; height: 16mm; border-radius: 3mm; background: var(--gold); display: flex; align-items: center; justify-content: center; font-family: 'Barlow Condensed', sans-serif; font-weight: 800; font-size: 15pt; color: var(--navy); letter-spacing: -1px; }
                    .banner-title h1 { font-family: 'Barlow Condensed', sans-serif; font-weight: 700; font-size: 16pt; color: var(--white); letter-spacing: .5px; line-height: 1.1; }
                    .banner-title p { font-family: 'Barlow Condensed', sans-serif; font-weight: 600; font-size: 9.5pt; color: var(--gold); letter-spacing: 2px; text-transform: uppercase; margin-top: 1.5mm; }
                    .banner-meta { text-align: right; line-height: 1.7; position: relative; z-index: 1; }
                    .banner-meta span { display: block; color: #90A4B8; font-size: 7.5pt; }
                    .banner-meta strong { color: #B0C4D8; font-weight: 600; }
                    .body { padding: 8mm var(--pad) 10mm; flex: 1; }
                    .sec-head { background: var(--navy2); border-bottom: 2.5px solid var(--gold); padding: 3mm 5mm; margin-bottom: 4mm; }
                    .sec-head h2 { font-family: 'Barlow Condensed', sans-serif; font-weight: 700; font-size: 11pt; color: var(--white); letter-spacing: 1px; text-transform: uppercase; }
                    .exec-text { color: var(--muted); line-height: 1.65; margin-bottom: 3mm; }
                    .highlights { background: var(--light); border-left: 3px solid var(--gold); padding: 3mm 5mm; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5mm 5mm; margin-bottom: 6mm; }
                    .highlights li { list-style: none; color: var(--text); font-size: 8.5pt; line-height: 1.5; padding-left: 8px; position: relative; }
                    .highlights li::before { content: '▸'; color: var(--gold); position: absolute; left: 0; font-size: 8pt; }
                    .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 3mm; margin-bottom: 4mm; }
                    .kpi-card { background: var(--white); border: 1px solid var(--border); border-top: 2.5px solid var(--gold); border-radius: 1mm; padding: 4mm 3mm; text-align: center; }
                    .kpi-value { font-family: 'Barlow Condensed', sans-serif; font-weight: 800; font-size: 20pt; color: var(--navy); line-height: 1; }
                    .kpi-label { font-size: 7.5pt; color: var(--muted); margin: 1.5mm 0 1.5mm; line-height: 1.35; }
                    .kpi-change { font-weight: 700; font-size: 8pt; padding: 0.8mm 3mm; border-radius: 10mm; display: inline-block; }
                    .chg-down { background: #FEE8E8; color: var(--red); }
                    .chg-up { background: #E2F5EC; color: var(--green); }
                    .chg-flat { background: #EFF2F6; color: var(--muted); }
                    .data-table, .ops-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 6mm; }
                    .data-table thead tr, .ops-table thead tr { background: var(--navy2); color: var(--white); }
                    .data-table thead th, .ops-table thead th { padding: 3mm 4mm; font-family: 'Barlow Condensed', sans-serif; font-weight: 600; font-size: 9pt; letter-spacing: .4px; border-bottom: 2px solid var(--gold); }
                    .data-table thead th:first-child, .ops-table thead th:first-child { text-align: left; }
                    .data-table td, .ops-table td { padding: 2.5mm 4mm; border: 1px solid var(--border); vertical-align: middle; color: var(--text); }
                    .data-table tbody tr:nth-child(odd), .ops-table tbody tr:nth-child(odd) { background: var(--light); }
                    .data-table tbody tr:nth-child(even), .ops-table tbody tr:nth-child(even) { background: var(--white); }
                    .data-table td:first-child { text-align: left; font-weight: 600; color: var(--navy); }
                    .ops-table td.area { font-weight: 600; color: var(--navy); }
                    .ops-table td.status { text-align: center; }
                    .ops-table td.notes { color: var(--muted); font-size: 8pt; }
                    .status-badge { display: inline-flex; align-items: center; gap: 3px; background: #E2F5EC; color: var(--green); font-weight: 700; font-size: 7.5pt; padding: 1mm 4mm; border-radius: 10mm; letter-spacing: .3px; }
                    .status-badge::before { content: '●'; font-size: 8pt; }
                    .goals-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 3mm; margin-bottom: 8mm; }
                    .goal-card { background: var(--light); border: 1px solid var(--border); border-top: 2.5px solid var(--gold); border-radius: 1mm; padding: 4mm 3.5mm; text-align: center; }
                    .goal-num { font-family: 'Barlow Condensed', sans-serif; font-weight: 800; font-size: 18pt; color: var(--gold2); line-height: 1; }
                    .goal-title { font-family: 'Barlow Condensed', sans-serif; font-weight: 700; font-size: 9.5pt; color: var(--navy); margin: 1.5mm 0 2mm; letter-spacing: .2px; text-transform: uppercase; }
                    .goal-desc { font-size: 7.5pt; color: var(--muted); line-height: 1.45; text-align: left; }
                    .notes-box { background: var(--light); border-left: 3px solid var(--gold); border: 1px solid var(--border); padding: 3mm 5mm; margin-bottom: 6mm; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2mm 5mm; }
                    .note-item { font-size: 8pt; color: var(--muted); line-height: 1.5; }
                    .note-item strong { color: var(--text); }
                    .footer { border-top: 1px solid var(--border); padding: 3mm var(--pad); display: flex; justify-content: space-between; align-items: center; margin-top: auto; }
                    .footer-left, .footer-right { font-size: 7pt; color: var(--muted); line-height: 1.5; }
                    .footer-right { text-align: right; }
                    .confidential { background: var(--navy); color: var(--gold); font-family: 'Barlow Condensed', sans-serif; font-weight: 700; font-size: 7pt; letter-spacing: 1.5px; padding: 1mm 3mm; border-radius: 1mm; }
                </style>
                <header class="banner">
                    <div class="logo-box">WD</div>
                    <div class="banner-title">
                        <h1>${gymName}</h1>
                        <p>Monthly Performance Report</p>
                    </div>
                    <div class="banner-meta">
                        <span><strong>Reporting Period:</strong> ${period}</span>
                        <span><strong>Date Submitted:</strong> ${generatedAt}</span>
                        <span><strong>Prepared By:</strong> ${exportedBy}</span>
                    </div>
                </header>
                <main class="body">
                    <div class="sec-head"><h2>1. Executive Summary</h2></div>
                    <p class="exec-text">A comprehensive overview of the gym's performance across all key metrics for the ${period.toLowerCase()} period. This report covers membership growth, revenue trends, operational efficiency, and facility management insights.</p>
                    <ul class="highlights">
                        <li>Membership and revenue data shows steady performance trends</li>
                        <li>Peak hours analysis indicates optimal operational windows</li>
                        <li>Membership distribution reflects current market positioning</li>
                        <li>Attendance tracking demonstrates facility utilization patterns</li>
                    </ul>
                    <div class="sec-head"><h2>2. Membership Metrics</h2></div>
                    <div class="kpi-grid">
                        <div class="kpi-card"><div class="kpi-value">${Number(current.members || 0).toLocaleString()}</div><div class="kpi-label">Active Members<br>(Current Month)</div><span class="kpi-change ${membersChange < 0 ? 'chg-down' : membersChange > 0 ? 'chg-up' : 'chg-flat'}">${membersChange < 0 ? '▼' : membersChange > 0 ? '▲' : '—'} ${membersChange}%</span></div>
                        <div class="kpi-card"><div class="kpi-value">${Number(current.members || 0).toLocaleString()}</div><div class="kpi-label">New Sign-ups<br>(Current Month)</div><span class="kpi-change ${membersChange < 0 ? 'chg-down' : membersChange > 0 ? 'chg-up' : 'chg-flat'}">${membersChange < 0 ? '▼' : membersChange > 0 ? '▲' : '—'} ${membersChange}%</span></div>
                        <div class="kpi-card"><div class="kpi-value">₱${Number(current.revenue || 0).toLocaleString()}</div><div class="kpi-label">Total Revenue<br>(Current Month)</div><span class="kpi-change ${revenueChange < 0 ? 'chg-down' : revenueChange > 0 ? 'chg-up' : 'chg-flat'}">${revenueChange < 0 ? '▼' : revenueChange > 0 ? '▲' : '—'} ${revenueChange}%</span></div>
                        <div class="kpi-card"><div class="kpi-value">₱${averageCurrent}</div><div class="kpi-label">Avg Revenue / Member<br>(Current Month)</div><span class="kpi-change ${averageChange < 0 ? 'chg-down' : averageChange > 0 ? 'chg-up' : 'chg-flat'}">${averageChange < 0 ? '▼' : averageChange > 0 ? '▲' : '—'} ${averageChange}%</span></div>
                    </div>
                    <table class="data-table">
                        <thead><tr><th>Metric</th><th>Current Month</th><th>Previous Month</th><th>Change (%)</th><th>YTD</th></tr></thead>
                        <tbody>
                            <tr><td>Total Active Members</td><td>${Number(current.members || 0).toLocaleString()}</td><td>${Number(previous.members || 0).toLocaleString()}</td><td class="chg-${membersChange < 0 ? 'down' : membersChange > 0 ? 'up' : 'flat'}">${membersChange < 0 ? '▼' : membersChange > 0 ? '▲' : '—'} ${membersChange}%</td><td>${ytdMembers.toLocaleString()}</td></tr>
                            <tr><td>New Sign-ups</td><td>${Number(current.members || 0).toLocaleString()}</td><td>${Number(previous.members || 0).toLocaleString()}</td><td class="chg-${membersChange < 0 ? 'down' : membersChange > 0 ? 'up' : 'flat'}">${membersChange < 0 ? '▼' : membersChange > 0 ? '▲' : '—'} ${membersChange}%</td><td>${ytdMembers.toLocaleString()}</td></tr>
                            <tr><td>Total Revenue</td><td>₱${Number(current.revenue || 0).toLocaleString()}</td><td>₱${Number(previous.revenue || 0).toLocaleString()}</td><td class="chg-${revenueChange < 0 ? 'down' : revenueChange > 0 ? 'up' : 'flat'}">${revenueChange < 0 ? '▼' : revenueChange > 0 ? '▲' : '—'} ${revenueChange}%</td><td>₱${ytdRevenue.toLocaleString()}</td></tr>
                            <tr><td>Average Revenue per Member</td><td>₱${averageCurrent}</td><td>₱${averagePrevious}</td><td class="chg-${averageChange < 0 ? 'down' : averageChange > 0 ? 'up' : 'flat'}">${averageChange < 0 ? '▼' : averageChange > 0 ? '▲' : '—'} ${averageChange}%</td><td>₱${ytdMembers ? (ytdRevenue / ytdMembers).toFixed(2) : '0.00'}</td></tr>
                        </tbody>
                    </table>
                    <div class="notes-box">
                        <div class="note-item"><strong>Growth Analysis:</strong> Recent trends show positive momentum in membership acquisition.</div>
                        <div class="note-item"><strong>Revenue Performance:</strong> Revenue growth aligns with membership expansion.</div>
                        <div class="note-item"><strong>Market Position:</strong> Current data reflects competitive positioning in the local fitness market.</div>
                    </div>
                    <div class="sec-head"><h2>3. Operational & Facility Management</h2></div>
                    <table class="ops-table">
                        <thead><tr><th style="width:28%">Area</th><th class="center" style="width:14%">Status</th><th>Notes / Action Taken</th></tr></thead>
                        <tbody>
                            <tr><td class="area">Equipment Utilization</td><td class="status"><span class="status-badge">GOOD</span></td><td class="notes">All equipment operating within normal parameters.</td></tr>
                            <tr><td class="area">Facility Cleanliness</td><td class="status"><span class="status-badge">GOOD</span></td><td class="notes">Regular maintenance and cleaning schedules maintained.</td></tr>
                            <tr><td class="area">Member Satisfaction</td><td class="status"><span class="status-badge">GOOD</span></td><td class="notes">Positive feedback on facility conditions and services.</td></tr>
                            <tr><td class="area">Staff Performance</td><td class="status"><span class="status-badge">GOOD</span></td><td class="notes">Consistent service delivery and member support.</td></tr>
                            <tr><td class="area">Safety Compliance</td><td class="status"><span class="status-badge">GOOD</span></td><td class="notes">All safety protocols and procedures followed.</td></tr>
                        </tbody>
                    </table>
                    <div class="sec-head"><h2>4. Goals for Next Month</h2></div>
                    <div class="goals-grid">
                        <div class="goal-card"><div class="goal-num">1</div><div class="goal-title">Membership Growth</div><p class="goal-desc">Continue implementing effective marketing strategies to increase member acquisition.</p></div>
                        <div class="goal-card"><div class="goal-num">2</div><div class="goal-title">Revenue Optimization</div><p class="goal-desc">Focus on maximizing revenue per member through premium services and add-ons.</p></div>
                        <div class="goal-card"><div class="goal-num">3</div><div class="goal-title">Operational Excellence</div><p class="goal-desc">Maintain high standards of facility management and equipment maintenance.</p></div>
                        <div class="goal-card"><div class="goal-num">4</div><div class="goal-title">Member Experience</div><p class="goal-desc">Enhance member satisfaction through improved services and amenities.</p></div>
                        <div class="goal-card"><div class="goal-num">5</div><div class="goal-title">Market Expansion</div><p class="goal-desc">Explore opportunities for business growth and market penetration.</p></div>
                    </div>
                </main>
                <footer class="footer">
                    <div class="footer-left">Report generated by <strong>${gymName}</strong> on ${generatedAt}<br>Prepared by: <strong>${exportedBy}</strong></div>
                    <div class="footer-right"><span class="confidential">CONFIDENTIAL</span><br><span style="margin-top:1mm;display:block">For Internal Use Only</span></div>
                </footer>
            </div>
        `;

        return wrapper;
    }

    function generatePlainTextPdf(filename, content) {
        const element = buildPlainReportElement('all-reports', false);
        renderElementToPdf(element, filename, 'all-reports');
    }

    function getChartCanvasIds(type) {
        if (type === 'growth') {
            return ['membershipGrowthChart', 'revenueOverviewChart'];
        }

        if (type === 'peak-hours') {
            return ['peakHoursChart'];
        }

        if (type === 'membership-distribution') {
            return ['membershipDistributionChart'];
        }

        return [];
    }

    function getChartLabel(canvasId) {
        const labels = {
            membershipGrowthChart: 'Membership Growth Chart',
            revenueOverviewChart: 'Revenue Overview Chart',
            peakHoursChart: 'Peak Hours Chart',
            membershipDistributionChart: 'Membership Distribution Chart',
        };

        return labels[canvasId] || 'Chart';
    }

    function generateReportPdfWithCharts(type, filename) {
        const element = buildPlainReportElement(type, true);
        renderElementToPdf(element, filename, type);
    }

    function generatePdfFromHtml(type, filename) {
        if (type === 'all-reports') {
            generatePlainTextPdf(filename, buildPlainFullReport());
            return;
        }

        generateReportPdfWithCharts(type, filename);
        return;

        const html2canvasFn = window.html2canvas || window.html2canvas;
        const jsPDFCtor = window.jspdf?.jsPDF || window.jsPDF || window.jspdf;

        const element = buildStyledReportElement(type);
        document.body.appendChild(element);
        window.scrollTo(0, 0);

        setTimeout(() => {
            if (typeof html2canvasFn !== 'function' || typeof jsPDFCtor !== 'function') {
                document.body.removeChild(element);
                const pdf = buildSimplePdf(type);
                downloadFile(filename, pdf, 'application/pdf');
                return;
            }

            html2canvasFn(element, { scale: 2, useCORS: true, backgroundColor: '#ffffff' })
                .then((canvas) => {
                    const imgData = canvas.toDataURL('image/jpeg', 1.0);
                    const pdf = new jsPDFCtor({ unit: 'mm', format: 'a4', orientation: 'portrait' });
                    const pdfWidth = pdf.internal.pageSize.getWidth();
                    const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
                    pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
                    pdf.save(filename);
                    document.body.removeChild(element);
                })
                .catch((error) => {
                    console.error('PDF render failed', error);
                    document.body.removeChild(element);
                    const pdf = buildSimplePdf(type);
                    downloadFile(filename, pdf, 'application/pdf');
                });
        }, 300);
    }

    function buildSimplePdf(type) {
        const reportContent = buildPlainReport(type);
        const lines = reportContent.split(/\r?\n/);

        // Simple PDF generation from text content with light wrapping.
        const wrappedLines = lines.flatMap((line) => {
            if (line.startsWith('#') || line.startsWith('|') || line === '---' || line.trim() === '') {
                return [line];
            }
            return wrapPdfText(line, 90);
        });

        const contents = ['BT', '/F1 16 Tf', '22 TL', '40 760 Td'];
        const titleLine = wrappedLines.shift() || 'Gym Management Report';
        contents.push(`(${escapePdfText(titleLine)}) Tj`, 'T*', '/F1 12 Tf');

        wrappedLines.forEach((line, index) => {
            contents.push(`(${escapePdfText(line)}) Tj`);
            if (index < wrappedLines.length - 1) {
                contents.push('T*');
            }
        });

        contents.push('ET');
        const stream = contents.join('\n');
        const streamLength = getByteLength(stream);

        const objects = [
            `1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj`,
            `2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj`,
            `3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj`,
            `4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj`,
            `5 0 obj\n<< /Length ${streamLength} >>\nstream\n${stream}\nendstream\nendobj`,
        ];

        let pdf = '%PDF-1.3\n';
        const offsets = [getByteLength(pdf)];
        objects.forEach((object) => {
            const objectText = object + '\n';
            pdf += objectText;
            offsets.push(offsets[offsets.length - 1] + getByteLength(objectText));
        });

        const xrefStart = offsets[offsets.length - 1];
        pdf += 'xref\n0 ' + (objects.length + 1) + '\n0000000000 65535 f \n';
        offsets.slice(0, -1).forEach((offset) => {
            pdf += `${String(offset).padStart(10, '0')} 00000 n \n`;
        });
        pdf += `trailer\n<< /Size ${objects.length + 1} /Root 1 0 R >>\nstartxref\n${xrefStart}\n%%EOF`;

        return pdf;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildPlainReportElement(type, includeCharts = false) {
        const wrapper = document.createElement('div');
        const reportLines = buildPlainReport(type).split(/\r?\n/);
        const title = reportLines.shift() || 'Gym Management Report';
        const bodyHtml = reportLines.map((line) => {
            if (!line.trim()) {
                return '<div class="plain-report-spacer"></div>';
            }

            if (/^\d+\.\s/.test(line)) {
                return `<h2 class="plain-report-section">${escapeHtml(line)}</h2>`;
            }

            if (line.endsWith(':')) {
                return `<h3 class="plain-report-subsection">${escapeHtml(line)}</h3>`;
            }

            if (line.startsWith('- ')) {
                return `<p class="plain-report-bullet">${escapeHtml(line)}</p>`;
            }

            return `<p class="plain-report-line">${escapeHtml(line)}</p>`;
        }).join('');

        const chartHtml = includeCharts
            ? getChartCanvasIds(type).map((canvasId) => {
                const canvas = document.getElementById(canvasId);

                if (!(canvas instanceof HTMLCanvasElement) || !canvas.width || !canvas.height) {
                    return '';
                }

                return `
                    <section class="plain-report-chart-block">
                        <h2 class="plain-report-section">${escapeHtml(getChartLabel(canvasId))}</h2>
                        <img class="plain-report-chart-image" src="${canvas.toDataURL('image/png', 1.0)}" alt="${escapeHtml(getChartLabel(canvasId))}" />
                    </section>
                `;
            }).join('')
            : '';

        wrapper.style.position = 'absolute';
        wrapper.style.left = '-99999px';
        wrapper.style.top = '0';
        wrapper.style.width = '794px';
        wrapper.style.background = '#ffffff';
        wrapper.style.zIndex = '10000';
        wrapper.style.padding = '0';
        wrapper.style.margin = '0';
        wrapper.style.opacity = '1';
        wrapper.style.pointerEvents = 'none';
        wrapper.innerHTML = `
            <div class="plain-report-page">
                <style>
                    .plain-report-page {
                        width: 794px;
                        background: #ffffff;
                        color: #142132;
                        font-family: "Barlow", "Segoe UI", Arial, sans-serif;
                        padding: 40px 44px;
                        box-sizing: border-box;
                    }
                    .plain-report-title {
                        margin: 0 0 24px;
                        font-size: 28px;
                        line-height: 1.2;
                        font-weight: 700;
                        letter-spacing: -0.03em;
                        color: #0d1b2a;
                    }
                    .plain-report-section {
                        margin: 26px 0 12px;
                        font-size: 18px;
                        line-height: 1.3;
                        font-weight: 700;
                        color: #0d1b2a;
                    }
                    .plain-report-subsection {
                        margin: 16px 0 8px;
                        font-size: 15px;
                        line-height: 1.35;
                        font-weight: 700;
                        color: #22324a;
                    }
                    .plain-report-line,
                    .plain-report-bullet {
                        margin: 0 0 7px;
                        font-size: 14px;
                        line-height: 1.55;
                        color: #334155;
                        white-space: pre-wrap;
                        word-break: break-word;
                    }
                    .plain-report-bullet {
                        padding-left: 14px;
                    }
                    .plain-report-spacer {
                        height: 10px;
                    }
                    .plain-report-chart-block {
                        margin-top: 28px;
                    }
                    .plain-report-chart-image {
                        display: block;
                        width: 100%;
                        height: auto;
                        margin-top: 12px;
                        border: 1px solid #d7deea;
                        border-radius: 14px;
                        background: #ffffff;
                    }
                </style>
                <h1 class="plain-report-title">${escapeHtml(title)}</h1>
                ${bodyHtml}
                ${chartHtml}
            </div>
        `;

        return wrapper;
    }

    function renderElementToPdf(element, filename, fallbackType) {
        const html2canvasFn = window.html2canvas || window.html2canvas;
        const jsPDFCtor = window.jspdf?.jsPDF || window.jsPDF || window.jspdf;

        if (typeof html2canvasFn !== 'function' || typeof jsPDFCtor !== 'function') {
            const pdf = buildSimplePdf(fallbackType);
            downloadFile(filename, pdf, 'application/pdf');
            return;
        }

        document.body.appendChild(element);
        window.scrollTo(0, 0);

        setTimeout(() => {
            html2canvasFn(element, { scale: 2, useCORS: true, backgroundColor: '#ffffff' })
                .then((canvas) => {
                    const pdf = new jsPDFCtor({ unit: 'mm', format: 'a4', orientation: 'portrait' });
                    const pageWidth = pdf.internal.pageSize.getWidth();
                    const pageHeight = pdf.internal.pageSize.getHeight();
                    const imageData = canvas.toDataURL('image/png', 1.0);
                    const imageWidth = pageWidth;
                    const imageHeight = (canvas.height * imageWidth) / canvas.width;
                    let heightLeft = imageHeight;
                    let position = 0;

                    pdf.addImage(imageData, 'PNG', 0, position, imageWidth, imageHeight);
                    heightLeft -= pageHeight;

                    while (heightLeft > 0) {
                        position = heightLeft - imageHeight;
                        pdf.addPage();
                        pdf.addImage(imageData, 'PNG', 0, position, imageWidth, imageHeight);
                        heightLeft -= pageHeight;
                    }

                    pdf.save(filename);
                    document.body.removeChild(element);
                })
                .catch((error) => {
                    console.error('PDF render failed', error);
                    if (document.body.contains(element)) {
                        document.body.removeChild(element);
                    }

                    const pdf = buildSimplePdf(fallbackType);
                    downloadFile(filename, pdf, 'application/pdf');
                });
        }, 150);
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
        exportSubtitle.textContent = `Selected period: ${periodLabel}. Choose CSV, PDF, or Markdown and then download.`;
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
            generatePdfFromHtml(currentExportKey, filename);
        } else if (format === 'md') {
            const markdown = buildMarkdownReport(currentExportKey);
            downloadFile(filename, markdown, 'text/markdown;charset=utf-8;');
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
