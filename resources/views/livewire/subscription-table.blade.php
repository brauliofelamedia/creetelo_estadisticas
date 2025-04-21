<div>
    <form class="row g-3 align-items-center mb-12 pt-10">
        <div class="col-auto d-flex align-items-center">
            <div class="form-check form-check-inline mb-0">
                <input wire:model.live="status" class="form-check-input" type="checkbox" value="active" id="status-active">
                <label class="form-check-label" for="status-active">Activo</label>
            </div>
            <div class="form-check form-check-inline mb-0">
            <input wire:model.live="status" class="form-check-input" type="checkbox" value="canceled" id="status-canceled">
            <label class="form-check-label" for="status-canceled">Cancelado</label>
            </div>
            <div class="form-check form-check-inline mb-0">
                <input wire:model.live="status" class="form-check-input" type="checkbox" value="incomplete_expired" id="status-incomplete-expired">
                <label class="form-check-label" for="status-incomplete-expired">Incompleto / expirado</label>
            </div>
            <div class="form-check form-check-inline mb-0">
                <input wire:model.live="status" class="form-check-input" type="checkbox" value="past_due" id="status-past-due">
                <label class="form-check-label" for="status-past-due">Vencidos</label>
            </div>
        </div>
        
        <!-- Combined Filter Button -->
        <div class="col-auto">
            <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#combinedFilterSidebar">
            <i class="fas fa-filter"></i> Filtros
            </button>
        </div>

        <!-- Combined Sidebar -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="combinedFilterSidebar" style="max-width: 450px;" wire:ignore.self>
            <div class="offcanvas-header">
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <!-- Provider Type Filters -->
                <div class="mb-4">
                    <h6 class="mb-3">Proveedor de pago</h6>
                    <div class="d-flex flex-column w-100">
                        <div class="form-check mb-2" style="font-size: 0.9rem;">
                            <input wire:model.live="provider_type" class="form-check-input form-check-input-sm" type="checkbox" value="stripe" id="provider-type-stripe">
                            <label class="form-check-label" for="provider-type-stripe">Stripe</label>
                        </div>
                        <div class="form-check mb-2" style="font-size: 0.9rem;">
                            <input wire:model.live="provider_type" class="form-check-input form-check-input-sm" type="checkbox" value="paypal" id="provider-type-paypal">
                            <label class="form-check-label" for="provider-type-paypal">PayPal</label>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="selectAllProviderTypes">Seleccionar todo</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="deselectAllProviderTypes">Deseleccionar todo</button>
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
                                <input wire:model.live="source_type" class="form-check-input form-check-input-sm" type="checkbox" value="{{ $type }}" id="source-type-{{ $loop->index }}">
                                <label class="form-check-label" for="source-type-{{ $loop->index }}">{{ $type }}</label>
                            </div>
                        @endforeach
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="selectAllSourceTypes">Seleccionar todo</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="deselectAllSourceTypes">Deseleccionar todo</button>
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
                                <input wire:model.live="tags" class="form-check-input form-check-input-sm" type="checkbox" 
                                       value="{{ $tag }}" id="tag-{{ $loop->index }}"
                                       checked>
                                <label class="form-check-label" for="tag-{{ $loop->index }}">{{ $tag }}</label>
                            </div>
                        @empty
                            <p class="text-muted">No hay etiquetas disponibles.</p>
                        @endforelse
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="selectAllTags">Seleccionar todo</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="deselectAllTags">Deseleccionar todo</button>
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
                                    <input wire:model.live="source" class="form-check-input form-check-input-sm" type="checkbox" value="{{ $sourceName }}" id="source-{{ $loop->index }}">
                                    <label class="form-check-label" for="source-{{ $loop->index }}">{{ str_replace('- Payment', '', $sourceName) }}</label>
                                </div>
                            @endforeach
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="selectAllSources">Seleccionar todo</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="deselectAllSources">Deseleccionar todo</button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-auto">
            <input type="date" wire:model.live="startDate" class="form-control" placeholder="Fecha inicial">
        </div>
        <div class="col-auto">
            <input type="date" wire:model.live="endDate" class="form-control" placeholder="Fecha final">
        </div>
        <div class="col-auto ms-auto">
            <input type="search" wire:model.live="search" id="search" class="form-control" placeholder="Buscar...">
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
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
                @if($noResultsMessage)
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
                        <td>{{ ucfirst($subscription->contact->fullname) }}</td>
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
                        <td class="d-none d-md-table-cell">{{ \Carbon\Carbon::parse($subscription->create_time)->format('d-m-Y h:s') }}</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
    
    <div class="mt-12 d-flex justify-content-between align-items-center">
        <div>
            <select wire:model.live="perPage" class="form-select">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
            </select>
        </div>
        <div>
            <button wire:click="previousPage" class="btn btn-secondary" {{ $page <= 1 ? 'disabled' : '' }}>
                Anterior
            </button>
            <span class="mx-2">Página {{ $page }} de {{ $totalPages }}</span>
            <button wire:click="nextPage" class="btn btn-secondary" {{ $page >= $totalPages ? 'disabled' : '' }}>
                Siguiente
            </button>
        </div>
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
</style>
@endpush

@push('scripts')
@endpush