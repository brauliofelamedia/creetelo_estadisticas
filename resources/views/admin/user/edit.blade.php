@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Editar usuario - {{$user->fullname}}</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
            <a href="index.html" class="d-flex align-items-center gap-1 hover-text-primary">
                <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>Dashboard
            </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Editar</li>
        </ul>
    </div>
    
    <div class="row gy-4">
        <div class="col-md-8">
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
                            <input type="text" name="name" value="{{$user->name}}" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Apellidos:</label>
                            <input type="text" name="last_name" value="{{$user->last_name}}" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Correo electrónico:</label>
                            <input type="email" name="email" class="form-control" value="{{$user->email}}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Teléfono:</label>
                            <input type="tel" name="phone" value="{{$user->phone}}" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Rol:</label>
                            <select name="role" class="form-control" value="{{$user->role}}" required>
                                <option value="">Seleccionar el rol</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{($user->role == $role->name)? 'selected':''}}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Actualizar</button>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title">Cambiar contraseña</h6>
                </div>
                <div class="card-body">
                    <p style="line-height: 1.4em;font-size:15px;">Si deseas cambiar contraseña, rellena el campo o selecciona enviar contraseña y guarda.</p>
                    <form action="#">
                        <div class="row gy-3">
                            <div class="col-12 fields_password">
                                <label class="form-label">Contraseña:</label>
                                <input type="password" name="password" class="form-control" id="password" required>
                            </div>
                            <div class="col-12 fields_password">
                                <label class="form-label">Repetir contraseña:</label>
                                <input type="password" name="repeat_password" class="form-control" id="repeat_password"  required>
                            </div>
                            <div class="col-12">
                                <div class="form-switch switch-primary d-flex align-items-center gap-3">
                                    <input class="form-check-input" name="send_recovery" type="checkbox" role="switch" id="send_recovery">
                                    <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="generate_password">Enviar correo de recuperación</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-warning" style="width: 100%;">Actualizar contraseña</button>
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
        // On change
        $('#send_recovery').change(function() {
            var recovery = $(this).is(':checked');
            if(recovery) {
                $('.fields_password').hide();
            } else {
                $('.fields_password').show();
            }
        });
    });
</script>
@endpush