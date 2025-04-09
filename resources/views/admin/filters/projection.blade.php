@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Membresías Activas del Mes</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="#" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Membresías del Mes</li>
        </ul>
    </div>

    <div class="row">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Filtros</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('filters.projection') }}" method="GET" id="filterForm">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-10">
                                    <label class="label-bold">Período de vencimiento</label>
                                    <select name="month_period" class="form-control" id="monthPeriod">
                                        <option value="1" {{ $monthPeriod == 1 ? 'selected' : '' }}>Este mes</option>
                                        <option value="2" {{ $monthPeriod == 2 ? 'selected' : '' }}>Próximos 2 meses</option>
                                        <option value="3" {{ $monthPeriod == 3 ? 'selected' : '' }}>Próximos 3 meses</option>
                                        <option value="6" {{ $monthPeriod == 6 ? 'selected' : '' }}>Próximos 6 meses</option>
                                    </select>
                                </div>
                                
                                <!-- Filter for current month charges -->
                                <div class="form-group mb-10">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="currentMonthChargesSwitch" 
                                               name="current_month_charges" 
                                               value="1" 
                                               {{ $currentMonthCharges ? 'checked' : '' }}>
                                        <label class="form-check-label" for="currentMonthChargesSwitch">Por cobrar este mes</label>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        Mostrar membresías que se cobrarán este mes
                                    </small>
                                </div>
                                
                                <div style="display: none;" class="form-group mb-10">
                                    <input type="hidden" id="simulatedDataInput" name="use_simulated" value="{{ $monthPeriod > 1 ? '1' : ($useSimulatedData ? '1' : '0') }}">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="simulatedDataSwitch" 
                                               name="use_simulated" value="1"
                                               {{ $useSimulatedData ? 'checked' : '' }}>
                                        <label class="form-check-label" for="simulatedDataSwitch">Usar proyección futura</label>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        Simular renovaciones futuras basadas en datos históricos
                                    </small>
                                </div>
                                <hr style="margin:10px 0;">
                                <div class="form-group mb-10">
                                    <input type="hidden" id="simulatedDataInput" name="use_simulated" value="{{ $monthPeriod > 1 ? '1' : ($useSimulatedData ? '1' : '0') }}">
                                    <label class="label-bold">Membresías</label>
                                    <div id="sourcesContainer">
                                        @foreach($sources as $key => $source)
                                            <div class="form-check mb-2" style="font-size: 0.9rem;">
                                                <input class="form-check-input form-check-input-sm source-checkbox" 
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
                                    <div class="d-flex gap-2 mb-3">
                                        <button type="button" class="btn btn-sm btn-outline-primary" style="width:100%;" id="selectAll">Seleccionar</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" style="width:100%;" id="deselectAll">Deseleccionar</button>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary" style="width: 75%;">Aplicar Filtros</button>
                                        <button type="button" id="clearFilters" class="btn btn-outline-danger" style="width: 25%;" title="Limpiar todos los filtros">
                                            <iconify-icon icon="solar:trash-bin-minimalistic-outline"></iconify-icon>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">Membresías Activas - {{ now()->format('F Y') }}</h6>
                    <div>
                        @if((isset($useSimulatedData) && $useSimulatedData) || $monthPeriod > 1)
                            <span class="badge bg-info me-2">
                                Proyección Futura Activada
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($expiringData) && count($expiringData) > 0)
                        <div class="mb-4">
                            <div class="row">
                                @php
                                    $totalSubscriptions = 0;
                                    $totalAmount = 0;
                                    foreach($expiringData as $monthData) {
                                        $totalSubscriptions += $monthData['count'];
                                        $totalAmount += $monthData['total_amount'];
                                    }
                                    
                                    $avgAmount = $totalSubscriptions > 0 ? $totalAmount / $totalSubscriptions : 0;
                                @endphp
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="mb-0">{{ count($expiringData) }}</h5>
                                            <p class="text-muted">Meses con Vencimientos</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="mb-0">{{ $totalSubscriptions }}</h5>
                                            <p class="text-muted">Total Membresías</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="mb-0">${{ number_format($totalAmount, 2) }}</h5>
                                            <p class="text-muted">Valor Total</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="mb-0">${{ number_format($avgAmount, 2) }}</h5>
                                            <p class="text-muted">Valor Promedio</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @foreach($expiringData as $monthData)
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0">{{ $monthData['month_name'] }} - {{ $monthData['count'] }} suscripciones</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Día</th>
                                                    <th>Suscripciones</th>
                                                    <th>Monto Total</th>
                                                    <th>Detalles</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    // Group subscriptions by day of expiration
                                                    $groupedByDay = collect($monthData['subscriptions'])->groupBy(function($sub) {
                                                        return Carbon\Carbon::parse($sub->end_date)->format('d');
                                                    })->sortKeys();
                                                @endphp
                                                @foreach($groupedByDay as $day => $daySubscriptions)
                                                <tr>
                                                    <td>{{ $day }}</td>
                                                    <td>{{ $daySubscriptions->count() }}</td>
                                                    <td>${{ number_format($daySubscriptions->sum('amount'), 2) }}</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary toggle-details" 
                                                                data-bs-toggle="collapse" 
                                                                data-bs-target="#day-details-{{ $monthData['month'] }}-{{ $day }}" 
                                                                aria-expanded="false">
                                                            <iconify-icon icon="solar:alt-arrow-down-outline"></iconify-icon>
                                                            Detalles
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="collapse" id="day-details-{{ $monthData['month'] }}-{{ $day }}">
                                                    <td colspan="4" class="p-0">
                                                        <div class="p-3 bg-light">
                                                            <table class="table table-sm">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Cliente</th>
                                                                        <th>Membresía</th>
                                                                        <th>Estado</th>
                                                                        <th>Inicio</th>
                                                                        <th>Vencimiento</th>
                                                                        <th>Monto</th>
                                                                        <th>Pago</th>
                                                                        @if(isset($useSimulatedData) && $useSimulatedData)
                                                                        <th>Tipo</th>
                                                                        @endif
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($daySubscriptions as $subscription)
                                                                    <tr>
                                                                        <td>
                                                                            @if(isset($subscription->contact))
                                                                                <div>{{ $subscription->contact->fullname ?? 'N/A' }}</div>
                                                                                <small class="text-muted">{{ $subscription->contact->email ?? 'N/A' }}</small>
                                                                            @else
                                                                                <span>N/A</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>{{ str_replace('- Payment', '', $subscription->entity_resource_name) }}</td>
                                                                        <td>
                                                                            <span class="badge {{ $subscription->status == 'active' ? 'bg-success' : 'bg-warning' }}">
                                                                                {{ ucfirst($subscription->status) }}
                                                                            </span>
                                                                        </td>
                                                                        <td>{{ Carbon\Carbon::parse($subscription->start_date)->format('d/m/Y') }}</td>
                                                                        <td>{{ Carbon\Carbon::parse($subscription->end_date)->format('d/m/Y') }}</td>
                                                                        <td>${{ number_format($subscription->amount, 2) }}</td>
                                                                        <td>
                                                                            @php
                                                                                $today = Carbon\Carbon::now();
                                                                                $endDate = Carbon\Carbon::parse($subscription->end_date);
                                                                                $isCharged = isset($subscription->last_payment_date) && 
                                                                                            Carbon\Carbon::parse($subscription->last_payment_date)->month == $today->month &&
                                                                                            Carbon\Carbon::parse($subscription->last_payment_date)->year == $today->year;
                                                                            @endphp
                                                                            @if($isCharged)
                                                                                <span class="badge bg-success">Cobrado</span>
                                                                            @elseif($endDate < $today)
                                                                                <span class="badge bg-danger">Vencido</span>
                                                                            @else
                                                                                <span class="badge bg-warning">Pendiente</span>
                                                                            @endif
                                                                        </td>
                                                                        @if(isset($useSimulatedData) && $useSimulatedData)
                                                                        <td>
                                                                            @if(isset($subscription->is_simulated) && $subscription->is_simulated)
                                                                                <span class="badge bg-info">Proyectado</span>
                                                                            @else
                                                                                <span class="badge bg-secondary">Real</span>
                                                                            @endif
                                                                        </td>
                                                                        @endif
                                                                    </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-info">
                            No se encontraron suscripciones que venzan en el período seleccionado.
                        </div>
                    @endif

                    <!-- New Monthly Membership Metrics Section -->
                    @if(isset($expiringData) && count($expiringData) > 0)
                        @php
                            // Get current month and year
                            $currentMonth = Carbon\Carbon::now()->format('Y-m');
                            
                            // Find current month data if exists
                            $currentMonthData = collect($expiringData)->firstWhere('month', $currentMonth);
                            
                            // Initialize counters
                            $totalMemberships = 0;
                            $chargedMemberships = 0;
                            $pendingMemberships = 0;
                            $canceledMemberships = 0;
                            
                            if ($currentMonthData) {
                                // Calculate totals for current month
                                $currentSubscriptions = collect($currentMonthData['subscriptions']);
                                $totalMemberships = $currentSubscriptions->count();
                                
                                // Count memberships already charged this month
                                $chargedMemberships = $currentSubscriptions->filter(function($sub) {
                                    $today = Carbon\Carbon::now();
                                    return isset($sub->last_payment_date) && 
                                           Carbon\Carbon::parse($sub->last_payment_date)->month == $today->month &&
                                           Carbon\Carbon::parse($sub->last_payment_date)->year == $today->year;
                                })->count();
                                
                                // Count memberships with status canceled
                                $canceledMemberships = $currentSubscriptions->where('status', 'canceled')->count();
                                
                                // Calculate pending memberships
                                $pendingMemberships = $totalMemberships - $chargedMemberships - $canceledMemberships;
                                
                                // Calculate cancellation rate
                                $cancellationRate = $totalMemberships > 0 ? ($canceledMemberships / $totalMemberships) * 100 : 0;
                            }
                        @endphp
                        
                        <div class="card mt-4">
                            <div class="card-header bg-primary text-white">
                                <h6 class="card-title mb-0">Métricas de Membresías - {{ Carbon\Carbon::now()->format('F Y') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="card border-primary mb-3">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">{{ $totalMemberships }}</h5>
                                                <p class="card-text text-muted">Total Membresías</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card border-success mb-3">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">{{ $chargedMemberships }}</h5>
                                                <p class="card-text text-muted">Ya Cobradas</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card border-warning mb-3">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">{{ $pendingMemberships }}</h5>
                                                <p class="card-text text-muted">Pendientes de Cobro</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card border-danger mb-3">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">{{ $canceledMemberships }}</h5>
                                                <p class="card-text text-muted">Canceladas</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <strong>Tasa de Cancelación:</strong> {{ number_format(isset($cancellationRate) ? $cancellationRate : 0, 2) }}%
                                            <small class="d-block mt-2">
                                                La tasa de cancelación se calcula como el número de membresías canceladas dividido por el total de membresías para el mes actual.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <!-- End of Monthly Membership Metrics Section -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Manejar el cambio en el período mensual
        $('#monthPeriod').change(function() {
            // Automatically set use_simulated to true when period > 1 month
            const selectedPeriod = parseInt($(this).val());
            $('#simulatedDataInput').val(selectedPeriod > 1 ? '1' : '0');
            $('#filterForm').submit();
        });

        // Handle current month charges switch
        $('#currentMonthChargesSwitch').change(function() {
            // Submit the form when this switch changes
            $('#filterForm').submit();
        });

        // Handle simulate current month switch
        $('#simulateCurrentMonthSwitch').change(function() {
            // Submit the form when this switch changes
            $('#filterForm').submit();
        });

        // MODIFICADO: Verificar en cada carga si el checkbox debería estar marcado (por default o por parámetro de URL)
        if ($('#currentMonthChargesSwitch').length) {
            // Obtener el valor desde la URL si existe
            const urlParams = new URLSearchParams(window.location.search);
            const paramValue = urlParams.get('current_month_charges');
            
            // Si no hay valor explícito en la URL o si es "1", marcar el checkbox
            if (!paramValue || paramValue === "1") {
                $('#currentMonthChargesSwitch').prop('checked', true);
            } else {
                $('#currentMonthChargesSwitch').prop('checked', false);
            }
            
            // Asegurar que el estado del checkbox se incluya al enviar el formulario incluso si está desmarcado
            $('#filterForm').on('submit', function() {
                if (!$('#currentMonthChargesSwitch').is(':checked')) {
                    // Si está desmarcado, agregar un campo oculto con valor "0"
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'current_month_charges',
                        value: '0'
                    }).appendTo('#filterForm');
                }
            });
        }

        // Funcionalidad para select/deselect de fuentes
        $('#selectAll').click(function() {
            $('.source-checkbox').prop('checked', true);
        });
        
        $('#deselectAll').click(function() {
            $('.source-checkbox').prop('checked', false);
        });

        // Nuevo: Botón para limpiar todos los filtros sin parámetros
        $('#clearFilters').click(function() {
            // Restablecer todos los controles del formulario
            $('#monthPeriod').val('1');
            $('#simulatedDataInput').val('0');
            $('#currentMonthChargesSwitch').prop('checked', true);
            $('.source-checkbox').prop('checked', false);
            // Redireccionar a la URL base sin parámetros
            window.location.href = "{{ route('filters.projection') }}";
        });

        // Improved toggle details button behavior
        $('.toggle-details').on('click', function() {
            const targetId = $(this).data('bs-target');
            const isExpanded = $(targetId).hasClass('show');
            // Update this specific button's text and icon
            if (isExpanded) {
                $(this).html('<iconify-icon icon="solar:alt-arrow-down-outline"></iconify-icon> Ver detalles');
            } else {
                $(this).html('<iconify-icon icon="solar:alt-arrow-up-outline"></iconify-icon> Ocultar detalles');
            }
        });

        // Ensure collapse events properly update button state
        $('.collapse').on('hidden.bs.collapse', function() {
            const targetId = '#' + $(this).attr('id');
            $('[data-bs-target="' + targetId + '"]').html('<iconify-icon icon="solar:alt-arrow-down-outline"></iconify-icon> Ver detalles');
        });

        $('.collapse').on('shown.bs.collapse', function() {
            const targetId = '#' + $(this).attr('id');
            $('[data-bs-target="' + targetId + '"]').html('<iconify-icon icon="solar:alt-arrow-up-outline"></iconify-icon> Ocultar detalles');
        });
    });
</script>
@endpush

@push('css')
<style>
    .form-check-label {
        position: relative;
        top: -2px;
        left: 5px;
    }
    .label-bold {
        font-weight: bold;
        margin-bottom: 10px;
    }
    .checkbox-list {
        border-left: 4px solid #0d6efd;
        padding: 10px;
    }
    .subscription-amount {
        font-weight: bold;
        color: #198754;
    }
    .subscription-count {
        color: #0d6efd;
        font-weight: bold;
    }
    .month-summary {
        border-left: 4px solid #0d6efd;
        background-color: #f8f9fa;
        padding: 10px;
        margin-bottom: 10px;
    }
    .table-sm {
        font-size: 0.875rem;
    }
    .toggle-details {
        white-space: nowrap;
    }
    .collapse .table {
        margin-bottom: 0;
    }
</style>
@endpush