<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
            --primary-color: #4da8cf;
            --primary-dark: #3f8f81;
        }

        body {
            font-family: var(--font-sans);
            background: linear-gradient(40deg, #3f8f81 0%, #6dc5ee 5%, #e0f2fe 20%, #e0f2fe 80%, #6dc5ee 95%, #3f8f81 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
    </style>
    @endif
</head>

<body
    class="bg-linear-to-br from-[#6dc5ee] via-[#e0f2fe] to-[#6dc5ee] min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <div class="backdrop-blur-md bg-white/20 border border-white/30 rounded-2xl p-8 shadow-2xl">
            @yield('content')
        </div>
        <footer class="mt-8 text-center text-sm text-[#3f8f81]/70">
            <p class="mb-2">{{ config('app.name') }}</p>
            <p class="text-xs">
                Need help?
                <a href="mailto:support@example.com" class="underline hover:text-[#4da8cf] transition-colors">
                    Contact Support
                </a>
            </p>
        </footer>
    </div>
</body>

</html>
