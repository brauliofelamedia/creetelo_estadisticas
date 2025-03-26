@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Todos los usuarios</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
            <a href="index.html" class="d-flex align-items-center gap-1 hover-text-primary">
                <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                Dashboard
            </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Usuarios</li>
        </ul>
    </div>
    
    <div class="card basic-data-table">
      <div class="card-body">
          @livewire('users-table')
      </div>
    </div>
    
</div>
@endsection