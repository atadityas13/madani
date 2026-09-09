<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2c94fd">
    <title>@yield('title', 'Wali Kelas') · Ta'lim</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="talim-webview-body">
    <div class="talim-webview">
        <header class="talim-webview__header">
            <div class="talim-webview__title">@yield('heading', 'Wali Kelas')</div>
            <div class="talim-webview__sub">@yield('subheading')</div>
        </header>
        <main class="talim-webview__content">
            @yield('content')
        </main>
    </div>
</body>
</html>
