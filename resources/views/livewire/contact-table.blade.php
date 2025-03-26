<div>
    <form class="row g-3 align-items-center mb-12 pt-10">
        <div class="col-auto">
            <select wire:model.live="country" id="country" class="form-control form-select">
                <option value="*">-- Seleccionar el país --</option>
                @foreach($countries as $country)
                    <option value="{{$country}}">{{$country}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <select wire:model.live="date_filter" id="date_filter" class="form-control form-select">
                <option value="*">-- Selecciona fecha de registro --</option>
                <option value="{{ now()->startOfWeek()->format('Y-m-d') }}">Esta semana</option>
                <option value="{{ now()->subMonth()->format('Y-m-d') }}">Último mes</option>
                <option value="{{ now()->subMonths(2)->format('Y-m-d') }}">Últimos 2 meses</option>
                <option value="{{ now()->subMonths(3)->format('Y-m-d') }}">Más de 3 meses</option>
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
                    <th>País</th>
                    <th class="d-none d-md-table-cell">Última Actualización</th>
                    <th style="display: none;">Acciones</th>
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
                    @foreach($contacts as $contact)
                    <tr>
                        <td class="d-none d-md-table-cell">{{ $loop->iteration }}</td>
                        <td>{{ ucfirst($contact->firstNameLowerCase) }} {{ ucfirst($contact->lastNameLowerCase) }}</td>
                        <td class="d-none d-md-table-cell">{{ $contact->email }}</td>
                        <td>{{ $contact->countryName }}</td>
                        <td class="d-none d-md-table-cell">{{ \Carbon\Carbon::parse($contact->dateAdded)->diffForHumans() }}</td>
                        <td style="display: none;">
                            <div class="btn-group" role="group">
                                <a href="#" class="btn btn-sm btn-primary" title="Editar">
                                    <iconify-icon icon="mdi:pencil" class="menu-icon"></iconify-icon>
                                </a>
                                <a href="#" class="btn btn-sm btn-info" title="Ver">
                                    <iconify-icon icon="mdi:eye" class="menu-icon"></iconify-icon>
                                </a>
                                <button wire:click="delete({{ $contact->id }})" class="btn btn-sm btn-danger" title="Eliminar">
                                    <iconify-icon icon="mdi:delete" class="menu-icon"></iconify-icon>
                                </button>
                            </div>
                        </td>
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
