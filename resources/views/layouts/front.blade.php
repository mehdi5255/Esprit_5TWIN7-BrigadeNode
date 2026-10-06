<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TextileCycle') — TextileCycle</title>
    <meta name="description" content="@yield('meta', 'TextileCycle donne une seconde vie à vos vêtements.')">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('front/js/tailwind.config.js') }}"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/validation.js') }}" defer></script>
    <script src="{{ asset('js/confirmation.js') }}" defer></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    @stack('styles')
</head>
<body class="min-h-screen bg-[#f7f8f4] font-sans text-slate-800 antialiased">
    @include('partials.front.navbar')
    @include('partials.front.alerts')
    <main>@yield('content')</main>
    @include('partials.front.footer')
    <script src="{{ asset('front/js/front.js') }}"></script>
    @stack('scripts')
</body>
</html>
