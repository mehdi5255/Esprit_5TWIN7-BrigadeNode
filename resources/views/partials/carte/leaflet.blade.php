{{-- Bibliothèque de cartes Leaflet + fonds OpenStreetMap (gratuits, sans clé). Chargée une seule fois par page. --}}
@once
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>.leaflet-container { font: inherit; } .leaflet-popup-content { margin: 10px 14px; }</style>
@endpush
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Carte OpenStreetMap prête à l'emploi (attribution obligatoire des contributeurs OSM).
    window.carteOsm = (element, centre, zoom) => {
        const carte = L.map(element, { scrollWheelZoom: false }).setView(centre, zoom);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(carte);
        carte.on('focus', () => carte.scrollWheelZoom.enable());
        return carte;
    };
</script>
@endpush
@endonce
