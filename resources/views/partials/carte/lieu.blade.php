{{-- Lieu d'un événement sur la carte, avec itinéraire. Attend $evenement. --}}
@if($evenement->estLocalise())
@include('partials.carte.leaflet')
<div class="mt-6">
    <div data-carte-lieu data-lat="{{ $evenement->latitude }}" data-lng="{{ $evenement->longitude }}" data-titre="{{ $evenement->titre }}" data-lieu="{{ $evenement->lieu }}"
         class="relative z-0 h-72 w-full overflow-hidden rounded-2xl ring-1 ring-slate-200" role="img" aria-label="Carte : {{ $evenement->lieu }}"></div>
    <div class="mt-3 flex flex-wrap gap-3">
        <a href="{{ $evenement->lienItineraire() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-700"><i data-lucide="navigation" class="h-4 w-4"></i>Itinéraire (Google Maps)</a>
        <a href="{{ $evenement->lienCarte() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 hover:text-forest-700"><i data-lucide="map" class="h-4 w-4"></i>Voir sur OpenStreetMap</a>
    </div>
</div>
@push('scripts')
<script>
    document.querySelectorAll('[data-carte-lieu]:not([data-prete])').forEach(element => {
        element.dataset.prete = '1';
        const position = [Number(element.dataset.lat), Number(element.dataset.lng)];
        const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
        const carte = carteOsm(element, position, 16);
        L.marker(position).addTo(carte).bindPopup(`<strong>${esc(element.dataset.titre)}</strong><br>${esc(element.dataset.lieu)}`).openPopup();
    });
</script>
@endpush
@else
<a href="{{ $evenement->lienItineraire() }}" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-forest-700 hover:underline"><i data-lucide="navigation" class="h-4 w-4"></i>Itinéraire vers « {{ $evenement->lieu }} » (Google Maps)</a>
@endif
