@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Todos las subscripciones</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="#" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Subscripciones</li>
        </ul>
    </div>

    <div class="card basic-data-table">
        <div class="card-body">
            <form class="row g-3 align-items-center mb-12 pt-10" action="" method="GET" id="filterForm">
                <div class="col-auto">
                    <div class="dropdown tag-filter-dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="statusFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="selected-status-count">Estatus</span>
                        </button>
                        <div class="dropdown-menu p-3" aria-labelledby="statusFilterDropdown" style="min-width: 250px;">
                            <div class="status-checkboxes">
                                <div class="form-check mb-2">
                                    <input class="form-check-input status-checkbox-all" type="checkbox" name="status[]" value="*" id="statusAll" {{ in_array('*', (array)request('status')) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="statusAll">Todos los estatus</label>
                                </div>
                                <div class="dropdown-divider"></div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input status-checkbox" type="checkbox" name="status[]" value="active" id="status-active" {{ in_array('active', $status ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="status-active">Activo</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input status-checkbox" type="checkbox" name="status[]" value="canceled" id="status-canceled" {{ in_array('canceled', $status ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="status-canceled">Cancelado</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input status-checkbox" type="checkbox" name="status[]" value="incomplete_expired" id="status-incomplete-expired" {{ in_array('incomplete_expired', $status ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="status-incomplete-expired">Incompleto</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input status-checkbox" type="checkbox" name="status[]" value="past_due" id="status-past-due" {{ in_array('past_due', $status ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="status-past-due">Vencidos</label>
                                </div>
                            </div>
                            <div class="dropdown-divider mt-2"></div>
                            <div class="d-flex justify-content-between mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary clear-status-btn">Limpiar</button>
                                <button type="button" class="btn btn-sm btn-primary apply-status-btn">Aplicar</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Combined Filter Button -->
                <div class="col-auto">
                    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#combinedFilterSidebar">
                    <i class="fas fa-filter"></i> Filtros
                    </button>
                </div>

                <!-- Export Button -->
                <div class="col-auto">
                    <a href="{{ route('subscriptions.export', request()->query()) }}" class="btn btn-success">
                        <i class="fas fa-file-export"></i> Exportar
                    </a>
                </div>
        
                <!-- Combined Sidebar -->
                <div class="offcanvas offcanvas-end" tabindex="-1" id="combinedFilterSidebar" style="max-width: 450px;">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title">Filtros de búsqueda</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <!-- Provider Type Filters -->
                        <div class="mb-4">
                            <h6 class="mb-3">Proveedor de pago</h6>
                            <div class="d-flex flex-column w-100">
                                <div class="form-check mb-2" style="font-size: 0.9rem;">
                                    <input name="provider_type[]" class="form-check-input form-check-input-sm" type="checkbox" value="stripe" id="provider-type-stripe"
                                        {{ in_array('stripe', $provider_type ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="provider-type-stripe">Stripe</label>
                                </div>
                                <div class="form-check mb-2" style="font-size: 0.9rem;">
                                    <input name="provider_type[]" class="form-check-input form-check-input-sm" type="checkbox" value="paypal" id="provider-type-paypal"
                                        {{ in_array('paypal', $provider_type ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="provider-type-paypal">PayPal</label>
                                </div>
                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll('provider_type[]')">Seleccionar todo</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll('provider_type[]')">Deseleccionar todo</button>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-3">
                        
                        <!-- Source Type Filters -->
                        <div class="mb-4">
                            <h6 class="mb-3">Tipo de fuente</h6>
                            <div class="d-flex flex-column w-100">
                                @foreach($sourceTypes as $type)
                                    <div class="form-check mb-2" style="font-size: 0.9rem;">
                                        <input name="source_type[]" class="form-check-input form-check-input-sm" type="checkbox" value="{{ $type }}" id="source-type-{{ $loop->index }}"
                                            {{ in_array($type, $source_type ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="source-type-{{ $loop->index }}">{{ $type }}</label>
                                    </div>
                                @endforeach
                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll('source_type[]')">Seleccionar todo</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll('source_type[]')">Deseleccionar todo</button>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-3">
                        
                        <!-- Tags Filters -->
                        <div class="mb-4">
                            <h6 class="mb-3">Etiquetas</h6>
                            <div class="d-flex flex-column w-100">
                                @forelse($availableTags as $tag)
                                    <div class="form-check mb-2" style="font-size: 0.9rem;">
                                        <input name="tags[]" class="form-check-input form-check-input-sm" type="checkbox" 
                                               value="{{ $tag }}" id="tag-{{ $loop->index }}"
                                               {{ in_array($tag, $selectedTags ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="tag-{{ $loop->index }}">{{ $tag }}</label>
                                    </div>
                                @empty
                                    <p class="text-muted">No hay etiquetas disponibles.</p>
                                @endforelse
                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll('tags[]')">Seleccionar todo</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll('tags[]')">Deseleccionar todo</button>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-3">
                        
                        <!-- Membership Filters -->
                        <div>
                            <h6 class="mb-3">Tipo de fuente</h6>
                            <div class="d-flex flex-column w-100">
                                @if(empty($filteredSourceNames))
                                    <p class="text-muted">Seleccione al menos un tipo de fuente para ver las membresías disponibles.</p>
                                @else
                                    @foreach($filteredSourceNames as $sourceName)
                                        <div class="form-check mb-2" style="font-size: 0.9rem;">
                                            <input name="source[]" class="form-check-input form-check-input-sm" type="checkbox" value="{{ $sourceName }}" id="source-{{ $loop->index }}"
                                                {{ in_array($sourceName, $source ?? []) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="source-{{ $loop->index }}">{{ str_replace('- Payment', '', $sourceName) }}</label>
                                        </div>
                                    @endforeach
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll('source[]')">Seleccionar todo</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll('source[]')">Deseleccionar todo</button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
        
                <div class="col-auto">
                    <input type="date" name="startDate" class="form-control" placeholder="Fecha inicial" value="{{ $startDate ?? '' }}">
                </div>
                <div class="col-auto">
                    <input type="date" name="endDate" class="form-control" placeholder="Fecha final" value="{{ $endDate ?? '' }}">
                </div>
                <div class="col-auto ms-auto">
                    <input type="search" name="search" id="search" class="form-control" placeholder="Buscar..." value="{{ $search ?? '' }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-success">Aplicar filtros</button>
                </div>
            </form>
        
            <div class="table-responsive">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th class="d-none d-md-table-cell">#</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th class="d-none d-md-table-cell">Tipo</th>
                            <th>Membresía</th>
                            <th>Fecha cancelación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($noResultsMessage) && $noResultsMessage)
                            <tr>
                                <td colspan="6">
                                    <div class="p-4 text-center text-muted">
                                        {{ $noResultsMessage }}
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($subscriptions as $subscription)
                            <tr>
                                <td class="d-none d-md-table-cell">{{ $subscription->id }}</td>
                                <td>{{ $subscription->contact->fullname }}</td>
                                <td>{{ $subscription->provider_type }}</td>
                                <td>$ {{ number_format($subscription->amount) }} USD</td>
                                <td>{{ str_replace('- Payment', '', $subscription->entity_resource_name) }}</td>
                                <td>
                                    @if($subscription->status === 'active')
                                        <span class="badge bg-success text-white">Activo</span>
                                    @elseif($subscription->status === 'canceled')
                                        <span class="badge bg-danger text-white">Cancelado</span>
                                    @elseif($subscription->status === 'incomplete_expired')
                                        <span class="badge bg-warning text-dark">Incompleto / expirado</span>
                                    @else
                                        <span class="badge bg-secondary text-white">{{ $subscription->status }}</span>
                                    @endif
                                </td>
                                <td class="d-none d-md-table-cell">{{ \Carbon\Carbon::parse($subscription->cancelled_at)->format('d-m-Y h:s') }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        @if($subscription->contact->phone)
                                            <a href="https://api.whatsapp.com/send?phone={{ preg_replace('/[^0-9]/', '', $subscription->contact->phone) }}" 
                                                target="_blank" class="btn btn-sm btn-success" title="Contactar por WhatsApp">
                                                <iconify-icon icon="bi:whatsapp"></iconify-icon>
                                            </a>
                                        @endif
                                        @if($subscription->contact->email)
                                            <a target="_blank" href="mailto:{{ $subscription->contact->email }}" 
                                                class="btn btn-sm btn-primary" title="Enviar correo">
                                                <iconify-icon icon="mdi:email"></iconify-icon>
                                            </a>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="showDateSelectionModal({{ $subscription->id }})"
                                                title="Cancelar subscripción">
                                            <iconify-icon icon="mdi:calendar"></iconify-icon>
                                        </button>
                                        <!-- Modal para selección de fecha -->
                                        <div class="modal fade" id="cancelModal{{$subscription->id}}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Seleccionar fecha de cancelación</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Fecha de cancelación</label>
                                                            <input type="date" id="cancellation_date_{{$subscription->id}}" class="form-control" value="{{ date('Y-m-d') }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                        <button type="button" class="btn btn-success" onclick="confirmCancelSubscription({{ $subscription->id }})">Continuar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Formulario oculto para enviar la cancelación -->
                                        <form id="cancelForm{{$subscription->id}}" action="{{ route('subscriptions.change') }}" method="POST" style="display: none;">
                                            @csrf
                                            <input type="hidden" name="contact_id" value="{{ $subscription->contact->contact_id }}">
                                            <input type="hidden" name="cancellation_date" id="cancellation_date_hidden_{{$subscription->id}}">
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="mt-3 mb-3 d-flex justify-content-center">
                {{ $subscriptions->links('pagination::bootstrap-4') }}
            </div>
            
            <div class="table-responsive mt-4">
                <table class="table table-sm bordered-table">
                    <thead>
                        <tr>
                            <th>Transacciones totales exitosas</th>
                            <th>Total de registros filtrados</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="h5">$ {{ number_format($totalAmount) }} USD</td>
                            <td class="h5">{{ $subscriptions->total() }} registros</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
    .form-check-label {
        top: -4px;
        position: relative;
        left: 5px;
    }
    .ts-wrapper {
        min-width: 250px;
    }
    .ts-control {
        border-radius: 4px !important;
    }
    .ts-dropdown {
        border-radius: 4px !important;
    }

    .offcanvas,.offcanvas-backdrop {
        margin-top: 0;
    }

    .offcanvas h6 {
        font-size: 18px !important;
    }
</style>
@endpush

@push('scripts')
<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function showDateSelectionModal(subscriptionId) {
        // Mostrar primero el modal de selección de fecha
        $(`#cancelModal${subscriptionId}`).modal('show');
    }
    
    function confirmCancelSubscription(subscriptionId) {
        // Obtener la fecha seleccionada
        const selectedDate = document.getElementById(`cancellation_date_${subscriptionId}`).value;
        
        // Cerrar el modal de fecha
        $(`#cancelModal${subscriptionId}`).modal('hide');
        
        // Configurar el valor en el formulario oculto
        document.getElementById(`cancellation_date_hidden_${subscriptionId}`).value = selectedDate;
        
        // Mostrar SweetAlert para confirmar
        Swal.fire({
            title: 'Cancelar subscripción',
            text: "Esto no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No, cancelar',
            customClass: {
                title: 'fs-5'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Enviar el formulario si se confirma
                document.getElementById(`cancelForm${subscriptionId}`).submit();
            }
        });
    }

    function selectAll(name) {
        const checkboxes = document.querySelectorAll(`input[name="${name}"]`);
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
            // Disparar evento change para activar cualquier listener
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }
    
    function deselectAll(name) {
        const checkboxes = document.querySelectorAll(`input[name="${name}"]`);
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            // Disparar evento change para activar cualquier listener
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    // Asegurarse que los checkboxes mantengan su estado después de la selección
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxGroups = ['provider_type[]', 'source_type[]', 'source[]', 'tags[]'];
        
        checkboxGroups.forEach(group => {
            const checkboxes = document.querySelectorAll(`input[name="${group}"]`);
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    // Asegurarse que el estado del checkbox se mantenga
                    this.checked = this.checked;
                });
            });
        });

        // Make sure select for perPage is within the form
        const perPageSelect = document.querySelector('select[name="perPage"]');
        if (perPageSelect && !perPageSelect.form) {
            document.getElementById('filterForm').appendChild(perPageSelect.parentNode.cloneNode(true));
            perPageSelect.parentNode.style.display = 'none';
        }

        // Set up apply-status-btn click handler
        document.querySelector('.apply-status-btn').addEventListener('click', function() {
            document.getElementById('filterForm').submit();
        });

        // Optional: handle the clear button too
        document.querySelector('.clear-status-btn').addEventListener('click', function() {
            document.querySelectorAll('.status-checkbox, .status-checkbox-all').forEach(checkbox => {
                checkbox.checked = false;
            });
        });
    });
</script>
@endpush