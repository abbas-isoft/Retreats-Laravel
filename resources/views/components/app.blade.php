<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="UTF-8">
  <title>{{ $title ?? config('app.name') }}</title>
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>

<body>
    {{ $slot }}
</body>
</html>