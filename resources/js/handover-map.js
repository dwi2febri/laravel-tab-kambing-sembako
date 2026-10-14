let mapsPromise;
let mapLibraryPromise;
let markerLibraryPromise;
let loadAttempt = 0;

function withTimeout(promise, message, duration = 12000) {
    let timer;
    return Promise.race([promise, new Promise((_, reject) => { timer = setTimeout(() => reject(new Error(message)), duration); })])
        .finally(() => clearTimeout(timer));
}

function loadMaps(key) {
    if (window.google?.maps?.importLibrary) return Promise.resolve(window.google.maps);
    if (!key) return Promise.reject(new Error('Peta belum dikonfigurasi.'));
    if (!mapsPromise) {
        const script = document.createElement('script');
        const callback = `sakinaMapsReady${++loadAttempt}`;
        mapsPromise = withTimeout(new Promise((resolve, reject) => {
            window[callback] = () => { resolve(window.google.maps); delete window[callback]; };
            script.src = `https://maps.googleapis.com/maps/api/js?${new URLSearchParams({ key, loading: 'async', callback, libraries: 'maps,marker', map_ids: 'DEMO_MAP_ID', v: 'weekly', language: 'id', region: 'ID' })}`;
            script.async = true;
            script.onerror = () => reject(new Error('Peta gagal dimuat. Periksa koneksi lalu coba lagi.'));
            document.head.append(script);
        }), 'Koneksi peta terlalu lama. Coba lagi atau gunakan lokasi perangkat.').catch(error => {
            mapsPromise = undefined;
            script.remove();
            window[callback] = () => {};
            throw error;
        });
    }
    return mapsPromise;
}

function prepareMaps(key) {
    if (!mapLibraryPromise) {
        mapLibraryPromise = withTimeout(loadMaps(key).then(maps => maps.importLibrary('maps')), 'Peta belum tersedia. Silakan coba lagi.')
            .catch(error => { mapLibraryPromise = undefined; throw error; });
    }
    if (!markerLibraryPromise) {
        markerLibraryPromise = loadMaps(key).then(maps => maps.importLibrary('marker'))
            .catch(() => { markerLibraryPromise = undefined; return null; });
    }
    return mapLibraryPromise;
}

