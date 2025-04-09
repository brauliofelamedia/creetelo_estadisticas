@extends('layouts.app')

@section('content')
<div class="dashboard-main-body">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Configuraciones</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
            <a href="index.html" class="d-flex align-items-center gap-1 hover-text-primary">
                <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>Dashboard
            </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Configuración</li>
        </ul>
    </div>
    
    <div class="row gy-4">
      <div class="col-12">
          @if(session('success'))
            <div class="alert alert-success">
              {{ session('success') }}
            </div>
          @endif
      </div>
      <div class="col-md-8">
        <div class="card mb-20">
          <div class="card-header">
            <h6 class="card-title mb-0">Tags de importación de contactos</h6>
            <p class="mb-0">A partir de los tags de importación se trae las transacciones y subscripciones activas.</p>
          </div>
            <div class="card-body">
              <div class="row gy-3">
                <form action="{{route('config.tags')}}" method="POST">
                  @csrf
                  <div class="col-12">
                    <label class="form-label">Tags:</label>
                    <input type="text" name="tags" class="form-control" value="{{ $config->tags ?? '' }}">
                    <small>Separalas con coma las tags</small>
                  </div>
                  <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="updateTags" onclick="confirmUpdate(event)">Actualizar y descargar</button>
                  </div>
              </form>
              </div>
            </div>
        </div>
        <div class="card mb-20">
          <div class="card-header">
            <h6 class="card-title mb-0">Acceso a GHL</h6>
          </div>
            <div class="card-body">
              <div class="row gy-3">
                <div class="col-6">
                  <label class="form-label">Código</label>
                  <input type="text" name="code" class="form-control" value="{{ $config->code ?? '' }}" readonly>
                </div>
                <div class="col-6">
                  <label class="form-label">ID de Compañía</label>
                  <input type="text" name="company_id" class="form-control" value="{{ $config->company_id ?? '' }}" readonly>
                </div>
                <div class="col-6">
                  <label class="form-label">ID de Ubicación</label>
                  <input type="text" name="location_id" class="form-control" value="{{ $config->location_id ?? '' }}" readonly>
                </div>
                <div class="col-6">
                  <label class="form-label">Token de Actualización</label>
                  <input type="text" name="refresh_token" class="form-control" value="{{ $config->refresh_token ?? '' }}" readonly>
                </div>
                <div class="col-6">
                  <label class="form-label">Token de Acceso</label>
                  <input type="text" name="access_token" class="form-control" value="{{ $config->access_token ?? '' }}" readonly>
                </div>
                <div class="col-12 d-flex gap-2">
                  <a href="{{route('renew.token')}}" class="btn btn-primary">Refresh Token</a>
                  <a href="{{route('connect')}}" class="btn btn-secondary">Conectar GHL</a>
                </div>
              </div>
            </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card mb-20">
          <div class="card-header">
            <h6 class="card-title mb-0">Generales</h6>
          </div>
            <div class="card-body">
            <form action="{{ route('config.update','0195aa88-19d7-7045-9116-fb9f61b75e4c') }}" method="POST">
              @csrf
              @method('PATCH')
              <div class="row gy-3">
              <div class="col-6">
                <label class="form-label">Nombre del sitio</label>
                <input type="text" name="site_name" class="form-control" value="{{ $config->site_name ?? '' }}">
              </div>
              <div class="row">
                <div class="col-6">
                <label class="form-label">Color primario</label>
                <input type="color" name="primary_color" class="form-control" value="{{ $config->primary_color ?? '#000000' }}">
                </div>
                <div class="col-6">
                <label class="form-label">Color secundario</label>
                <input type="color" name="secondary_color" class="form-control" value="{{ $config->secondary_color ?? '#ffffff' }}">
                </div>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
              </div>
              </div>
            </form>
            </div>
        </div>
        <div class="card mb-20">
          <div class="card-header">
            <h6 class="card-title mb-0">Logo y favicon</h6>
          </div>
          <div class="card-body">
            <form action="{{ route('config.media') }}" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="row gy-3">
                <div class="col-12">
                  <label class="form-label">Logo Light</label>
                    <input type="file" name="logo_light" class="form-control" accept="image/*"><br/>
                    @if($config->logo_light ?? false)
                      <img src="{{asset('storage/'.$config->logo_light)}}" class="img-fluid mb-2" style="width:200px;">
                    @endif
                </div>
                <div class="col-12">
                    <label class="form-label">Logo Dark</label>
                      <input type="file" name="logo_dark" class="form-control" accept="image/*"><br/>
                      @if($config->logo_dark ?? false)
                        <img src="{{asset('storage/'.$config->logo_dark)}}" class="img-fluid mb-2" style="width:200px;">
                      @endif
                  </div>
                <div class="col-12">
                  <label class="form-label">Favicon</label>
                  <input type="file" name="favicon" class="form-control" accept="image/*"><br/>
                  @if($config->favicon ?? false)
                    <img src="{{asset('storage/'.$config->favicon)}}" class="img-fluid mb-2" alt="Favicon actual">
                  @endif
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-primary">Actualizar imágenes</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    
  </div>
@endsection

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
<style>
  .swal-title-small {
      font-size: 28px !important;
      font-weight: bold!important;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>
  function confirmUpdate(event) {
    event.preventDefault();
    
    Swal.fire({
      title: '¿Estás seguro?',
      text: "Se actualizarán las tags y se descargarán los datos, esto podría tardar unos minutos.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Sí, actualizar', 
      cancelButtonText: 'Cancelar',
      customClass: {
        title: 'swal-title-small'
      }
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          title: 'Actualizando archivos',
          text: 'Este proceso podría tardar unos minutos. La página se recargará automáticamente.',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          customClass: {
            title: 'swal-title-small'
          },
          didOpen: () => {
            Swal.showLoading();
          }
        });
        event.target.closest('form').submit();
      }
    });
  }
  </script>
@endpush