// Heatmap overlay for AAA Geo Business Mapper. Uses heatmap.js to draw a
// density map over the Google map based on visible places. The heatmap
// can be toggled via a button in the UI. The overlay is added once and
// hidden/shown on toggle.

(function () {
  const DEBUG_THIS_FILE = true;
  const S = () => window.AAA_GBM.state;
  function log(msg, obj) {
    if (!DEBUG_THIS_FILE) return;
    console.log('[AAA_GBM/heat]', msg, obj || '');
  }
  let overlay = null;
  let hm = null;
  // Build overlay if not already created. This attaches to map's overlay
  // layer and instantiates heatmap.js with default options.
  function buildOverlay() {
    if (!window.h337) return;
    if (overlay) return;
    overlay = new google.maps.OverlayView();
    overlay.onAdd = function () {
      const div = document.createElement('div');
      div.style.position = 'absolute';
      div.style.top = '0';
      div.style.left = '0';
      div.style.width = '100%';
      div.style.height = '100%';
      div.id = 'aaa-gbm-heatwrap';
      this.getPanes().overlayLayer.appendChild(div);
      hm = h337.create({ container: div, radius: 20, maxOpacity: 0.6, minOpacity: 0.0, blur: 0.85 });
    };
    overlay.draw = function () {
      renderHeat();
    };
    overlay.onRemove = function () {
      const el = document.getElementById('aaa-gbm-heatwrap');
      if (el && el.parentNode) el.parentNode.removeChild(el);
      hm = null;
    };
    overlay.setMap(S().map);
    log('overlay added');
  }
  // Render heatmap data from visible layers. Transforms lat/lng to pixel
  // coordinates via projection and passes to heatmap.js.
  function renderHeat() {
    if (!overlay || !hm) return;
    const proj = overlay.getProjection();
    if (!proj) return;
    const pts = [];
    const layers = S().layers.filter((L) => L.visible);
    layers.forEach((L) => {
      L.places.forEach((p) => {
        if (!p.location) return;
        const ll = new google.maps.LatLng(p.location.latitude, p.location.longitude);
        const px = proj.fromLatLngToDivPixel(ll);
        pts.push({ x: Math.round(px.x), y: Math.round(px.y), value: Math.max(1, L.weight || 1) });
      });
    });
    hm.setData({ max: 10, data: pts });
  }
  // Toggle heatmap display. Creates overlay if needed. On toggle, show/hide
  // the heatwrap div and re-render heat.
  function toggleHeat() {
    buildOverlay();
    const el = document.getElementById('aaa-gbm-heatwrap');
    if (!el) return;
    const on = el.style.display !== 'none';
    el.style.display = on ? 'none' : 'block';
    window.AAA_GBM.util.setStatus(on ? 'Heatmap hidden.' : 'Heatmap shown.');
    renderHeat();
  }
  // Expose toggle to global so analysis module can call.
  window.AAA_GBM.toggleHeatmap = toggleHeat;
  log('ready');
})();