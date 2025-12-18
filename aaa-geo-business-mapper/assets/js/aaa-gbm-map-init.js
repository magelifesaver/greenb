// File Path: /wp-content/plugins/aaa-geo-business-mapper/assets/js/aaa-gbm-map-init.js
(function () {
  const DEBUG_THIS_FILE = true;

  function log(msg, obj) { if (DEBUG_THIS_FILE) console.log("[AAA_GBM/map]", msg, obj || ""); }

  window.AAA_GBM = window.AAA_GBM || {};
  window.AAA_GBM.state = {
    map: null,
    drawing: null,
    shape: null,
    shapeType: null,
    layers: [],
    clusterer: null,
    bestMarkers: [],
  };

  window.AAA_GBM.util = {
    setStatus: (t) => {
      const el = document.getElementById("aaa-gbm-status");
      if (el) el.textContent = t || "";
    },
  };

  window.AAA_GBM.initMap = function initMap() {
    const wrap = document.getElementById("aaa-gbm-map");
    if (!wrap || !window.google || !google.maps || !google.maps.drawing) {
      window.AAA_GBM.util.setStatus("Google Maps not loaded. Check Browser API key.");
      return;
    }

    const map = new google.maps.Map(wrap, { center: { lat: 34.11, lng: -117.65 }, zoom: 11 });
    window.AAA_GBM.state.map = map;

    const drawing = new google.maps.drawing.DrawingManager({
      drawingControl: true,
      drawingControlOptions: { position: google.maps.ControlPosition.TOP_CENTER, drawingModes: ["rectangle", "circle", "polygon"] },
      polygonOptions: { editable: true },
      rectangleOptions: { editable: true },
      circleOptions: { editable: true },
    });

    drawing.setMap(map);
    window.AAA_GBM.state.drawing = drawing;

    google.maps.event.addListener(drawing, "overlaycomplete", (e) => {
      if (window.AAA_GBM.state.shape) window.AAA_GBM.state.shape.setMap(null);
      window.AAA_GBM.state.shape = e.overlay;
      window.AAA_GBM.state.shapeType = e.type;
      drawing.setDrawingMode(null);
      window.AAA_GBM.util.setStatus("Area set. Add/select a layer, then run scan.");
      log("overlaycomplete", e.type);
    });

    window.AAA_GBM.util.setStatus("Draw a rectangle/circle/polygon to define scan area.");
    log("ready");
  };

  document.addEventListener("DOMContentLoaded", () => {
    const wait = setInterval(() => {
      if (window.google && google.maps && google.maps.drawing) {
        clearInterval(wait);
        window.AAA_GBM.initMap();
      }
    }, 200);
  });
})();
