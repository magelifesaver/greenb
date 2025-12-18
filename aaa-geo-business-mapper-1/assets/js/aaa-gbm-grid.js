// Grid helper functions. Provides methods to convert metres to lat/lng
// distances and to generate a grid of points inside the drawn shape.
// Also exposes a refine helper for adaptive scanning.

(function () {
  const DEBUG_THIS_FILE = true;
  function log(msg, obj) {
    if (!DEBUG_THIS_FILE) return;
    console.log('[AAA_GBM/grid]', msg, obj || '');
  }
  // Convert metres to degrees latitude.
  function metersToLat(m) { return m / 111320; }
  // Convert metres to degrees longitude at a given latitude.
  function metersToLng(m, lat) { return m / (111320 * Math.cos((lat * Math.PI) / 180)); }
  // Determine bounds from shape.
  function boundsFromShape(type, shape) {
    if (type === 'circle') return shape.getBounds();
    if (type === 'rectangle') return shape.getBounds();
    if (type === 'polygon') {
      const b = new google.maps.LatLngBounds();
      shape.getPath().forEach((p) => b.extend(p));
      return b;
    }
    return null;
  }
  // Generate grid points inside the shape. spacingM is the spacing in metres.
  window.AAA_GBM.gridPointsFromShape = function (type, shape, spacingM) {
    const b = boundsFromShape(type, shape);
    if (!b) return [];
    const ne = b.getNorthEast();
    const sw = b.getSouthWest();
    const midLat = (ne.lat() + sw.lat()) / 2;
    const dLat = metersToLat(spacingM);
    const dLng = metersToLng(spacingM, midLat);
    const pts = [];
    for (let lat = sw.lat(); lat <= ne.lat(); lat += dLat) {
      for (let lng = sw.lng(); lng <= ne.lng(); lng += dLng) {
        const LL = new google.maps.LatLng(lat, lng);
        let ok = false;
        if (type === 'circle') ok = (google.maps.geometry.spherical.computeDistanceBetween(LL, shape.getCenter()) <= shape.getRadius());
        if (type === 'rectangle') ok = shape.getBounds().contains(LL);
        if (type === 'polygon') ok = google.maps.geometry.poly.containsLocation(LL, shape);
        if (ok) pts.push({ lat, lng });
      }
    }
    log('gridPoints', { count: pts.length, spacingM });
    return pts;
  };
  // Adaptive refinement: when a tile returns near the max results, subdivide
  // into four smaller tiles. Returns an array of new points offset from the
  // original centre by half the spacing. Not used by itself; scanning logic
  // calls this.
  window.AAA_GBM.refineTile = function (point, spacingM) {
    const half = spacingM / 2;
    const latOff = metersToLat(half);
    const lngOff = metersToLng(half, point.lat);
    return [
      { lat: point.lat + latOff, lng: point.lng + lngOff },
      { lat: point.lat + latOff, lng: point.lng - lngOff },
      { lat: point.lat - latOff, lng: point.lng + lngOff },
      { lat: point.lat - latOff, lng: point.lng - lngOff },
    ];
  };
})();