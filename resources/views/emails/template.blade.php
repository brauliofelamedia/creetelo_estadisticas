<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            max-width: 200px;
            height: auto;
        }
        .content {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h2 {
            font-weight: 600;
            color: #2d3748;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .content p {
            font-weight: 300;
            color: #333333;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .button {
            font-family: 'Poppins', Arial, sans-serif;
            font-weight: 500;
            display: block;
            width: 200px;
            padding: 16px 15px;
            margin: 20px auto;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .footer {
            font-weight: 300;
            text-align: center;
            margin-top: 30px;
            color: #666666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="{{ asset('storage/'.$config_global->logo_light) }}" alt="Logo">
        </div>
        
        <div class="content">
            <h2>¡Bienvenido {{ $userName ?? 'Usuario' }}!</h2>
            
            <p>
                {{ $content ?? 'Contenido del correo electrónico aquí.' }}
            </p>

            <a href="{{ $buttonUrl ?? '#' }}" class="button" style="background-color:{{$config_global->primary_color}};">
                {{ $buttonText ?? 'Click Aquí' }}
            </a>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $config_global->site_name }}. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
