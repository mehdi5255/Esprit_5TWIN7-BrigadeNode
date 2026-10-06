{{-- Accepter / refuser (avec motif) une demande en attente. Attend $routeAccepter, $routeRefuser, $texte. --}}
<p class="text-slate-500">{{ $texte }}</p>
<form method="POST" action="{{ $routeAccepter }}">@csrf<button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 font-semibold text-white shadow-sm hover:bg-forest-700"><i data-lucide="check" class="h-4 w-4"></i>Accepter</button></form>
<details class="rounded-xl border border-red-200" @if($errors->has('motif_refus')) open @endif>
    <summary class="flex cursor-pointer list-none items-center justify-center gap-2 px-4 py-2.5 font-semibold text-red-600 hover:bg-red-50"><i data-lucide="x" class="h-4 w-4"></i>Refuser…</summary>
    <form method="POST" action="{{ $routeRefuser }}" class="space-y-3 border-t border-red-100 p-4">@csrf
        <x-champ label="Motif du refus" name="motif_refus" type="textarea" :required="true" placeholder="Expliquez au particulier pourquoi vous refusez." />
        <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 font-semibold text-white hover:bg-red-700">Confirmer le refus</button>
    </form>
</details>
