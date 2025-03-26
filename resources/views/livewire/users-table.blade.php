<div>
    <form class="row g-3 align-items-center mb-12 pt-10">
        <div class="col-auto">
            <select wire:model.live="status" id="status" class="form-control form-select">
                <option value="*">-- Seleccionar estatus --</option>
                <option value="active">Activo</option>
                <option value="inactive">Inactivo</option>
            </select>
        </div>
        <div class="col-auto">
            <select wire:model.live="role" id="role" class="form-control form-select">
                <option value="*">-- Seleccionar rol --</option>
                @foreach($roles as $role)
                    <option value="{{ $role }}">{{ $role }}</option>
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
                    <th>Rol</th>
                    <th class="d-none d-md-table-cell">Fecha de registro</th>
                </tr>
            </thead>
            <tbody>
                @if(count($users) === 0)
                    <tr>
                        <td colspan="4">
                            <div class="p-4 text-center text-muted">
                                No se encontraron usuarios
                            </div>
                        </td>
                    </tr>
                @else
                    @foreach($users as $user)
                    <tr>
                        <td class="d-none d-md-table-cell">{{ $loop->iteration }}</td>
                        <td>{{ ucwords($user->name) }} {{ ucwords($user->last_name) }}</td>
                        <td class="d-none d-md-table-cell">{{ $user->email }}</td>
                        <td>{{$user->role}}</td>
                        <td class="d-none d-md-table-cell">{{ \Carbon\Carbon::parse($user->created_at)->format('d-m-Y') }}</td>
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