for (const form of document.querySelectorAll('[data-handover-form]')) {
    const date = form.querySelector('[name="preferred_at"]');
    const latitude = form.querySelector('[name="latitude"]');
    const longitude = form.querySelector('[name="longitude"]');
    const location = form.querySelector('[name="location"]');
    const status = form.querySelector('[data-location-status]');
    const container = form.querySelector('[data-handover-map]');
    const panel = form.querySelector('[data-map-panel]');
    const overlay = form.querySelector('[data-map-loading]');
    const loadingText = form.querySelector('[data-map-loading-text]');
    const retry = form.querySelector('[data-map-retry]');
    const deviceButton = form.querySelector('[data-device-location]');
    let map, marker, opening, renderTimer, geocoderPromise, version = 0;
    const showDay = () => {
        const value = date.value ? new Date(date.value) : null;
        form.querySelector('[data-date-day]').textContent = value && !Number.isNaN(value.getTime())
            ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'full', timeStyle: 'short' }).format(value) : '';
    };
    date.addEventListener('change', showDay);
    showDay();
    const coordinates = () => latitude.value !== '' && longitude.value !== '' ? { lat: Number(latitude.value), lng: Number(longitude.value) } : null;
    const readyMessage = () => coordinates() ? 'Titik lokasi tersimpan. Geser pin untuk menyesuaikan.' : 'Klik titik lokasi serah terima pada peta.';
    function showError(error) {
        clearTimeout(renderTimer);
        panel.setAttribute('aria-busy', 'false');
        overlay.hidden = false;
        overlay.classList.add('is-error');
        loadingText.textContent = error.message;
        retry.hidden = false;
        status.textContent = error.message;
    }
    async function selectPoint(point) {
        const selection = ++version;
        const previous = coordinates();
        if (!previous || previous.lat !== Number(point.lat.toFixed(7)) || previous.lng !== Number(point.lng.toFixed(7))) location.value = '';
        latitude.value = point.lat.toFixed(7);
        longitude.value = point.lng.toFixed(7);
        if (marker) marker.position = point;
        status.textContent = `Titik dipilih: ${latitude.value}, ${longitude.value}. Lengkapi alamat dan patokan lokasi.`;
        try {
            if (!geocoderPromise) {
                geocoderPromise = withTimeout(loadMaps(form.dataset.mapsKey).then(maps => maps.importLibrary('geocoding')).then(({ Geocoder }) => new Geocoder()), 'Alamat belum dapat dimuat.')
                    .catch(error => { geocoderPromise = undefined; throw error; });
            }
            const geocoder = await geocoderPromise;
            const response = await geocoder.geocode({ location: point });
            if (selection === version && response.results[0]) location.value = response.results[0].formatted_address.slice(0, 255);
        } catch { /* Coordinates remain valid and the buyer can type the address. */ }
    }
    function openMap() {
        panel.hidden = false;
        if (map) return Promise.resolve();
        if (opening) return opening;
        overlay.hidden = false;
        overlay.classList.remove('is-error');
        retry.hidden = true;
        loadingText.textContent = 'Menyiapkan peta lokasi...';
        panel.setAttribute('aria-busy', 'true');
        status.textContent = 'Memuat peta...';
        opening = prepareMaps(form.dataset.mapsKey).then(({ Map }) => {
            const center = coordinates() || { lat: -6.9667, lng: 110.4167 };
            map = new Map(container, { center, zoom: coordinates() ? 17 : 12, mapId: 'DEMO_MAP_ID', streetViewControl: false, mapTypeControl: false, fullscreenControl: true, gestureHandling: 'cooperative' });
            map.addListener('click', event => { if (event.latLng) void selectPoint(event.latLng.toJSON()); });
            renderTimer = setTimeout(() => showError(new Error('Peta belum selesai dimuat. Coba lagi atau gunakan lokasi perangkat.')), 12000);
            window.google.maps.event.addListenerOnce(map, 'tilesloaded', () => {
                clearTimeout(renderTimer);
                overlay.hidden = true;
                panel.setAttribute('aria-busy', 'false');
                status.textContent = readyMessage();
            });
            const currentMap = map;
            void markerLibraryPromise?.then(library => {
                if (!library || !map || map !== currentMap) return;
                marker = new library.AdvancedMarkerElement({ map, position: coordinates() || undefined, gmpDraggable: true, title: 'Lokasi serah terima' });
                marker.addListener('dragend', event => { if (event.latLng) void selectPoint(event.latLng.toJSON()); });
            });
        }).catch(error => { showError(error); throw error; }).finally(() => { opening = undefined; });
        return opening;
    }
    form.querySelector('[data-open-map]').addEventListener('click', () => { void openMap().catch(() => {}); });
    retry.addEventListener('click', () => {
        if (map) window.google.maps.event.clearInstanceListeners(map);
        marker?.setMap?.(null);
        if (marker) marker.map = null;
        map = undefined;
        marker = undefined;
        container.replaceChildren();
        void openMap().catch(() => {});
    });
    deviceButton.addEventListener('click', () => {
        if (!navigator.geolocation) { status.textContent = 'Perangkat tidak mendukung lokasi. Silakan pilih lewat peta.'; return; }
        deviceButton.disabled = true;
        status.textContent = 'Mengambil lokasi perangkat...';
        navigator.geolocation.getCurrentPosition(position => {
            const point = { lat: position.coords.latitude, lng: position.coords.longitude };
            deviceButton.disabled = false;
            void selectPoint(point);
            void openMap().then(() => { map.setCenter(point); map.setZoom(17); }).catch(() => {
                status.textContent = 'Lokasi perangkat berhasil diambil. Lengkapi alamat dan patokan lokasi.';
            });
        }, () => {
            deviceButton.disabled = false;
            status.textContent = 'Lokasi belum diizinkan atau tidak tersedia. Izinkan akses lokasi atau pilih lewat peta.';
        }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 });
    });
    form.addEventListener('submit', event => {
        if (!coordinates()) {
            event.preventDefault();
            status.textContent = 'Pilih titik di peta atau gunakan lokasi perangkat sebelum mengirim pengajuan.';
            status.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    });
    void prepareMaps(form.dataset.mapsKey).catch(() => {});
}

export { loadMaps };
