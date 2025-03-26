@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Comparar por día</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="#" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Proyección</li>
        </ul>
    </div>

    <div class="row">
        <div class="col-4">
            <div class="card">
                <div class="card-header">
                    <h5>Filtros</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="form-group mb-3">
                            <label>Fecha Inicial</label>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                        <div class="form-group mb-3">
                            <label>Fecha Final</label>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-8">
            <div class="card">
                <div class="card-header">
                    <h5>Resultados</h5>
                </div>
                <div class="card-body">
                    @if(request('start_date') && request('end_date'))
                        <canvas id="transactionsChart"></canvas>

                        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const ctx = document.getElementById('transactionsChart');
                                
                                const firstMonth = {!! json_encode($firstMonthData ?? []) !!};
                                const secondMonth = {!! json_encode($secondMonthData ?? []) !!};

                                new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: Array.from({length: 31}, (_, i) => i + 1), // Days 1-31
                                        datasets: [{
                                            label: 'Primer Mes',
                                            data: firstMonth,
                                            borderColor: 'rgb(75, 192, 192)',
                                            tension: 0.1
                                        },
                                        {
                                            label: 'Segundo Mes',
                                            data: secondMonth,
                                            borderColor: 'rgb(255, 99, 132)',
                                            tension: 0.1
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        plugins: {
                                            title: {
                                                display: true,
                                                text: 'Comparación de Transacciones por Mes'
                                            }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true
                                            }
                                        }
                                    }
                                });
                            });
                        </script>
                    @else
                        <div class="alert alert-info">
                            Seleccione un rango de fechas para ver la comparación
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@endpush