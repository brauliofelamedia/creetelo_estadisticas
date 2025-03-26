<div>
    <form class="row g-3 align-items-center mb-12 pt-10">
        <div class="col-auto">
            <select wire:model.live="status" id="status" class="form-control form-select">
                <option value="*">-- Seleccionar estatus --</option>
                <option value="active">Activo</option>
                <option value="incomplete_expired">Incompleto / expirado</option>
                <option value="canceled">Cancelado</option>
                <option value="past_due">Pago atrasado</option>
            </select>
        </div>
        <div class="col-auto">
            <select wire:model.live="source" id="source" class="form-control form-select">
                <option value="">-- Seleccionar source --</option>
                @foreach($sourceNames as $sourceName)
                    <option value="{{ $sourceName }}">{{ $sourceName }}</option>
                @endforeach
            </select>
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
                    @foreach($subscriptions as $subscription)
                    <tr>
                        <td class="d-none d-md-table-cell">{{ $loop->iteration }}</td>
                        <td>{{ ucfirst($subscription->contactName) }}</td>
                        <td class="d-none d-md-table-cell">{{ $subscription->contactEmail }}</td>
                        <td>$ {{ number_format($subscription->amount) }} USD</td>
                        <td>{{$subscription->entitySourceName}}</td>
                        <td>
                            @if($subscription->status === 'active')
                                <span class="text-success">Activo</span>
                            @elseif($subscription->status === 'canceled')
                                <span class="text-danger">Cancelado</span>
                            @elseif($subscription->status === 'incomplete_expired')
                                <span class="text-warning">Incompleto</span>
                            @elseif($subscription->status === 'past_due')
                                <span class="text-info">Pago atrasado</span>
                            @else
                                {{ $subscription->status }}
                            @endif
                        </td>
                        <td class="d-none d-md-table-cell">{{ \Carbon\Carbon::parse($subscription->createdAt)->diffForHumans() }}</td>
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
</div>