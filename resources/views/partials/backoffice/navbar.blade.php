<header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
    <button data-menu class="rounded-xl border border-slate-200 p-2 text-slate-600 lg:hidden" aria-label="Ouvrir le menu"><i data-lucide="menu" class="h-5 w-5"></i></button>
    <p class="hidden text-sm font-semibold text-slate-500 sm:block">{{ auth()->user()->structure()?->nom ?? 'Administration TextileCycle' }}</p>
    <div class="flex items-center gap-3">
        @php $nonLues = auth()->user()->unreadNotifications()->count(); @endphp
        <a href="{{ route('notifications.index') }}" class="relative rounded-xl p-2.5 text-slate-500 hover:bg-slate-100" aria-label="Notifications{{ $nonLues ? " ($nonLues non lues)" : '' }}"><i data-lucide="bell" class="h-5 w-5"></i>@if($nonLues)<span class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white ring-2 ring-white">{{ $nonLues }}</span>@endif</a>
        <span class="h-7 w-px bg-slate-200"></span>
        <a href="{{ route('compte.edit') }}" class="text-right hover:opacity-80"><p class="text-sm font-semibold text-slate-800">Bonjour, {{ auth()->user()->name }}</p><p class="text-xs text-slate-400">{{ now()->translatedFormat('d F Y') }}</p></a>
    </div>
</header>
