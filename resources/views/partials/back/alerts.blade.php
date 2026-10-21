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
<div class="mb-6 flex items-center justify-between rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
    <span class="flex items-center gap-3"><i data-lucide="circle-check" class="h-5 w-5"></i>{{ $success }}</span>
    <button aria-label="Fermer" onclick="this.parentElement.remove()"><i data-lucide="x" class="h-4 w-4"></i></button>
</div>
@endif
@if($error)
<div class="mb-6 flex items-center justify-between rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
    <span class="flex items-center gap-3"><i data-lucide="circle-alert" class="h-5 w-5"></i>{{ $error }}</span>
    <button aria-label="Fermer" onclick="this.parentElement.remove()"><i data-lucide="x" class="h-4 w-4"></i></button>
</div>
@endif
