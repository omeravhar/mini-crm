@extends('layouts.crm')

@section('pageTitle', 'אנליטיקה ניהולית')
@section('pageSubtitle', 'מדדי לידים, סגירות וביצועי משתמשים לפי טווח תאריכים')

@section('content')
    <div
        id="analyticsPage"
        data-created-leads="{{ $metrics['created_leads'] }}"
        data-open-leads="{{ $metrics['open_leads'] }}"
        data-won-leads="{{ $metrics['won_leads'] }}"
        data-closed-leads="{{ $metrics['closed_leads'] }}"
    >
        <form method="GET" action="{{ route('admin.analytics.index') }}" class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label" for="from">מתאריך</label>
                        <input class="form-control" id="from" name="from" type="date" value="{{ $filters['from'] }}">
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label" for="to">עד תאריך</label>
                        <input class="form-control" id="to" name="to" type="date" value="{{ $filters['to'] }}">
                    </div>
                    <div class="col-md-4 col-lg-2 d-grid">
                        <button class="btn btn-dark" type="submit">הצג נתונים</button>
                    </div>
                    <div class="col-md-4 col-lg-2 d-grid">
                        <a class="btn btn-outline-secondary" href="{{ route('admin.analytics.index') }}">איפוס</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-2">
                <div class="card card-stat h-100">
                    <div class="card-body">
                        <div class="text-muted small">לידים שנכנסו</div>
                        <div class="display-6 fw-semibold">{{ $metrics['created_leads'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="card card-stat h-100">
                    <div class="card-body">
                        <div class="text-muted small">לידים פתוחים</div>
                        <div class="display-6 fw-semibold">{{ $metrics['open_leads'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="card card-stat h-100">
                    <div class="card-body">
                        <div class="text-muted small">סגירות מוצלחות</div>
                        <div class="display-6 fw-semibold">{{ $metrics['won_leads'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="card card-stat h-100">
                    <div class="card-body">
                        <div class="text-muted small">לידים שנסגרו</div>
                        <div class="display-6 fw-semibold">{{ $metrics['closed_leads'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="card card-stat h-100">
                    <div class="card-body">
                        <div class="text-muted small">ימים ממוצעים לסגירה</div>
                        <div class="display-6 fw-semibold">{{ is_null($metrics['average_close_days']) ? '-' : $metrics['average_close_days'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-2">
                <div class="card card-stat h-100">
                    <div class="card-body">
                        <div class="text-muted small">אחוז זכייה</div>
                        <div class="display-6 fw-semibold">{{ is_null($metrics['win_rate']) ? '-' : $metrics['win_rate'] . '%' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <div class="fw-semibold">כניסת לידים וסגירות לאורך זמן</div>
                    </div>
                    <div class="card-body">
                        <div style="height: 320px;">
                            <canvas id="leadFlowChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <div class="fw-semibold">התפלגות סטטוסים</div>
                    </div>
                    <div class="card-body">
                        <div style="height: 320px;">
                            <canvas id="statusDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <div class="fw-semibold">לידים לפי משתמש</div>
                    </div>
                    <div class="card-body">
                        @if ($ownerPerformance->isNotEmpty())
                            <div style="height: 340px;">
                                <canvas id="ownerPerformanceChart"></canvas>
                            </div>
                        @else
                            <div class="text-center text-muted py-5">אין מספיק נתונים להצגת גרף משתמשים בטווח שנבחר.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <div class="fw-semibold">ביצועים לפי משתמש</div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>משתמש</th>
                                        <th>לידים בטווח</th>
                                        <th>פתוחים</th>
                                        <th>זכיות</th>
                                        <th>אבודים</th>
                                        <th>ממוצע ימי סגירה</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ownerPerformance as $row)
                                        <tr>
                                            <td class="fw-semibold">{{ $row['name'] }}</td>
                                            <td>{{ $row['created'] }}</td>
                                            <td>{{ $row['open'] }}</td>
                                            <td>{{ $row['won'] }}</td>
                                            <td>{{ $row['lost'] }}</td>
                                            <td>{{ is_null($row['avg_close_days']) ? '-' : $row['avg_close_days'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">אין נתונים בטווח התאריכים שנבחר.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" crossorigin="anonymous"></script>
    <script>
        (() => {
            const charts = @json($charts);

            const flowCanvas = document.getElementById('leadFlowChart');
            const statusCanvas = document.getElementById('statusDistributionChart');
            const ownerCanvas = document.getElementById('ownerPerformanceChart');

            if (flowCanvas) {
                new Chart(flowCanvas, {
                    type: 'line',
                    data: {
                        labels: charts.daily.labels,
                        datasets: [
                            {
                                label: 'לידים שנכנסו',
                                data: charts.daily.created,
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13,110,253,0.12)',
                                tension: 0.3,
                                fill: true,
                            },
                            {
                                label: 'סגירות מוצלחות',
                                data: charts.daily.won,
                                borderColor: '#198754',
                                backgroundColor: 'rgba(25,135,84,0.12)',
                                tension: 0.3,
                                fill: true,
                            },
                            {
                                label: 'אבודים',
                                data: charts.daily.lost,
                                borderColor: '#dc3545',
                                backgroundColor: 'rgba(220,53,69,0.12)',
                                tension: 0.3,
                                fill: true,
                            },
                        ],
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                        },
                    },
                });
            }

            if (statusCanvas) {
                new Chart(statusCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: charts.statusDistribution.labels,
                        datasets: [{
                            data: charts.statusDistribution.values,
                            backgroundColor: ['#0d6efd', '#20c997', '#ffc107', '#6f42c1', '#198754', '#dc3545'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                        },
                    },
                });
            }

            if (ownerCanvas && charts.owners.labels.length > 0) {
                new Chart(ownerCanvas, {
                    type: 'bar',
                    data: {
                        labels: charts.owners.labels,
                        datasets: [
                            {
                                label: 'פתוחים',
                                data: charts.owners.open,
                                backgroundColor: '#0d6efd',
                            },
                            {
                                label: 'זכיות',
                                data: charts.owners.won,
                                backgroundColor: '#198754',
                            },
                            {
                                label: 'אבודים',
                                data: charts.owners.lost,
                                backgroundColor: '#dc3545',
                            },
                        ],
                    },
                    options: {
                        maintainAspectRatio: false,
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                        },
                        scales: {
                            x: {
                                stacked: true,
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                },
                            },
                        },
                    },
                });
            }
        })();
    </script>
@endpush
