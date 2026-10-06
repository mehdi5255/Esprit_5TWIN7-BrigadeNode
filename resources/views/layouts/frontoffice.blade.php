<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TextileCycle') — TextileCycle</title>
    <meta name="description" content="@yield('meta', 'TextileCycle donne une seconde vie à vos vêtements.')">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/validation.js') }}" defer></script>
    <script src="{{ asset('js/confirmation.js') }}" defer></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['DM Sans', 'sans-serif'], display: ['Manrope', 'sans-serif'] },
                    colors: { forest: { 50: '#f2f7f4', 100: '#deeee4', 500: '#2f7d57', 600: '#256747', 700: '#1f523b', 900: '#17382b' }, clay: '#d97a52' }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-[#f7f8f4] font-sans text-slate-800 antialiased">
    @include('partials.frontoffice.navbar')
    @include('partials.frontoffice.alerts')
    <main>@yield('content')</main>
    @include('partials.frontoffice.footer')
    <script>lucide.createIcons();</script>
    @yield('scripts')
</body>
</html>
