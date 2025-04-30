@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Todas las transacciones</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.html" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Transacciones</li>
        </ul>
    </div>

    <div class="card basic-data-table">
        @php
            $updating = file_exists(storage_path('app/transactions.json.temp'));
        @endphp
      <div class="card-body">
            @if($updating)
                <div class="alert alert-warning">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    Estamos actualizando las transacciones, por favor espere...
                </div>
            @endif
            <form id="transactionFilterForm" class="row g-3 align-items-center mb-12 pt-10" method="GET" action="{{ route('transactions.index') }}">
                <div class="col-auto d-flex align-items-center">
                    <div class="form-check form-check-inline mb-0">
                        <input name="status[]" class="form-check-input" type="checkbox" value="succeeded" id="status-succeeded" {{ in_array('succeeded', $status) ? 'checked' : '' }}>
                        <label class="form-check-label" for="status-succeeded">Exitoso</label>
                    </div>
                    <div class="form-check form-check-inline mb-0">
                    <input name="status[]" class="form-check-input" type="checkbox" value="failed" id="status-failed" {{ in_array('failed', $status) ? 'checked' : '' }}>
                    <label class="form-check-label" for="status-failed">Fallido</label>
                    </div>
                    <div class="form-check form-check-inline mb-0">
                    <input name="status[]" class="form-check-input" type="checkbox" value="refunded" id="status-refunded" {{ in_array('refunded', $status) ? 'checked' : '' }}>
                    <label class="form-check-label" for="status-refunded">Reembolso</label>
                    </div>
                </div>
        
                <!-- Single Floating Filter Button -->
                <div class="col-auto">
                    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterSidebar">
                    <i class="fas fa-filter"></i> Filtros
                    </button>
                </div>
        
                <!-- Combined Floating Sidebar for Source Types and Source Names -->
                <div class="offcanvas offcanvas-end" tabindex="-1" id="filterSidebar" style="max-width: 450px;">
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
                                    <input name="provider_type[]" class="form-check-input form-check-input-sm" type="checkbox" value="stripe" id="provider-type-stripe" {{ in_array('stripe', $provider_type) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="provider-type-stripe">Stripe</label>
                                </div>
                                <div class="form-check mb-2" style="font-size: 0.9rem;">
                                    <input name="provider_type[]" class="form-check-input form-check-input-sm" type="checkbox" value="paypal" id="provider-type-paypal" {{ in_array('paypal', $provider_type) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="provider-type-paypal">PayPal</label>
                                </div>
                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary select-all-provider-types">Seleccionar todo</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary deselect-all-provider-types">Deseleccionar todo</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tags Section -->
                        <div class="mb-4">
                            <h6 class="mb-3 border-bottom pb-2">Etiquetas</h6>
                            <div class="d-flex flex-column w-100">
                                @if(empty($availableTags))
                                    <div class="alert alert-info">
                                        No hay etiquetas disponibles
                                    </div>
                                @else
                                    @foreach($availableTags as $tag)
                                        <div class="form-check mb-2" style="font-size: 0.9rem;">
                                            <input name="selectedTags[]" class="form-check-input form-check-input-sm tag-checkbox" type="checkbox" value="{{ $tag }}" id="tag-{{ $loop->index }}" {{ in_array($tag, $selectedTags) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="tag-{{ $loop->index }}">{{ $tag }}</label>
                                        </div>
                                    @endforeach
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary select-all-tags">Seleccionar todo</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary deselect-all-tags">Deseleccionar todo</button>
                                    </div>
                                    <div class="form-text text-muted mt-2">
                                        <small>Sin selección = no filtrar por etiquetas</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Source Types Section -->
                        <div class="mb-4">
                            <h6 class="mb-3 border-bottom pb-2">Tipos de fuente</h6>
                            <div class="d-flex flex-column w-100">
                                @foreach($sourceTypeNames as $sourceTypeName)
                                    <div class="form-check mb-2" style="font-size: 0.9rem;">
                                        <input name="sourceType[]" class="form-check-input form-check-input-sm source-type-checkbox" type="checkbox" value="{{ $sourceTypeName }}" id="sourceType-{{ $loop->index }}" {{ in_array($sourceTypeName, $sourceType) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="sourceType-{{ $loop->index }}">{{ $sourceTypeName }}</label>
                                    </div>
                                @endforeach
                                <div class="d-flex gap-2 mt-2 mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary select-all-source-types">Seleccionar todo</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary deselect-all-source-types">Deseleccionar todo</button>
                                </div>
                            </div>
                        </div>

                        <!-- Membership Filters -->
                        <div>
                            <h6 class="mb-3 border-bottom pb-2">Fuentes</h6>
                            <div class="d-flex flex-column w-100">
                                @if(empty($filteredSourceNames))
                                    <p class="text-muted">Seleccione al menos un tipo de fuente para ver las membresías disponibles.</p>
                                @else
                                    @foreach($filteredSourceNames as $sourceName)
                                        <div class="form-check mb-2" style="font-size: 0.9rem;">
                                            <input name="source[]" class="form-check-input form-check-input-sm source-checkbox" 
                                                type="checkbox" value="{{ $sourceName }}" id="source-{{ $loop->index }}"
                                                {{ in_array($sourceName, $source ?? []) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="source-{{ $loop->index }}">{{ str_replace('- Payment', '', $sourceName) }}</label>
                                        </div>
                                    @endforeach
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary select-all-sources">Seleccionar todo</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary deselect-all-sources">Deseleccionar todo</button>
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
        
                <div class="col-auto">
                    <input type="date" name="startDate" class="form-control" placeholder="Fecha inicial" value="{{ $startDate }}">
                </div>
                <div class="col-auto">
                    <input type="date" name="endDate" class="form-control" placeholder="Fecha final" value="{{ $endDate }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-info" formaction="{{ route('transactions.export') }}">
                        <i class="fas fa-file-export"></i> Exportar Excel
                    </button>
                </div>
                <div class="col-auto ms-auto">
                    <input type="search" name="search" id="search" class="form-control" placeholder="Buscar..." value="{{ $search }}">
                </div>
            </form>
            <div class="table-responsive">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th class="d-none d-md-table-cell">#</th>
                            <th>Nombre</th>
                            <th class="d-none d-md-table-cell">Tipo de fuente</th>
                            <th>Monto</th>
                            <th>Nombre</th>
                            <th>Estatus</th>
                            <th class="d-none d-md-table-cell">Fecha de creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($noResultsMessage)
                            <tr>
                                <td colspan="7">
                                    <div class="p-4 text-center text-muted">
                                        {{ $noResultsMessage }}
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($transactions as $transaction)
                            <tr>
                                <td class="d-none d-md-table-cell">{{ $loop->iteration }}</td>
                                <td>{{ ucfirst($transaction->name) }}</td>
                                <td class="d-none d-md-table-cell">{{ $transaction->source_type }}</td>
                                <td>$ {{ number_format($transaction->amount) }} USD</td>
                                <td>{{ str_replace('- Payment', '', $transaction->entity_resource_name) }}</td>
                                <td>
                                    @if($transaction->status === 'succeeded')
                                        <span class="badge bg-success text-white">Exitoso</span>
                                    @elseif($transaction->status === 'failed')
                                        <span class="badge bg-danger text-white">Fallido</span>
                                    @elseif($transaction->status === 'refunded')
                                        <span class="badge bg-warning text-dark">Reembolsado</span>
                                    @else
                                        <span class="badge bg-secondary text-white">{{ $transaction->status }}</span>
                                    @endif
                                </td>
                                <td class="d-none d-md-table-cell">{{ \Carbon\Carbon::parse($transaction->create_time)->format('d-m-Y') }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        @if($transaction->contact && $transaction->contact->phone)
                                            <a href="https://api.whatsapp.com/send?phone={{ preg_replace('/[^0-9]/', '', $transaction->contact->phone) }}" 
                                               target="_blank" class="btn btn-sm btn-success" title="Contactar por WhatsApp">
                                                <iconify-icon icon="bi:whatsapp"></iconify-icon>
                                            </a>
                                        @endif
                                        @if($transaction->contact && $transaction->contact->email)
                                            <a target="_blank" href="mailto:{{ $transaction->contact->email }}" 
                                               class="btn btn-sm btn-primary" title="Enviar correo">
                                                <iconify-icon icon="mdi:email"></iconify-icon>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3 mb-3 d-flex justify-content-center">
                {{ $transactions->links('pagination::bootstrap-4') }}
            </div>
        
            <div class="table-responsive mt-4">
                <table class="table table-sm bordered-table">
                    <thead>
                        <tr>
                            <th>Transacciones totales exitosas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="h5">$ {{ number_format($totalAmount) }} USD</td>
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
<script>
    $(document).ready(function() {
        // Per page selection change
        $('#perPage').change(function() {
            // Update hidden perPage input and submit the form
            const perPageValue = $(this).val();
            $('#transactionFilterForm').append('<input type="hidden" name="perPage" value="' + perPageValue + '">');
            $('#transactionFilterForm').submit();
        });

        // Select all provider types
        $('.select-all-provider-types').click(function() {
            $('input[name="provider_type[]"]').prop('checked', true);
        });

        // Deselect all provider types
        $('.deselect-all-provider-types').click(function() {
            $('input[name="provider_type[]"]').prop('checked', false);
        });

        // Select all tags
        $('.select-all-tags').click(function() {
            $('.tag-checkbox').prop('checked', true);
        });

        // Deselect all tags
        $('.deselect-all-tags').click(function() {
            $('.tag-checkbox').prop('checked', false);
        });

        // Select all source types
        $('.select-all-source-types').click(function() {
            $('.source-type-checkbox').prop('checked', true);
        });

        // Deselect all source types
        $('.deselect-all-source-types').click(function() {
            $('.source-type-checkbox').prop('checked', false);
        });
        
        // Select all sources - Added these functions that were missing
        $('.select-all-sources').click(function() {
            $('.source-checkbox').prop('checked', true);
        });

        // Deselect all sources
        $('.deselect-all-sources').click(function() {
            $('.source-checkbox').prop('checked', false);
        });

        // Functions for selectAll and deselectAll
        function selectAll(name) {
            $('input[name="' + name + '"]').prop('checked', true);
        }

        function deselectAll(name) {
            $('input[name="' + name + '"]').prop('checked', false);
        }

        // Make these functions globally available
        window.selectAll = selectAll;
        window.deselectAll = deselectAll;

        // Source type change triggers update of available sources
        $('.source-type-checkbox').change(function() {
            var selectedTypes = $('.source-type-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedTypes.length > 0) {
                // Submit the form to refresh the page with the new source types
                $('#transactionFilterForm').submit();
            }
        });
    });
</script>
@endpush