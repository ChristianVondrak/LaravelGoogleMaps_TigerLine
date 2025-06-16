<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Mi aplicación')</title>

  <!-- Google Fonts + Material Icons -->
  <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <!-- Section para estilos específicos -->
  @yield('styles')
</head>
<body>
  <!-- Aquí va todo el contenido de cada página -->
  @yield('content')

  <!-- Section para scripts específicos -->
  @yield('scripts')
</body>
</html>
