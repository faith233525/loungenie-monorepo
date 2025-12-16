(function() {
    const el = document.getElementById('lgp-company-map');
    if (!el || !window.lgpCompanyMap || !Array.isArray(window.lgpCompanyMap.markers)) return;

    const { markers, tileUrl, tileAttribution } = window.lgpCompanyMap;
    const map = L.map(el, { scrollWheelZoom: false });
    L.tileLayer(tileUrl, { attribution: tileAttribution }).addTo(map);

    if (!markers.length) {
        map.setView([39.5, -98.35], 4); // continental US default
        return;
    }

    const layerGroup = L.layerGroup().addTo(map);
    markers.forEach(marker => {
        if (typeof marker.lat !== 'number' || typeof marker.lng !== 'number') return;
        const popup = `<strong>${marker.name || 'Company'}</strong>` + (marker.type ? `<br><em>${marker.type}</em>` : '');
        L.marker([marker.lat, marker.lng]).bindPopup(popup).addTo(layerGroup);
    });

    const bounds = L.latLngBounds(markers.map(m => [m.lat, m.lng]));
    map.fitBounds(bounds.pad(0.15));
})();
