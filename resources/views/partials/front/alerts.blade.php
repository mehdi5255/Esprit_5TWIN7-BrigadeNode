@php
    // Messages de l'application (success) et de Laravel Breeze (status).
    $success = $success ?? session('success') ?? match (session('status')) {
        null => null,
        'verification-link-sent' => 'Un nouveau lien de vérification vient de vous être envoyé.',
        default => session('status'),
    };
    $error = $error ?? session('error');
@endphp
@if($success)
<div class="mx-auto mt-5 flex max-w-7xl items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="alert">
    <i data-lucide="circle-check" class="h-5 w-5"></i><span>{{ $success }}</span>
</div>
@endif
@if($error)
<div class="mx-auto mt-5 flex max-w-7xl items-center gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert">
    <i data-lucide="circle-alert" class="h-5 w-5"></i><span>{{ $error }}</span>
</div>
@endif
