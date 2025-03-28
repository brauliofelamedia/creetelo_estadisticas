<!DOCTYPE html>
<html>
<head>
    <title>Actualizando Contactos</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card p-4 shadow-sm">
            <h3 class="text-center mb-4">Actualización de Contactos</h3>
            
            <div class="progress mb-3">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
            </div>
            
            <p class="text-center mb-3" id="statusText">
                Por favor, no cierre esta ventana hasta que se complete la actualización...
            </p>

            <div class="alert alert-success d-none" id="completeMessage">
                ¡Actualización completada! Puede cerrar esta ventana.
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const total = {{ $total }};
            processNextBatch(0, total);
        });

        function processNextBatch(offset, total) {
            $.ajax({
                url: '{{ route("process.contacts") }}',
                method: 'POST',
                data: {
                    offset: offset,
                    total: total,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('.progress-bar').css('width', response.progress + '%');
                    
                    if (response.isDone) {
                        $('#statusText').addClass('d-none');
                        $('#completeMessage').removeClass('d-none');
                    } else {
                        processNextBatch(response.nextOffset, total);
                    }
                },
                error: function() {
                    alert('Ocurrió un error durante la actualización.');
                }
            });
        }
    </script>
</body>
</html>
