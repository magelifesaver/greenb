// Layer management: add, select, toggle visibility, remove. Maintains
// the list of layers and updates the UI accordingly. When scan button
// is clicked, delegates to the scan module.

(function () {
  const DEBUG_THIS_FILE = true;
  const S = () => window.AAA_GBM.state;
  function log(msg, obj) {
    if (!DEBUG_THIS_FILE) return;
    console.log('[AAA_GBM/layers]', msg, obj || '');
  }
  // Render the list of layers. Each item displays the name, marker count,
  // weight and provides Toggle, Select and Remove buttons. Buttons are
  // wired after rendering.
  function renderLayers() {
    const el = document.getElementById('aaa-gbm-layer-list');
    if (!el) return;
    el.innerHTML = '';
    S().layers.forEach((L, idx) => {
      const div = document.createElement('div');
      div.className = 'aaa-gbm-layer-item';
      div.innerHTML =
        `<strong>${L.name}</strong>` +
        `<span class="aaa-gbm-chip" style="background:${L.color};color:#fff">${L.mode}</span>` +
        `<div style="margin-top:6px">Markers: ${L.markers.length} | Weight: ${L.weight}</div>` +
        `<button class="button" data-idx="${idx}" data-act="toggle">${L.visible ? 'Hide' : 'Show'}</button> ` +
        `<button class="button" data-idx="${idx}" data-act="select">Select</button> ` +
        `<button class="button" data-idx="${idx}" data-act="remove">Remove</button>`;
      el.appendChild(div);
    });
    el.querySelectorAll('button').forEach((b) => {
      b.addEventListener('click', () => {
        const idx = parseInt(b.dataset.idx, 10);
        const act = b.dataset.act;
        if (act === 'toggle') toggleLayer(idx);
        if (act === 'select') selectLayer(idx);
        if (act === 'remove') removeLayer(idx);
      });
    });
  }
  // Select a layer as the active one for scanning. Only one layer can be
  // selected at a time.
  function selectLayer(idx) {
    S().layers.forEach((L, i) => (L.selected = i === idx));
    window.AAA_GBM.util.setStatus(`Selected layer: ${S().layers[idx].name}`);
    renderLayers();
  }
  // Toggle visibility of a layer. When hidden, its markers are removed
  // from the map; when shown, markers are added back. Clusterer is
  // rebuilt afterwards.
  function toggleLayer(idx) {
    const L = S().layers[idx];
    L.visible = !L.visible;
    L.markers.forEach((m) => m.setMap(L.visible ? S().map : null));
    rebuildCluster();
    renderLayers();
  }
  // Remove a layer entirely. Its markers are removed from the map and
  // clusterer. The layer is spliced from the list.
  function removeLayer(idx) {
    const L = S().layers[idx];
    L.markers.forEach((m) => m.setMap(null));
    S().layers.splice(idx, 1);
    rebuildCluster();
    renderLayers();
    window.AAA_GBM.util.setStatus(`Removed layer: ${L.name}`);
  }
  // Rebuild marker clusterer for all visible layers.
  function rebuildCluster() {
    if (!window.markerClusterer || !window.markerClusterer.MarkerClusterer) return;
    const all = S().layers.flatMap((L) => (L.visible ? L.markers : []));
    if (S().clusterer) S().clusterer.setMap(null);
    S().clusterer = new markerClusterer.MarkerClusterer({ map: S().map, markers: all });
  }
  // Called when Add Layer button is clicked. Reads UI inputs and creates
  // a new layer with appropriate properties. Validates inputs based on
  // mode: Nearby requires types; Text requires a query; Multi Text
  // requires at least one query.
  function addLayerFromUI() {
    const name = (document.getElementById('aaa-gbm-layer-name').value || '').trim() || 'Layer';
    const mode = document.getElementById('aaa-gbm-layer-mode').value;
    const types = (document.getElementById('aaa-gbm-layer-types').value || '').split(',').map((s) => s.trim()).filter(Boolean);
    const textQuery = (document.getElementById('aaa-gbm-layer-text').value || '').trim();
    const multi = (document.getElementById('aaa-gbm-layer-multi').value || '').split('\n').map((s) => s.trim()).filter(Boolean);
    const color = document.getElementById('aaa-gbm-layer-color').value || '#1e73be';
    const weight = parseFloat(document.getElementById('aaa-gbm-layer-weight').value || '1');
    // Validate inputs per mode.
    if (mode === 'nearby' && types.length === 0) {
      window.AAA_GBM.util.setStatus('Types required for Nearby mode (e.g., gym,fitness_center).');
      return;
    }
    if (mode === 'text' && !textQuery) {
      window.AAA_GBM.util.setStatus('Text query required for Text mode.');
      return;
    }
    if (mode === 'multi-text' && multi.length === 0) {
      window.AAA_GBM.util.setStatus('At least one query required for Multi Text mode.');
      return;
    }
    // Build the layer object. For Text mode, wrap query into array for
    // consistency with multi mode. For Nearby mode, queries array is empty.
    const L = {
      name,
      mode,
      types,
      queries: mode === 'multi-text' ? multi : (mode === 'text' ? [textQuery] : []),
      color,
      weight,
      visible: true,
      selected: true,
      markers: [],
      places: [],
      placeIds: [],
    };
    // Deselect other layers and add this one.
    S().layers.forEach((x) => (x.selected = false));
    S().layers.push(L);
    renderLayers();
    window.AAA_GBM.util.setStatus(`Added layer "${name}". Click Run Scan.`);
  }
  // Reset all layers and markers. Clears map, clusterer and best markers.
  function resetAll() {
    S().layers.forEach((L) => L.markers.forEach((m) => m.setMap(null)));
    S().layers = [];
    if (S().clusterer) S().clusterer.setMap(null);
    S().bestMarkers.forEach((m) => m.setMap(null));
    S().bestMarkers = [];
    window.AAA_GBM.util.setStatus('Reset complete. Draw a new area and add layers.');
    document.getElementById('aaa-gbm-best-list').innerHTML = '';
    renderLayers();
  }
  // DOM events
  document.addEventListener('DOMContentLoaded', () => {
    // Add layer
    document.getElementById('aaa-gbm-add-layer').addEventListener('click', addLayerFromUI);
    // Run scan button triggers scanning for selected layer via scan module.
    document.getElementById('aaa-gbm-run-scan').addEventListener('click', async () => {
      const L = S().layers.find((x) => x.selected);
      if (!L) {
        return window.AAA_GBM.util.setStatus('Select a layer first.');
      }
      try {
        await window.AAA_GBM.runScanForLayer(L);
      } catch (e) {
        window.AAA_GBM.util.setStatus(e.message);
      }
    });
    // Reset map and layers.
    document.getElementById('aaa-gbm-reset').addEventListener('click', resetAll);
  });
  // Expose helpers for other modules.
  window.AAA_GBM.renderLayers = renderLayers;
  window.AAA_GBM.rebuildCluster = rebuildCluster;
  log('ready');
})();