@extends($layout)
@section('title', 'Notifications')
@section('breadcrumb', 'Notifications')
@section('content')
<section class="{{ $layout === 'layouts.front' ? 'mx-auto max-w-3xl px-4 py-12 sm:px-6' : 'max-w-3xl' }}">
    @if($layout === 'layouts.front')<h1 class="mb-8 font-display text-4xl font-bold">Notifications</h1>@endif
    <div class="space-y-3">
        @forelse($notifications as $notification)
        <a href="{{ $notification->data['url'] ?? '#' }}" class="flex items-start gap-4 rounded-2xl border {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-forest-200 bg-forest-50' }} p-4 transition hover:shadow-md">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white text-forest-700 ring-1 ring-slate-200"><i data-lucide="{{ ['Réparation' => 'wrench', 'Transformation' => 'scissors', 'Don' => 'gift'][$notification->data['type'] ?? ''] ?? 'bell' }}" class="h-5 w-5"></i></span>
            <div class="flex-1">
                <p class="text-sm {{ $notification->read_at ? 'text-slate-600' : 'font-semibold text-slate-900' }}">{{ $notification->data['message'] ?? '' }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $notification->data['reference'] ?? '' }} · {{ $notification->created_at->diffForHumans() }}</p>
            </div>
            @unless($notification->read_at)<span class="mt-2 h-2 w-2 rounded-full bg-amber-500" aria-label="Non lue"></span>@endunless
        </a>
        @empty
        <p class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-10 text-center text-slate-500">Aucune notification pour le moment.</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $notifications->links() }}</div>
</section>
@endsection
