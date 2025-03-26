@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Subscripciones</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="#" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Subscripciones</li>
        </ul>
    </div>

    <div class="row">
        <div class="col-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Filtros</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('filters.subscriptions') }}" method="GET">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="label-bold">Fecha inicial</label>
                                    <input type="date" class="form-control" name="start_date" value="{{($startDate)? $startDate : \Carbon\Carbon::now()->startOfWeek()->format('Y-m-d') }}">
                                </div>
                                <div class="form-group">
                                    <label class="label-bold">Fecha final</label>
                                    <input type="date" class="form-control" name="end_date" value="{{($endDate)? $endDate : \Carbon\Carbon::now()->endOfWeek()->format('Y-m-d') }}">
                                </div>
                                <div class="form-group mb-10">
                                    <label class="label-bold">Membresías</label>
                                    @foreach($allSources as $key => $source)
                                        <div class="form-check mb-2" style="font-size: 0.9rem;">
                                            <input class="form-check-input form-check-input-sm" 
                                                name="sources[]" 
                                                type="checkbox" 
                                                id="source-{{ $key }}" 
                                                value="{{ urlencode($source) }}"
                                                {{ in_array($source, $selectedSources) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="source-{{ $key }}">
                                                    {{ str_replace('- Payment', '', $source) }}
                                                </label>
                                        </div>
                                    @endforeach
                                    <button type="submit" class="btn btn-primary" style="width: 100%;">Resumen de subscripciones</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-9">
            <div class="card mb-20">
                <div class="card-header">
                    <h6 class="card-title mb-0">Resumen de subscripciones</h6>
                </div>
                <div class="card-body">

                    <!-- Estadísticas globales -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-success bg-opacity-10">
                                <div class="card-body">
                                    <h6>Activas</h6>
                                    <h5>{{ $totalStats['active_count'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning bg-opacity-10">
                                <div class="card-body">
                                    <h6>Vencidas</h6>
                                    <h5>{{ $totalStats['incomplete_expired_count'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger bg-opacity-10">
                                <div class="card-body">
                                    <h6>Canceladas</h6>
                                    <h5>{{ $totalStats['canceled_count'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info bg-opacity-10">
                                <div class="card-body">
                                    <h6>Pago atrasado</h6>
                                    <h5>{{ $totalStats['past_due_count'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="card bg-primary bg-opacity-10">
                                <div class="card-body">
                                    <h6>Ingresos totales</h6>
                                    <h5>${{ number_format($totalStats['total_amount'], 2) }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="card bg-danger bg-opacity-10">
                                <div class="card-body">
                                    <h6>Churn Rate (Tasa de cancelación)</h6>
                                    <h5>{{ number_format($totalStats['churn_rate'], 2) }}%</h5>
                                    <small class="text-muted">Porcentaje de clientes perdidos sobre el total</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla por fuente -->
            @foreach($grouped as $sourceName => $sourceData)
                <div class="source-section mb-4">
                    <h5 class="border-bottom pb-2 title-source">{{ $sourceName }}</h5>
                    
                    <!-- Resumen de la fuente -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-0"><strong>Total suscripciones:</strong> {{ $sourceData['summary']['total_count'] }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-0"><strong>Ingresos (solo activas):</strong> ${{ number_format($sourceData['summary']['total_amount'], 2) }} USD</p>
                        </div>
                    </div>

                    <!-- Pestañas para los diferentes estados -->
                    <ul class="nav nav-tabs" role="tablist">
                        @foreach(['active' => 'Activas', 'incomplete_expired' => 'Vencidas', 'canceled' => 'Canceladas', 'past_due' => 'Pago atrasado'] as $status => $label)
                            <li class="nav-item">
                                <a class="nav-link {{ $status === 'active' ? 'active' : '' }}" 
                                   data-bs-toggle="tab" 
                                   href="#{{ $status }}-{{ Str::slug($sourceName) }}">
                                    {{ $label }} ({{ $sourceData[$status]['count'] }})
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content">
                        @foreach(['active' => 'Activa', 'incomplete_expired' => 'Vencida', 'canceled' => 'Cancelada', 'past_due' => 'Pago atrasado'] as $status => $label)
                            <div class="tab-pane fade {{ $status === 'active' ? 'show active' : '' }}" 
                                 id="{{ $status }}-{{ Str::slug($sourceName) }}">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Email</th>
                                                <th>Inicio</th>
                                                <th>Duración (días)</th>
                                                <th>Monto</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sourceData[$status]['subscriptions'] as $subscription)
                                                <tr>
                                                    <td>{{ $subscription->contactName ?? 'N/A' }}</td>
                                                    <td>{{ $subscription->contactEmail ?? 'N/A' }}</td>
                                                    <td>{{ Carbon\Carbon::parse($subscription->subscriptionStartDate)->format('Y-m-d') }}</td>
                                                    <td>{{ number_format($subscription->duration, 0) }}</td>
                                                    <td>${{ number_format($subscription->amount, 2) }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $status === 'active' ? 'success' : ($status === 'incomplete_expired' ? 'warning' : ($status === 'canceled' ? 'danger' : 'info')) }}">
                                                            {{ $label }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
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
</style>
@endpush

@push('js')
<script>
    // Inicializar las pestañas de Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        var tabElms = document.querySelectorAll('a[data-bs-toggle="tab"]');
        tabElms.forEach(function(tabElm) {
            new bootstrap.Tab(tabElm);
        });
    });
</script>
@endpush