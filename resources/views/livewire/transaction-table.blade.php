<div>
    <form class="row g-3 align-items-center mb-12 pt-10">
        <div class="col-auto d-flex align-items-center">
            <div class="form-check form-check-inline mb-0">
                <input wire:model.live="status" class="form-check-input" type="checkbox" value="succeeded" id="status-succeeded">
                <label class="form-check-label" for="status-succeeded">Exitoso</label>
            </div>
            <div class="form-check form-check-inline mb-0">
            <input wire:model.live="status" class="form-check-input" type="checkbox" value="failed" id="status-failed">
            <label class="form-check-label" for="status-failed">Fallido</label>
            </div>
            <div class="form-check form-check-inline mb-0">
            <input wire:model.live="status" class="form-check-input" type="checkbox" value="refunded" id="status-refunded">
            <label class="form-check-label" for="status-refunded">Reembolso</label>
            </div>
        </div>
        <!-- Floating Filter Button -->
        <div class="col-auto">
            <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterSidebar">
            <i class="fas fa-filter"></i> Membresías
            </button>
        </div>

        <!-- Floating Sidebar -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="filterSidebar" style="max-width: 450px;" wire:ignore.self>
            <div class="offcanvas-header">
            <h6 class="offcanvas-title">Filtros por membresía</h6>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
            <div class="d-flex flex-column w-100">
                @foreach($sourceNames as $sourceName)
                    <div class="form-check mb-2" style="font-size: 0.9rem;">
                        <input wire:model.defer="source" wire:change="$refresh" class="form-check-input form-check-input-sm" type="checkbox" value="{{ $sourceName }}" id="source-{{ $loop->index }}" {{ !$source || in_array($sourceName, $source) ? 'checked' : '' }}>
                        <label class="form-check-label" for="source-{{ $loop->index }}">{{ str_replace('- Payment', '', $sourceName) }}</label>
                    </div>
                @endforeach
                <div class="d-flex gap-2 mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="selectAllSources">Seleccionar todo</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="deselectAllSources">Deseleccionar todo</button>
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
                    <th class="d-none d-md-table-cell">Correo</th>
                    <th>Monto</th>
                    <th>Membresia</th>
                    <th>Estatus</th>
                    <th class="d-none d-md-table-cell">Fecha de creación</th>
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
                    @foreach($transactions as $transaction)
                    <tr>
                        <td class="d-none d-md-table-cell">{{ $loop->iteration }}</td>
                        <td>{{ ucfirst($transaction->name) }}</td>
                        <td class="d-none d-md-table-cell">{{ $transaction->email }}</td>
                        <td>$ {{ number_format($transaction->amount) }} USD</td>
                        <td>{{ str_replace('- Payment', '', $transaction->entitySourceName) }}</td>
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