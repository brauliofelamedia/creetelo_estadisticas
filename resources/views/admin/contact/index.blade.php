@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Todos los contactos</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.html" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">contactos</li>
        </ul>
    </div>

    <div class="card basic-data-table">
        @php
            $updating = file_exists(storage_path('app/contacts.json.temp'));
        @endphp
        <div class="card-body">
            @if($updating)
                <div class="alert alert-warning">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    Estamos actualizando los contactos, por favor espere...
                </div>
            @endif
            <form class="row g-3 align-items-center mb-12 pt-10" method="GET">
                <div class="col-auto">
                    <select name="country" id="country" class="form-control form-select">
                        <option value="*">-- Seleccionar el país --</option>
                        @foreach($countries as $country)
                            <option value="{{$country}}" {{ request('country') == $country ? 'selected' : '' }}>{{$country}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="date_filter" id="date_filter" class="form-control form-select">
                        <option value="*">-- Selecciona fecha de registro --</option>
                        <option value="week" {{ request('date_filter') == 'week' ? 'selected' : '' }}>Esta semana</option>
                        <option value="month" {{ request('date_filter') == 'month' ? 'selected' : '' }}>Este mes</option>
                        <option value="year" {{ request('date_filter') == 'year' ? 'selected' : '' }}>Este año</option>
                    </select>
                </div>
                <div class="col-auto ms-auto">
                    <input type="search" id="search" name="search" class="form-control" placeholder="Buscar..." value="{{ request('search') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
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
                        @foreach($contacts as $contact)
                        <tr>
                            <td class="d-none d-md-table-cell">{{ ($contacts->currentPage() - 1) * $contacts->perPage() + $loop->iteration }}</td>
                            <td>{{ ucfirst($contact->firstNameLowerCase) }} {{ ucfirst($contact->lastNameLowerCase) }}</td>
                            <td class="d-none d-md-table-cell">{{ $contact->email }}</td>
                            <td>{{ $contact->country }}</td>
                            <td class="d-none d-md-table-cell">{{ Carbon\Carbon::parse($contact->dateAdded)->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-12">
                {{ $contacts->links() }}
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#changePerPage').on('change', function() {
                $(this).closest('form').submit();
            });
        });
    </script>
@endpush