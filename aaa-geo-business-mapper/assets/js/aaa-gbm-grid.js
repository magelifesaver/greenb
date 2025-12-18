// File Path: /wp-content/plugins/aaa-geo-business-mapper/assets/js/aaa-gbm-grid.js
(function () {
  const DEBUG_THIS_FILE = true;
  const S = () => window.AAA_GBM.state;

  function log(msg, obj) { if (DEBUG_THIS_FILE) console.log("[AAA_GBM/grid]", msg, obj || ""); }

  function metersToLat(m) { return m / 111320; }
  function metersToLng(m, lat) { return m / (111320 * Math.cos((lat * Math.PI) / 180)); }

  function boundsFromShape(shapeType, shape) {
    if (shapeType === "circle" || shapeType === "rectangle") return shape.getBounds();
    if (shapeType === "polygon") {
      const b = new google.maps.LatLngBounds();
      shape.getPath().forEach((p) => b.extend(p));
      return b;
    }
    return null;
  }

  window.AAA_GBM.pointInShape = function pointInShape(lat, lng) {
    const st = S();
    if (!st.shape) return false;
    const LL = new google.maps.LatLng(lat, lng);

    if (st.shapeType === "circle") {
      return google.maps.geometry.spherical.computeDistanceBetween(LL, st.shape.getCenter()) <= st.shape.getRadius();
    }
    if (st.shapeType === "rectangle") return st.shape.getBounds().contains(LL);
    if (st.shapeType === "polygon") return google.maps.geometry.poly.containsLocation(LL, st.shape);
    return false;
  };

  window.AAA_GBM.gridPointsFromShape = function gridPointsFromShape(shapeType, shape, spacingM) {
    const b = boundsFromShape(shapeType, shape);
    if (!b) return [];
    const ne = b.getNorthEast(), sw = b.getSouthWest();
    const midLat = (ne.lat() + sw.lat()) / 2;

    const dLat = metersToLat(spacingM);
    const dLng = metersToLng(spacingM, midLat);

    const pts = [];
    for (let lat = sw.lat(); lat <= ne.lat(); lat += dLat) {
      for (let lng = sw.lng(); lng <= ne.lng(); lng += dLng) {
        if (window.AAA_GBM.pointInShape(lat, lng)) pts.push({ lat, lng });
      }
    }
    log("gridPoints", { count: pts.length, spacingM });
    return pts;
  };
})();
