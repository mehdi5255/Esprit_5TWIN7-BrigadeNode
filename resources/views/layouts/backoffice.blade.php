<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Espace de gestion') — TextileCycle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/validation.js') }}" defer></script>
    <script src="{{ asset('js/confirmation.js') }}" defer></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { sans: ['DM Sans', 'sans-serif'], display: ['Manrope', 'sans-serif'] },
                colors: { ink: '#17211d', forest: { 50: '#f2f7f4', 100: '#deeee4', 500: '#2f7d57', 600: '#256747', 700: '#1f523b', 900: '#17382b' } }
            }}
        }
    </script>
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    @include('partials.backoffice.sidebar')
    <div class="lg:pl-64">
        @include('partials.backoffice.navbar')
        <main class="px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="mb-7">
                    <p class="mb-2 text-sm font-medium text-forest-600">@yield('breadcrumb', 'Espace de gestion')</p>
                    <h1 class="font-display text-3xl font-bold tracking-tight text-ink">@yield('title')</h1>
                </div>
                @if(auth()->user()->estEnAttente())
                <div class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <i data-lucide="hourglass" class="mt-0.5 h-5 w-5 shrink-0"></i>
                    <p><span class="font-semibold">Compte en attente de validation.</span> Complétez votre profil{{ auth()->user()->hasRole('atelier') ? ' et vos services' : ' et vos besoins' }} : vous serez visible des particuliers dès que l'administration aura validé votre compte.</p>
                </div>
                @endif
                @include('partials.backoffice.alerts')
                @yield('content')
            </div>
        </main>
        @include('partials.backoffice.footer')
    </div>
    <script>
        lucide.createIcons();
        document.querySelector('[data-menu]')?.addEventListener('click', () => document.querySelector('[data-sidebar]')?.classList.toggle('-translate-x-full'));
    </script>
    @yield('scripts')
</body>
</html>
