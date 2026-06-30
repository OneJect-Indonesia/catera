<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
    :root.dark {
        color-scheme: dark;
    }
</style>
<script>
    window.Flux = {
        applyAppearance (appearance) {
            let applyDark = () => document.documentElement.classList.add('dark')
            let applyLight = () => document.documentElement.classList.remove('dark')

            if (appearance === 'system') {
                let media = window.matchMedia('(prefers-color-scheme: dark)')
                window.localStorage.removeItem('flux.appearance')
                media.matches ? applyDark() : applyLight()
            } else if (appearance === 'dark') {
                window.localStorage.setItem('flux.appearance', 'dark')
                applyDark()
            } else if (appearance === 'light') {
                window.localStorage.setItem('flux.appearance', 'light')
                applyLight()
            }
        }
    }

    window.Flux.applyAppearance(window.localStorage.getItem('flux.appearance') || 'light')
</script>
