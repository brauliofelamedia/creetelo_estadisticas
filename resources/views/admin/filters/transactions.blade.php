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
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Filtros</h6></div>
                <div class="card-body">
                    <form method="get" action="{{route('filters')}}">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group mb-3">
                                    <label class="label-bold">Fecha inicial</label>
                                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date', \Carbon\Carbon::now()->startOfWeek()->format('Y-m-d')) }}">
                                </div>
                                <div class="form-group mb-3">
                                    <label class="label-bold">Fecha final</label>
                                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date', \Carbon\Carbon::now()->endOfWeek()->format('Y-m-d')) }}">
                                </div>
                                
                                <div class="accordion mb-3" id="filtersAccordion">
                                    <!-- Tipos de Fuentes -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="sourceTypesHeader">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sourceTypesCollapse" aria-expanded="false" aria-controls="sourceTypesCollapse">
                                                <span class="label-bold">Tipos de Fuentes</span>
                                            </button>
                                        </h2>
                                        <div id="sourceTypesCollapse" class="accordion-collapse collapse" aria-labelledby="sourceTypesHeader" data-bs-parent="#filtersAccordion">
                                            <div class="accordion-body">
                                                @foreach($sourcesTypes as $key => $type)
                                                    <div class="form-check mb-2" style="font-size: 0.9rem;">
                                                        <input class="form-check-input form-check-input-sm sourceType-filter" 
                                                            name="source_types[]" 
                                                            type="checkbox" 
                                                            id="source-type-{{ $key }}" 
                                                            value="{{ urlencode($type) }}"
                                                            {{ empty($selectedSourceTypes) || in_array($type, $selectedSourceTypes) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="source-type-{{ $key }}">
                                                                {{ $type ?: 'Sin especificar' }}
                                                            </label>
                                                    </div>
                                                @endforeach
                                                <div class="d-flex gap-2 mt-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" style="width:100%;" id="selectAllTypes">Seleccionar</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" style="width:100%;" id="deselectAllTypes">Deseleccionar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Membresías -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="sourcesHeader">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#sourcesCollapse" aria-expanded="true" aria-controls="sourcesCollapse">
                                                <span class="label-bold">Membresías</span>
                                            </button>
                                        </h2>
                                        <div id="sourcesCollapse" class="accordion-collapse collapse show" aria-labelledby="sourcesHeader" data-bs-parent="#filtersAccordion">
                                            <div class="accordion-body">
                                                @foreach($sources as $key => $source)
                                                    <div class="form-check mb-2 source-item" style="font-size: 0.9rem;">
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
                                                <div class="d-flex gap-2 mt-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" style="width:100%;" id="selectAll">Seleccionar</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" style="width:100%;" id="deselectAll">Deseleccionar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width:100%;">Filtrar</button>
                    </form>
                    
                </div>
            </div>
        </div>
        <div class="col-lg-9">
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
                                            <th>Nombre</th>
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
                                                
                                                // Determinar el tipo de transacción
                                                $transactionType = '';
                                                if (stripos($key, 'membership') !== false) {
                                                    $transactionType = 'membership';
                                                } elseif (stripos($key, 'payment_link') !== false) {
                                                    $transactionType = 'payment_link';
                                                } elseif (stripos($key, 'funnel') !== false) {
                                                    $transactionType = 'funnel';
                                                } elseif (stripos($key, 'invoice') !== false) {
                                                    $transactionType = 'invoice';
                                                } elseif (stripos($key, 'manual') !== false) {
                                                    $transactionType = 'manual';
                                                }
                                            @endphp
                                            
                                            <tr class="source-row">
                                                <td class="d-flex align-items-center">
                                                    {{$key}}
                                                    {{$transactionType}}
                                                    @if($transactionType)
                                                        <span class="badge bg-{{ 
                                                            $transactionType == 'membership' ? 'primary' : 
                                                            ($transactionType == 'payment_link' ? 'success' : 
                                                            ($transactionType == 'funnel' ? 'warning' : 
                                                            ($transactionType == 'invoice' ? 'info' : 'secondary'))) 
                                                        }} transaction-type-badge">
                                                            {{ strtoupper($transactionType) }}
                                                        </span>
                                                    @endif
                                                </td>
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
                            <!-- Gráfico de totales -->
                            <div class="chart-container" style="position: relative; height:300px; margin-top: 20px;">
                                <canvas id="transactionChart-{{ $loop->index }}"></canvas>
                            </div>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const ctx = document.getElementById('transactionChart-{{ $loop->index }}').getContext('2d');
                                    new Chart(ctx, {
                                        type: 'bar',
                                        data: {
                                            labels: [
                                                @foreach($transaction['sources'] as $key => $source)
                                                    "{{ $key }}",
                                                @endforeach
                                            ],
                                            datasets: [{
                                                label: 'Total Transacciones',
                                                data: [
                                                    @foreach($transaction['sources'] as $source)
                                                        {{ $source['count'] }},
                                                    @endforeach
                                                ],
                                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                                borderColor: 'rgba(54, 162, 235, 1)',
                                                borderWidth: 1
                                            }, {
                                                label: 'Exitosas',
                                                data: [
                                                    @foreach($transaction['sources'] as $source)
                                                        {{ $source['succeeded'] }},
                                                    @endforeach
                                                ],
                                                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                                                borderColor: 'rgba(75, 192, 192, 1)',
                                                borderWidth: 1
                                            }, {
                                                label: 'Fallidas',
                                                data: [
                                                    @foreach($transaction['sources'] as $source)
                                                        {{ $source['failed'] }},
                                                    @endforeach
                                                ],
                                                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                                                borderColor: 'rgba(255, 99, 132, 1)',
                                                borderWidth: 1
                                            }, {
                                                label: 'Reembolsos',
                                                data: [
                                                    @foreach($transaction['sources'] as $source)
                                                        {{ $source['refunded'] }},
                                                    @endforeach
                                                ],
                                                backgroundColor: 'rgba(255, 206, 86, 0.5)',
                                                borderColor: 'rgba(255, 206, 86, 1)',
                                                borderWidth: 1
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            scales: {
                                                y: {
                                                    beginAtZero: true
                                                }
                                            },
                                            plugins: {
                                                title: {
                                                    display: true,
                                                    text: 'Resumen de Transacciones por Membresía - {{ \Carbon\Carbon::parse($transaction['createdAt'])->format('d/m/Y') }}'
                                                }
                                            }
                                        }
                                    });
                                });
                            </script>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Para membresías
        $('#selectAll').click(function() {
            $('input[name="sources[]"]').prop('checked', true);
        });
        $('#deselectAll').click(function() {
            $('input[name="sources[]"]').prop('checked', false);
        });
        // Para tipos de fuentes
        $('#selectAllTypes').click(function() {
            $('input[name="source_types[]"]').prop('checked', true);
        });
        $('#deselectAllTypes').click(function() {
            $('input[name="source_types[]"]').prop('checked', false);
        });
        // Filtrar membresías según los tipos seleccionados
        $('.sourceType-filter').change(function() {
            // Esta funcionalidad requeriría datos adicionales sobre qué membresía pertenece a qué tipo
            // Para implementarla completamente, necesitaríamos agregar esta relación en el controlador
        });
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar los cambios en los filtros de tipo de fuente
    const sourceTypeCheckboxes = document.querySelectorAll('.source-type-filter');
    const sourceGroups = document.querySelectorAll('.source-group');
    const sourceFilters = document.querySelectorAll('.source-filter');

    // Función para actualizar la visibilidad de los grupos de fuentes
    function updateSourceGroupsVisibility() {
        const selectedTypes = Array.from(sourceTypeCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => decodeURIComponent(cb.value));

        // Si no hay tipos seleccionados, mostrar todos
        if (selectedTypes.length === 0) {
            sourceGroups.forEach(group => {
                group.style.display = 'block';
            });
            return;
        }

        // Mostrar solo los grupos que coinciden con los tipos seleccionados
        sourceGroups.forEach(group => {
            const groupType = decodeURIComponent(group.dataset.sourceType);
            if (selectedTypes.includes(groupType)) {
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
                
                // Desmarcar las fuentes de tipos no seleccionados
                group.querySelectorAll('.source-filter').forEach(cb => {
                    cb.checked = false;
                });
            }
        });
    }

    // Aplicar la lógica de filtrado al cargar la página
    updateSourceGroupsVisibility();

    // Actualizar al cambiar los tipos de fuente
    sourceTypeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSourceGroupsVisibility);
    });
});
</script>
@endpush
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
    .accordion-button:not(.collapsed) {
        background-color: #f8f9fa;
        color: #000;
    }
    .accordion-button:focus {
        box-shadow: none;
    }
    .transaction-type-badge {
        font-size: 0.7rem;
        padding: 4px 8px;
        display: inline-block;
        font-weight: 600;
        letter-spacing: 0.5px;
        vertical-align: middle;
    }
</style>

@endpush