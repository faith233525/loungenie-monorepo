const path = require('path');

describe('lgp-map renderer', () => {
  let map;
  let layerGroupObj;
  let markerBindPopup;
  let markerAddTo;

  const loadMap = () => {
    jest.resetModules();
    document.body.innerHTML = '<div id="lgp-company-map"></div>';

    map = { setView: jest.fn(), fitBounds: jest.fn() };
    layerGroupObj = {};
    markerAddTo = jest.fn();
    markerBindPopup = jest.fn().mockReturnValue({ addTo: markerAddTo });

    global.L = {
      map: jest.fn(() => map),
      tileLayer: jest.fn(() => ({ addTo: jest.fn() })),
      layerGroup: jest.fn(() => ({ addTo: jest.fn(() => layerGroupObj) })),
      marker: jest.fn(() => ({ bindPopup: markerBindPopup })),
      latLngBounds: jest.fn(() => ({ pad: jest.fn(() => ({ bounds: true })) })),
    };
  };

  test('plots markers with popup content', () => {
    loadMap();
    global.window.lgpCompanyMap = {
      markers: [
        { name: 'Acme Pools', type: 'Resort', lat: 10.5, lng: -20.25 },
      ],
      tileUrl: 'tiles',
      tileAttribution: 'attr',
    };

    require(path.join('..', 'js', 'lgp-map'));

    expect(global.L.map).toHaveBeenCalledWith(document.getElementById('lgp-company-map'), { scrollWheelZoom: false });
    expect(global.L.marker).toHaveBeenCalledWith([10.5, -20.25]);
    expect(markerBindPopup).toHaveBeenCalledWith(expect.stringContaining('Acme Pools'));
    expect(markerBindPopup).toHaveBeenCalledWith(expect.stringContaining('Resort'));
    expect(markerAddTo).toHaveBeenCalledWith(layerGroupObj);
    expect(map.fitBounds).toHaveBeenCalled();
  });

  test('falls back to default view when no markers exist', () => {
    loadMap();
    global.window.lgpCompanyMap = {
      markers: [],
      tileUrl: 'tiles',
      tileAttribution: 'attr',
    };

    require(path.join('..', 'js', 'lgp-map'));

    expect(map.setView).toHaveBeenCalledWith([39.5, -98.35], 4);
    expect(global.L.marker).not.toHaveBeenCalled();
  });
});
