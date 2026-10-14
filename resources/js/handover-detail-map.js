import { loadMaps } from './handover-map';

function initializeHandoverMaps() {
    for (const wrapper of document.querySelectorAll('[data-handover-detail-map]')) {
        if (wrapper.dataset.initialized) continue;
        wrapper.dataset.initialized = 'true';
        const status = wrapper.querySelector('[data-map-status]');
        const canvas = wrapper.querySelector('[data-map-canvas]');
        const center = { lat: Number(wrapper.dataset.lat), lng: Number(wrapper.dataset.lng) };
        const timer = setTimeout(() => { status.textContent = 'Peta belum tersedia. Buka tautan Google Maps di bawah untuk melihat lokasi.'; }, 15000);
        loadMaps(wrapper.dataset.key).then(async maps => {
            const { Map } = await maps.importLibrary('maps');
            if (!wrapper.isConnected) return;
            const map = new Map(canvas, { center, zoom: 16, mapId: 'DEMO_MAP_ID', mapTypeControl: false, streetViewControl: false, gestureHandling: 'cooperative' });
            maps.event.addListenerOnce(map, 'tilesloaded', () => { clearTimeout(timer); status.hidden = true; });
            const { AdvancedMarkerElement } = await maps.importLibrary('marker');
            if (wrapper.isConnected) new AdvancedMarkerElement({ map, position: center, title: 'Lokasi serah terima pembeli' });
        }).catch(() => {
            clearTimeout(timer);
            status.textContent = 'Peta tidak dapat dimuat. Buka lokasi melalui tautan Google Maps di bawah.';
        });
    }
}
initializeHandoverMaps();
document.addEventListener('livewire:navigated', initializeHandoverMaps);
if (window.Livewire) window.Livewire.hook('morphed', initializeHandoverMaps);
else document.addEventListener('livewire:initialized', () => window.Livewire.hook('morphed', initializeHandoverMaps), { once: true });
