<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Oliv — App Builder</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --oliv-header: #2d3748;
            --oliv-primary: #252621;
            --oliv-secondary: #D3D2BC;
            --oliv-accent: #B98B69;
            --oliv-action: #2d3748;
            --oliv-action-hover: #4a5568;
            --oliv-green: #48bb78;
            --oliv-green-bg: #f0fff4;
            --oliv-page-bg: #fdf9f3;
        }

        body {
            background-color: var(--oliv-page-bg);
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen antialiased">
    {{-- Top Header Bar --}}
    <header class="w-full text-white" style="background-color: var(--oliv-header);">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
            <div class="flex items-center gap-2">
                <img src="{{ asset('img/IMG_5398.png') }}" alt="Oliv" class="h-7 brightness-0 invert">
                <span class="text-sm font-medium opacity-80">— App Builder</span>
            </div>
            <span class="text-sm opacity-60">portal.oliv.tech</span>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="mx-auto max-w-5xl px-6 py-8">
        <div class="rounded-xl bg-white shadow-sm border border-gray-100 p-8">
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
</body>
</html>
