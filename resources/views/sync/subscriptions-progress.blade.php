<!DOCTYPE html>
<html>
<head>
    <title>Actualizando Suscripciones</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card p-4 shadow-sm">
            <h3 class="text-center mb-4">Actualización de Suscripciones</h3>
            
            <div class="progress mb-3">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
            </div>
            
            <p class="text-center mb-3" id="statusText">
                Por favor, no cierre esta ventana hasta que se complete la actualización...
            </p>

            <div class="alert alert-success d-none" id="completeMessage">
                ¡Actualización completada! falta un solo paso, no cierre la ventana, por favor.
                <br>Redirigiendo a la página de contactos...
            </div>

            <div class="alert alert-danger d-none" id="errorMessage">
                Ha ocurrido un error: <span id="errorText"></span>
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
                url: '{{ route("process.subscriptions") }}',
                method: 'POST',
                data: {
                    offset: offset,
                    total: total,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (!response) {
                        showError('La respuesta del servidor está vacía');
                        return;
                    }

                    $('.progress-bar').css('width', response.progress + '%');

                    if (response.isDone) {
                        $('#statusText').addClass('d-none');
                        $('#completeMessage').removeClass('d-none');
                        
                        if (response.redirect) {
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 1000);
                        }
                    } else {
                        setTimeout(function() {
                            processNextBatch(response.nextOffset, total);
                        }, 500);
                    }
                },
                error: function(xhr, status, error) {
                    showError('Error: ' + (error || 'Desconocido') + 
                             ' - Status: ' + status + 
                             (xhr.responseText ? ' - Detalles: ' + xhr.responseText : ''));
                }
            });
        }

        function showError(message) {
            $('#errorText').text(message);
            $('#errorMessage').removeClass('d-none');
            $('#statusText').addClass('d-none');
        }
    </script>
</body>
</html>
