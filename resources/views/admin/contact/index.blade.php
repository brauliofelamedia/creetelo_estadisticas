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
            @livewire('contact-table')
        </div>
    </div>
</div>
@endsection