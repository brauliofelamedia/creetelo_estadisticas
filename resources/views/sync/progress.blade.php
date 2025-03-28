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
            <li class="fw-medium">Sincronización</li>
        </ul>
    </div>
    
    <div class="card basic-data-table">
      <div class="card-body" style="padding: 35px!important;">
            <h6 class="text-center">No cierres la ventana, seras redirigido automaticamente</h6>
            <h5 class="text-center mb-3">
                Importando 
                @if($current_process == 'subscriptions')
                    subscripciones...
                @elseif($current_process == 'transactions')
                    transacciones...
                @elseif($current_process == 'contacts')
                    contactos...
                @endif
            </h5>
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
            <p class="mt-3 text-center">Procesando... <span id="progress-text">0%</span></p>
      </div>
    </div>
    
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let offset = 0;
        const total = {{ $total }};
        const currentProcess = '{{ $current_process }}';

        function processData() {
            $.ajax({
                url: '{{ url("/dashboard/sync/process-") }}' + currentProcess,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    offset: offset,
                    total: total
                },
                success: function(response) {
                    $('.progress-bar').css('width', response.progress + '%');
                    $('#progress-text').text(response.progress + '%');
                    
                    if (response.isDone) {
                        window.location.href = response.redirect;
                    } else {
                        offset = response.nextOffset;
                        processData();
                    }
                },
                error: function(xhr) {
                    console.error('Error en la sincronización:', xhr);
                }
            });
        }

        processData();
    });
</script>
@endpush