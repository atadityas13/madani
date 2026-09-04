<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MADANI') · MTsN 11 Majalengka</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script type="application/json" id="madani-wilayah-data">@json(config('wilayah'))</script>
    @php
        $madaniFlash = [
            'status' => session('status'),
            'error' => session('error'),
            'warning' => session('warning'),
        ];
    @endphp
    <script type="application/json" id="madani-flash">@json($madaniFlash)</script>
    @livewireStyles
</head>
<body>
    @yield('body')
    @livewireScripts
</body>
</html>
