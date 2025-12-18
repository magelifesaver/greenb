// Map initialisation and drawing manager.
// This file registers the AAA_GBM global state and exposes initMap().
// It must run after the Google Maps API has loaded. Logging is enabled
// via DEBUG_THIS_FILE constant.

(function () {
  const DEBUG_THIS_FILE = true;
  function log(msg, obj) {
    if (!DEBUG_THIS_FILE) return;
    console.log('[AAA_GBM/map]', msg, obj || '');
  }

  // Global state used across modules. The shape drawn defines the search
  // area; layers hold marker sets and meta information. clusterer groups
  // markers. bestMarkers show top scoring candidate spots. The adaptive flag
  // toggles tile refinement.
  window.AAA_GBM = window.AAA_GBM || {};
  window.AAA_GBM.state = {
    map: null,
    drawing: null,
    shape: null,
    shapeType: null,
    layers: [],
    clusterer: null,
    bestMarkers: [],
    adaptive: true,
  };

  // Utility functions accessible globally.
  window.AAA_GBM.util = {
    setStatus: (t) => {
      const el = document.getElementById('aaa-gbm-status');
      if (el) el.textContent = t || '';
    },
  };

  // Initialise map and drawing tools when Maps JS is ready.
  window.AAA_GBM.initMap = function () {
    const wrap = document.getElementById('aaa-gbm-map');
    if (!wrap || !window.google || !google.maps) {
      window.AAA_GBM.util.setStatus('Google Maps not loaded. Check Browser API key.');
      return;
    }
    const map = new google.maps.Map(wrap, { center: { lat: 34.05, lng: -118.25 }, zoom: 11 });
    window.AAA_GBM.state.map = map;
    // Setup drawing manager for polygon/rectangle/circle.
    const drawing = new google.maps.drawing.DrawingManager({
      drawingControl: true,
      drawingControlOptions: {
        position: google.maps.ControlPosition.TOP_CENTER,
        drawingModes: ['polygon', 'rectangle', 'circle'],
      },
      polygonOptions: { editable: true },
      rectangleOptions: { editable: true },
      circleOptions: { editable: true },
    });
    drawing.setMap(map);
    window.AAA_GBM.state.drawing = drawing;
    // When user completes drawing, store the shape and type. Remove any
    // previous shape to avoid clutter. Reset best markers and clear
    // clusters. Inform the user to add a layer next.
    google.maps.event.addListener(drawing, 'overlaycomplete', (e) => {
      log('overlaycomplete', e.type);
      if (window.AAA_GBM.state.shape) window.AAA_GBM.state.shape.setMap(null);
      window.AAA_GBM.state.shape = e.overlay;
      window.AAA_GBM.state.shapeType = e.type;
      drawing.setDrawingMode(null);
      // Clear previous best markers when new shape is drawn.
      window.AAA_GBM.state.bestMarkers.forEach((m) => m.setMap(null));
      window.AAA_GBM.state.bestMarkers = [];
      window.AAA_GBM.util.setStatus('Area set. Add a layer and run scan.');
    });
    window.AAA_GBM.util.setStatus('Draw a polygon/rectangle/circle to define the scan area.');
    log('map ready');
  };

  // Wait until DOM ready and Google Maps library loaded before init.
  document.addEventListener('DOMContentLoaded', () => {
    const wait = setInterval(() => {
      if (window.google && google.maps && google.maps.drawing) {
        clearInterval(wait);
        window.AAA_GBM.initMap();
      }
    }, 200);
  });
})();