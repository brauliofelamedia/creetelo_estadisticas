<!-- filepath: c:\laragon\www\creetee\resources\views\admin\filters\actives.blade.php -->
@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Subscripciones por Periodo</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="#" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Subscripciones por Periodo</li>
        </ul>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="card-title mb-0">Filtrar por Periodo</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('filters.actives') }}" method="GET">
                <div class="row">
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label>Inicio Mes</label>
                                    <select class="form-control" name="start_month1" id="start-month1-select">
                                        @php
                                            $months = [
                                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
                                                4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                                                7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
                                                10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                                            ];
                                            $selectedStartMonth1 = request()->get('start_month1', date('n'));
                                        @endphp
                                        @foreach ($months as $num => $name)
                                            <option value="{{ $num }}" {{ $selectedStartMonth1 == $num ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label>Inicio Año</label>
                                    <select class="form-control" name="start_year1" id="start-year1-select">
                                        @php
                                            $currentYear = date('Y');
                                            $startYear = $currentYear - 5;
                                            $selectedStartYear1 = request()->get('start_year1', $currentYear);
                                        @endphp
                                        @for ($year = $currentYear + 1; $year >= $startYear; $year--)
                                            <option value="{{ $year }}" {{ $selectedStartYear1 == $year ? 'selected' : '' }}>{{ $year }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label>Fin Mes</label>
                                    <select class="form-control" name="end_month1" id="end-month1-select">
                                        @php
                                            $selectedEndMonth1 = request()->get('end_month1', date('n'));
                                        @endphp
                                        @foreach ($months as $num => $name)
                                            <option value="{{ $num }}" {{ $selectedEndMonth1 == $num ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label>Fin Año</label>
                                    <select class="form-control" name="end_year1" id="end-year1-select">
                                        @php
                                            $selectedEndYear1 = request()->get('end_year1', $currentYear);
                                        @endphp
                                        @for ($year = $currentYear + 1; $year >= $startYear; $year--)
                                            <option value="{{ $year }}" {{ $selectedEndYear1 == $year ? 'selected' : '' }}>{{ $year }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-group mb-3 w-100">
                            <button type="submit" class="btn btn-primary w-100">Analizar Periodo</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Period Stats -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="card-title mb-0">
                Periodo: {{ $period1Data['period'] }}
                <small class="text-muted ms-2">Comparado con {{ $previousPeriod1 }}</small>
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card h-100 border-start border-primary border-3">
                        <div class="card-body p-3">
                            <h6 class="text-muted">Total Subscripciones</h6>
                            <h5>{{ number_format($period1Data['total']['total_count']) }}</h5>
                            @if(isset($period1Growth['total_count']))
                                <div class="d-flex align-items-center mt-2">
                                    @if($period1Growth['total_count']['value'] > 0)
                                        <span class="badge bg-success-subtle text-success me-2">+{{ number_format($period1Growth['total_count']['value']) }}</span>
                                        <span class="text-success">+{{ number_format($period1Growth['total_count']['percentage'], 1) }}%</span>
                                    @elseif($period1Growth['total_count']['value'] < 0)
                                        <span class="badge bg-danger-subtle text-danger me-2">{{ number_format($period1Growth['total_count']['value']) }}</span>
                                        <span class="text-danger">{{ number_format($period1Growth['total_count']['percentage'], 1) }}%</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary me-2">0</span>
                                        <span class="text-secondary">0%</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 border-start border-success border-3">
                        <div class="card-body p-3">
                            <h6 class="text-muted">A Cobrar</h6>
                            <h5>{{ number_format($period1Data['total']['to_be_charged']) }}</h5>
                            @if(isset($period1Growth['to_be_charged']))
                                <div class="d-flex align-items-center mt-2">
                                    @if($period1Growth['to_be_charged']['value'] > 0)
                                        <span class="badge bg-success-subtle text-success me-2">+{{ number_format($period1Growth['to_be_charged']['value']) }}</span>
                                        <span class="text-success">+{{ number_format($period1Growth['to_be_charged']['percentage'], 1) }}%</span>
                                    @elseif($period1Growth['to_be_charged']['value'] < 0)
                                        <span class="badge bg-danger-subtle text-danger me-2">{{ number_format($period1Growth['to_be_charged']['value']) }}</span>
                                        <span class="text-danger">{{ number_format($period1Growth['to_be_charged']['percentage'], 1) }}%</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary me-2">0</span>
                                        <span class="text-secondary">0%</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 border-start border-danger border-3">
                        <div class="card-body p-3">
                            <h6 class="text-muted">Cancelaciones</h6>
                            <h5>{{ number_format($period1Data['total']['canceled']) }}</h5>
                            @if(isset($period1Growth['canceled']))
                                <div class="d-flex align-items-center mt-2">
                                    @if($period1Growth['canceled']['value'] > 0)
                                        <span class="badge bg-danger-subtle text-danger me-2">+{{ number_format($period1Growth['canceled']['value']) }}</span>
                                        <span class="text-danger">+{{ number_format($period1Growth['canceled']['percentage'], 1) }}%</span>
                                    @elseif($period1Growth['canceled']['value'] < 0)
                                        <span class="badge bg-success-subtle text-success me-2">{{ number_format($period1Growth['canceled']['value']) }}</span>
                                        <span class="text-success">{{ number_format($period1Growth['canceled']['percentage'], 1) }}%</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary me-2">0</span>
                                        <span class="text-secondary">0%</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 border-start border-info border-3">
                        <div class="card-body p-3">
                            <h6 class="text-muted">Monto Total</h6>
                            <h5>${{ number_format($period1Data['total']['total_amount'], 2) }}</h5>
                            @if(isset($period1Growth['total_amount']))
                                <div class="d-flex align-items-center mt-2">
                                    @if($period1Growth['total_amount']['value'] > 0)
                                        <span class="badge bg-success-subtle text-success me-2">+${{ number_format($period1Growth['total_amount']['value'], 2) }}</span>
                                        <span class="text-success">+{{ number_format($period1Growth['total_amount']['percentage'], 1) }}%</span>
                                    @elseif($period1Growth['total_amount']['value'] < 0)
                                        <span class="badge bg-danger-subtle text-danger me-2">-${{ number_format(abs($period1Growth['total_amount']['value']), 2) }}</span>
                                        <span class="text-danger">{{ number_format($period1Growth['total_amount']['percentage'], 1) }}%</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary me-2">$0.00</span>
                                        <span class="text-secondary">0%</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comparison Charts Section -->
            <div class="row mt-4">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Comparación de Subscripciones</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="subscriptionsComparisonChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Comparación de Montos</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="amountComparisonChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Source Comparison Chart -->
            <div class="row mt-2 mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Comparación por Fuente</h6>
                        </div>
                        <div class="card-body">
                            <div style="height: 400px; overflow-y: auto;">
                                <canvas id="sourceComparisonChart" width="800" height="600"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily breakdown -->
            <div class="table-responsive mt-4">
                <h6 class="mb-3">Desglose Diario</h6>
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Cobros Programados</th>
                            <th>Monto</th>
                            <th>Cancelaciones</th>
                            <th>Fuentes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($period1Data['daily_data']) && count($period1Data['daily_data']) > 0)
                            @foreach($period1Data['daily_data'] as $day => $dayData)
                                <tr>
                                    <td>{{ $dayData['formatted_date'] }}</td>
                                    <td>{{ $dayData['to_be_charged'] }}</td>
                                    <td>${{ number_format($dayData['to_be_charged_amount'], 2) }}</td>
                                    <td>{{ $dayData['canceled'] }}</td>
                                    <td>
                                        @if(count($dayData['sources']) > 0)
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#sources-{{ str_replace('-', '', $day) }}" aria-expanded="false">
                                                Ver fuentes ({{ count($dayData['sources']) }})
                                            </button>
                                            <div class="collapse mt-2" id="sources-{{ str_replace('-', '', $day) }}">
                                                <div class="card card-body p-2">
                                                    <table class="table table-sm mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>Fuente</th>
                                                                <th>A Cobrar</th>
                                                                <th>Monto</th>
                                                                <th>Canceladas</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($dayData['sources'] as $source => $sourceData)
                                                                <tr>
                                                                    <td>{{ $source }}</td>
                                                                    <td>{{ $sourceData['to_be_charged'] }}</td>
                                                                    <td>${{ number_format($sourceData['to_be_charged_amount'], 2) }}</td>
                                                                    <td>{{ $sourceData['canceled'] }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">Ninguna</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="text-center">No hay datos disponibles para este periodo</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Daily Distribution Chart -->
            <div class="row mt-4 mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Distribución Diaria</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="dailyDistributionChart" width="800" height="400"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Source breakdown -->
            <div class="table-responsive mt-4">
                <h6 class="mb-3">Resumen por Fuente</h6>
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Fuente</th>
                            <th>A Cobrar</th>
                            <th>Monto</th>
                            <th>Cancelaciones</th>
                            <th>Cambio vs. Periodo Anterior</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($period1Data['by_source']) && count($period1Data['by_source']) > 0)
                            @foreach($period1Data['by_source'] as $source => $sourceData)
                                <tr>
                                    <td>{{ $source }}</td>
                                    <td>{{ $sourceData['to_be_charged'] }}</td>
                                    <td>${{ number_format($sourceData['to_be_charged_amount'], 2) }}</td>
                                    <td>{{ $sourceData['canceled'] }}</td>
                                    <td>
                                        @if(isset($period1Growth['by_source'][$source]))
                                            <div class="d-flex align-items-center gap-2">
                                                @if($period1Growth['by_source'][$source]['to_be_charged']['value'] > 0)
                                                    <span class="badge bg-success-subtle text-success">+{{ $period1Growth['by_source'][$source]['to_be_charged']['value'] }}</span>
                                                @elseif($period1Growth['by_source'][$source]['to_be_charged']['value'] < 0)
                                                    <span class="badge bg-danger-subtle text-danger">{{ $period1Growth['by_source'][$source]['to_be_charged']['value'] }}</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">0</span>
                                                @endif
                                                
                                                @if($period1Growth['by_source'][$source]['to_be_charged_amount']['value'] > 0)
                                                    <span class="badge bg-success-subtle text-success">+${{ number_format($period1Growth['by_source'][$source]['to_be_charged_amount']['value'], 2) }}</span>
                                                @elseif($period1Growth['by_source'][$source]['to_be_charged_amount']['value'] < 0)
                                                    <span class="badge bg-danger-subtle text-danger">-${{ number_format(abs($period1Growth['by_source'][$source]['to_be_charged_amount']['value']), 2) }}</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">$0.00</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="text-center">No hay datos disponibles por fuente</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
    .title-source {
        font-size: 21px !important;
        padding-bottom: 10px !important;
    }
    .form-check-label {
        position: relative;
        top: -2px;
        left: 5px;
    }
    .label-bold {
        font-weight: bold;
        margin-bottom: 10px;
    }
    .source-section {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .tab-content {
        padding: 20px 0;
    }
    .badge {
        padding: 5px 10px;
    }
    .summary-section {
        margin-bottom: 2rem;
    }
    .source-item {
        border-left: 3px solid #4e73df;
    }
    .bg-success-subtle {
        background-color: rgba(25,135,84,.1) !important;
    }
    .bg-danger-subtle {
        background-color: rgba(220,53,69,.1) !important;
    }
    .bg-secondary-subtle {
        background-color: rgba(108,117,125,.1) !important;
    }
    .chart-container {
        position: relative;
        margin: auto;
        height: 300px;
    }
</style>
@endpush

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
    $(document).ready(function() {
        // Validate that end date is after start date
        function validateDateRange() {
            let startYear = parseInt($('#start-year1-select').val());
            let startMonth = parseInt($('#start-month1-select').val());
            let endYear = parseInt($('#end-year1-select').val());
            let endMonth = parseInt($('#end-month1-select').val());
            
            // Check Period
            if ((endYear < startYear) || (endYear === startYear && endMonth < startMonth)) {
                alert('Error: La fecha de fin del Periodo debe ser posterior a la fecha de inicio');
                return false;
            }
            
            return true;
        }
        
        // Attach validator to form submit
        $('form').on('submit', function(e) {
            if (!validateDateRange()) {
                e.preventDefault();
            }
        });
        
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Prepare chart data
        const currentPeriodLabel = "{{ $period1Data['period'] }}";
        const previousPeriodLabel = "{{ $previousPeriod1 }}";
        
        // Subscriptions comparison chart
        const subscriptionsCtx = document.getElementById('subscriptionsComparisonChart').getContext('2d');
        new Chart(subscriptionsCtx, {
            type: 'bar',
            data: {
                labels: ['Total', 'A Cobrar', 'Cancelaciones'],
                datasets: [{
                    label: 'Periodo Actual',
                    data: [
                        {{ $period1Data['total']['total_count'] }},
                        {{ $period1Data['total']['to_be_charged'] }},
                        {{ $period1Data['total']['canceled'] }}
                    ],
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Periodo Anterior',
                    data: [
                        {{ $period1Data['total']['total_count'] - $period1Growth['total_count']['value'] }},
                        {{ $period1Data['total']['to_be_charged'] - $period1Growth['to_be_charged']['value'] }},
                        {{ $period1Data['total']['canceled'] - $period1Growth['canceled']['value'] }}
                    ],
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Comparación de Subscripciones'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Amount comparison chart
        const amountCtx = document.getElementById('amountComparisonChart').getContext('2d');
        new Chart(amountCtx, {
            type: 'bar',
            data: {
                labels: ['Monto Total'],
                datasets: [{
                    label: currentPeriodLabel,
                    data: [{{ $period1Data['total']['total_amount'] }}],
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: previousPeriodLabel,
                    data: [{{ $period1Data['total']['total_amount'] - $period1Growth['total_amount']['value'] }}],
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Monto ($)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Comparación de Montos'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.parsed.y.toLocaleString(undefined, {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            }
                        }
                    }
                }
            }
        });

        // Source comparison chart data preparation
        const sourceLabels = [];
        const currentSourceData = [];
        const previousSourceData = [];
        
        @if(isset($period1Data['by_source']) && count($period1Data['by_source']) > 0)
            @foreach($period1Data['by_source'] as $source => $sourceData)
                sourceLabels.push("{{ $source }}");
                currentSourceData.push({{ $sourceData['to_be_charged'] }});
                
                @if(isset($period1Growth['by_source'][$source]))
                    previousSourceData.push({{ $sourceData['to_be_charged'] - $period1Growth['by_source'][$source]['to_be_charged']['value'] }});
                @else
                    previousSourceData.push(0);
                @endif
            @endforeach
        @endif
        
        // Source comparison chart
        const sourceCtx = document.getElementById('sourceComparisonChart').getContext('2d');
        new Chart(sourceCtx, {
            type: 'bar',
            data: {
                labels: sourceLabels,
                datasets: [{
                    label: currentPeriodLabel,
                    data: currentSourceData,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: previousPeriodLabel,
                    data: previousSourceData,
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',  // Horizontal bars for better readability of source names
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Comparación por Fuente'
                    }
                }
            }
        });
        
        // Daily distribution chart
        const dailyLabels = [];
        const dailyCharges = [];
        const dailyCanceled = [];
        
        @if(isset($period1Data['daily_data']) && count($period1Data['daily_data']) > 0)
            @foreach($period1Data['daily_data'] as $day => $dayData)
                dailyLabels.push("{{ $dayData['formatted_date'] }}");
                dailyCharges.push({{ $dayData['to_be_charged'] }});
                dailyCanceled.push({{ $dayData['canceled'] }});
            @endforeach
        @endif
        
        const dailyCtx = document.getElementById('dailyDistributionChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dailyLabels,
                datasets: [{
                    label: 'A Cobrar',
                    data: dailyCharges,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.1
                }, {
                    label: 'Cancelaciones',
                    data: dailyCanceled,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Fecha'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Distribución Diaria'
                    }
                }
            }
        });
    });
</script>
@endpush