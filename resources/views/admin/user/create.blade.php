@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Crear usuario</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
            <a href="index.html" class="d-flex align-items-center gap-1 hover-text-primary">
                <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>Dashboard
            </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Agregar</li>
        </ul>
    </div>
    
    <div class="row gy-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h6 class="card-title mb-0">Datos generales</h6>
          </div>
            <div class="card-body">
            @if(session('success'))
              <div class="alert alert-success">
                {{ session('success') }}
              </div>
            @endif
            <form action="{{ route('users.store') }}" method="POST">
              @csrf
              <div class="row gy-3">
                <div class="col-12">
                    <div class="form-switch switch-primary d-flex align-items-center gap-3">
                        <input class="form-check-input" name="status" type="checkbox" role="switch" id="yes" checked>
                        <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="yes">Activo</label>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label">Nombre:</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Apellidos:</label>
                    <input type="text" name="last_name" class="form-control">
                </div>
                <div class="col-6">
                    <label class="form-label">Correo electrónico:</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Teléfono:</label>
                    <input type="tel" name="phone" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Rol:</label>
                    <select name="role" class="form-control" required>
                        <option value="">Seleccionar el rol</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12" id="password_field">
                    <label class="form-label">Contraseña:</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-12">
                    <div class="form-switch switch-primary d-flex align-items-center gap-3">
                        <input class="form-check-input" name="generate_password" type="checkbox" role="switch" id="generate_password">
                        <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="generate_password">Generar contraseña</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Crear usuario</button>
                </div>
              </div>
            </form>
            </div>
        </div>
      </div>
    </div>
    
  </div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        function togglePassword() {
            if ($('#generate_password').is(':checked')) {
                $('#password_field').hide();
                $('input[name="password"]').val('');
                $('input[name="password"]').prop('required', false);
            } else {
                $('#password_field').show();
                $('input[name="password"]').prop('required', true);
            }
        }

        // Initial state
        togglePassword();

        // On change
        $('#generate_password').change(function() {
            togglePassword();
        });
    });
</script>
@endpush