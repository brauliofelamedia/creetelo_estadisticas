@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Resumen de transacciones</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="#" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Filtros</li>
        </ul>
    </div>

    <div class="row">
        <div class="col-3">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Filtros</h6></div>
                <div class="card-body">
                    <form method="get" action="{{route('filters')}}">
                        <div class="row">
                            <div class="col-12">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group mb-3">
                                            <label class="label-bold">Fecha inicial</label>
                                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date', \Carbon\Carbon::now()->startOfWeek()->format('Y-m-d')) }}">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group mb-3">
                                            <label class="label-bold">Fecha final</label>
                                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date', \Carbon\Carbon::now()->endOfWeek()->format('Y-m-d')) }}">
                                        </div>
                                    </div>
                                </div>
                                <label class="label-bold">Membresías</label>
                                @foreach($sources as $key => $source)
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
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">Filtrar</button>
                    </form>
                    
                </div>
            </div>
        </div>
        <div class="col-9">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Resumen</h6>
                </div>
                <div class="card-body">
                    @if(!$transactions)
                        <p>No hay resultados para el filtro seleccionado</p>
                    @else
                        @foreach($transactions as $transaction)
                            <div class="card summary-card mb-4" style="display: none;">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <h5 class="card-title">Total</h5>
                                            <p class="display-6">{{$transaction['count']}}</p>
                                            <p class="text-muted">Transacciones</p>
                                        </div>
                                        <div class="col-md-3">
                                            <h5 class="card-title">Monto</h5>
                                            <p class="display-6">${{$transaction['amount']}} USD</p>
                                            <p class="text-muted">Total</p>
                                        </div>
                                        <div class="col-md-2">
                                            <h5 class="card-title text-success">Éxito</h5>
                                            <p class="display-6">{{$transaction['succeeded']}}</p>
                                        </div>
                                        <div class="col-md-2">
                                            <h5 class="card-title text-danger">Fallidos</h5>
                                            <p class="display-6">{{$transaction['failed']}}</p>
                                        </div>
                                        <div class="col-md-2">
                                            <h5 class="card-title text-warning">Reembolsos</h5>
                                            <p class="display-6">{{$transaction['refunded']}}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tabla de Fuentes -->
                            <div class="table-responsive" style="margin-top: 0;">
                                <table class="table table-hover table-striped">
                                    <thead class="table-success">
                                        <tr>
                                            <th colspan="6" class="text-center bg-dark text-light" style="text-align: left;">
                                                Fecha: {{ \Carbon\Carbon::parse($transaction['createdAt'])->format('d/m/Y') }}
                                            </th>
                                        </tr>
                                        <tr>
                                            <th>Membresía</th>
                                            <th>Transacciones</th>
                                            <th>Monto</th>
                                            <th>Éxito</th>
                                            <th>Fallidos</th>
                                            <th>Reembolsos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            // Inicializar variables para los totales
                                            $totalCount = 0;
                                            $totalAmount = 0;
                                            $totalSucceeded = 0;
                                            $totalFailed = 0;
                                            $totalRefunded = 0;
                                        @endphp
                                        @foreach($transaction['sources'] as $key => $source)
                                            @php
                                                // Acumular los valores
                                                $totalCount += $source['count'];
                                                $totalAmount += $source['amount'];
                                                $totalSucceeded += $source['succeeded'];
                                                $totalFailed += $source['failed'];
                                                $totalRefunded += $source['refunded'];
                                            @endphp
                                            
                                            <tr class="source-row">
                                                <td>{{$key}}</td>
                                                <td>{{$source['count']}}</td>
                                                <td>${{$source['amount']}} USD</td>
                                                <td class="text-success">{{$source['succeeded']}}</td>
                                                <td class="text-danger">{{$source['failed']}}</td>
                                                <td class="text-warning">{{$source['refunded']}}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-secondary">
                                        <tr>
                                            <th>Total</th>
                                            <th>{{ $totalCount }}</th>
                                            <th>${{ number_format($totalAmount, 0, ',', '.') }} USD</th>
                                            <th class="text-success">{{ $totalSucceeded }}</th>
                                            <th class="text-danger">{{ $totalFailed }}</th>
                                            <th class="text-warning">{{ $totalRefunded }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
<style>
    .table-responsive {
        margin-top: 20px;
    }
    .summary-card {
        border-left: 4px solid #0d6efd;
    }
    .source-row {
        transition: all 0.3s;
    }
    .source-row:hover {
        background-color: #f8f9fa;
    }
    .emoji {
        font-size: 1.2em;
    }
    .form-check-label {
        position: relative;
        top: -2px;
        left: 5px;
    }
    .label-bold {
        font-weight: bold;
        margin-bottom: 5px;
    }
</style>
@endpush