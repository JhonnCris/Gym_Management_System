@extends('layouts.admin', ['title' => 'Reports'])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Reports and Analytics</h1>
            <p class="page-description">Focused reporting for trends, peak usage windows, and membership mix without repeating the dashboard summary cards.</p>
        </div>
    </div>

    <section class="section-card">
        <div class="analytics-toolbar">
            <div>
                <div class="section-heading">
                    <span class="inline-icon">@include('admin.partials.icon', ['name' => 'reports'])</span>
                    <strong>Export Filters</strong>
                </div>
                <p class="analytics-note">Select a reporting period and export format before downloading chart summaries.</p>
            </div>
            <div class="toolbar-controls">
                <div class="field-inline">
                    <select class="select" id="reportPeriodFilter">
                        <option value="weekly">Weekly report</option>
                        <option value="monthly" selected>Monthly report</option>
                        <option value="quarterly">Quarterly report</option>
                        <option value="yearly">Yearly report</option>
                    </select>
                </div>
                <div class="field-inline">
                    <select class="select" id="reportFormatSelect">
                        <option value="csv">CSV</option>
                        <option value="pdf">PDF</option>
                        <option value="md">Markdown</option>
                    </select>
                </div>
                <div class="field-inline">
                    <button class="btn secondary" type="button" data-report-export="all-reports">
                        <span class="button-icon">@include('admin.partials.icon', ['name' => 'export'])</span>
                        <span>Export Full Report</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="section-card">
        <div class="page-header compact-header">
            <div class="section-heading">
                <span class="inline-icon">@include('admin.partials.icon', ['name' => 'reports'])</span>
                <strong>Membership & Revenue Growth</strong>
            </div>
            <button class="btn" type="button" data-report-export="growth"><span class="button-icon">@include('admin.partials.icon', ['name' => 'export'])</span><span>Export</span></button>
        </div>

        <div class="report-kpis">
            <div class="report-kpi">
                <div class="report-kpi-head">
                    <span>Top Revenue Month</span>
                    <span class="report-kpi-icon">@include('admin.partials.icon', ['name' => 'revenue'])</span>
                </div>
                <strong>{{ $reportStats['top_revenue_month']['label'] ?? 'N/A' }}</strong>
                <small>₱{{ number_format((float) ($reportStats['top_revenue_month']['revenue'] ?? 0), 2) }}</small>
            </div>
            <div class="report-kpi">
                <div class="report-kpi-head">
                    <span>Tracked Members</span>
                    <span class="report-kpi-icon">@include('admin.partials.icon', ['name' => 'users'])</span>
                </div>
                <strong>{{ number_format($reportStats['member_count']) }}</strong>
                <small>Members currently represented in the system</small>
            </div>
            <div class="report-kpi">
                <div class="report-kpi-head">
                    <span>Total Paid Revenue</span>
                    <span class="report-kpi-icon">@include('admin.partials.icon', ['name' => 'payments'])</span>
                </div>
                <strong>₱{{ number_format($reportStats['total_revenue'], 2) }}</strong>
                <small>Approved and paid membership transactions</small>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h2>Membership Growth</h2>
                        <p class="chart-card-subtitle">Total active members over time</p>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="membershipGrowthChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h2>Revenue Overview</h2>
                        <p class="chart-card-subtitle">Monthly revenue in PHP</p>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="revenueOverviewChart"></canvas>
                </div>
            </div>
        </div>
    </section>

    <div class="analytics-grid">
        <section class="chart-card">
            <div class="page-header compact-header">
                <div class="section-heading">
                    <span class="inline-icon">@include('admin.partials.icon', ['name' => 'visits'])</span>
                    <strong>Peak Hours Analysis</strong>
                </div>
                <button class="btn" type="button" data-report-export="peak-hours"><span class="button-icon">@include('admin.partials.icon', ['name' => 'export'])</span><span>Export</span></button>
            </div>

            <div class="chart-container line-chart-card">
                <canvas id="peakHoursChart"></canvas>
            </div>

            <p class="analytics-note">Peak slot: {{ $reportStats['top_peak_slot']['label'] ?? 'N/A' }} with {{ $reportStats['top_peak_slot']['visits'] ?? 0 }} logged check-ins.</p>
        </section>

        <section class="chart-card">
            <div class="page-header compact-header">
                <div class="section-heading">
                    <span class="inline-icon">@include('admin.partials.icon', ['name' => 'users'])</span>
                    <strong>Membership Distribution</strong>
                </div>
                <button class="btn" type="button" data-report-export="membership-distribution"><span class="button-icon">@include('admin.partials.icon', ['name' => 'export'])</span><span>Export</span></button>
            </div>

            <div class="chart-container">
                <canvas id="membershipDistributionChart"></canvas>
            </div>

            <p class="analytics-note">Leading plan: {{ $reportStats['top_membership']['label'] ?? 'N/A' }} with {{ $reportStats['top_membership']['count'] ?? 0 }} members.</p>
        </section>
    </div>

    <div class="modal-backdrop" id="reportExportModal" aria-hidden="true">
        <div class="modal-card">
            <h2 class="modal-title" id="reportExportTitle">Export Report</h2>
            <p class="analytics-note" id="reportExportSubtitle">Choose the file name for this analytics export.</p>

            <div class="form-grid" style="margin-top: 18px;">
                <div class="field">
                    <label for="reportFileName">File name</label>
                    <input class="input" id="reportFileName" type="text" value="report-export" />
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn primary" id="confirmReportExport">Download</button>
                <button class="btn" type="button" id="cancelReportExport">Cancel</button>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script id="adminReportsConfig" type="application/json">
        {!! json_encode([
            'growthRows' => $growthRows,
            'peakHoursRows' => $peakHoursRows,
            'distributionRows' => $distributionRows,
            'attendanceRows' => $attendanceRows,
            'generatedAt' => now()->format('M d, Y h:i A'),
            'gymName' => 'WeDumbell Gym Management System',
            'exportedBy' => auth()->user()?->full_name ?? auth()->user()?->email ?? 'Administrator',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="{{ asset('js/admin-reports.js') }}?v={{ filemtime(public_path('js/admin-reports.js')) }}"></script>
@endsection
