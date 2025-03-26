@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Proyección de Ventas</h6>
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
                    <h6 class="mb-0">Filtros</h6>
                </div>
                <div class="card-body">
                    <form method="get" action="{{route('filters.day')}}">
                        <div class="form-group mb-3">
                            <label>Fecha Inicial</label>
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date', \Carbon\Carbon::now()->startOfWeek()->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group mb-3">
                            <label>Fecha Final</label>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date', \Carbon\Carbon::now()->endOfWeek()->format('Y-m-d')) }}">
                        </div>
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Resultados</h6>
                </div>
                <div class="card-body">
                    <canvas id="transactionsChart"></canvas>
                    
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                        const data = @json($transactions);
                        const dates = data.map(item => moment(item.createdAt).format('DD-MM-YYYY'));
                        const amounts = data.map(item => item.amount);

                        new Chart(document.getElementById('transactionsChart'), {
                            type: 'bar',
                            data: {
                                labels: dates,
                                datasets: [{
                                    label: 'Ingresos USD',
                                    data: amounts,
                                    backgroundColor: 'rgb(75, 192, 75)',
                                    borderColor: 'rgb(75, 192, 75)',
                                    tension: 0.1
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return '$' + value.toFixed(2);
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection