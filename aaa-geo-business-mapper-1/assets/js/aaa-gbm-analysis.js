// Analysis functions: scoring candidate points to find best locations for
// business placement. Also attaches event listeners for scoring button.

(function () {
  const DEBUG_THIS_FILE = true;
  const S = () => window.AAA_GBM.state;
  function log(msg, obj) {
    if (!DEBUG_THIS_FILE) return;
    console.log('[AAA_GBM/analysis]', msg, obj || '');
  }
  // Haversine distance between two coords in metres.
  function haversineM(a, b) {
    const R = 6371000;
    const dLat = ((b.lat - a.lat) * Math.PI) / 180;
    const dLng = ((b.lng - a.lng) * Math.PI) / 180;
    const s1 = Math.sin(dLat / 2) ** 2;
    const s2 = Math.cos((a.lat * Math.PI) / 180) * Math.cos((b.lat * Math.PI) / 180) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(s1 + s2));
  }
  // Score candidate points and display top results. Called when user clicks
  // 'Find Best Spots'.
  function scoreCandidates() {
    if (!S().shape) return window.AAA_GBM.util.setStatus('Draw an area first.');
    const scoreR = parseFloat(document.getElementById('aaa-gbm-score-radius').value || '1600');
    const gridM  = parseFloat(document.getElementById('aaa-gbm-score-grid').value || '800');
    // Collect all visible places weighted by layer weight.
    const places = S().layers.filter((L) => L.visible).flatMap((L) => L.places.map((p) => ({ p, w: L.weight })));
    if (places.length === 0) return window.AAA_GBM.util.setStatus('No places loaded. Run a scan first.');
    const candidates = window.AAA_GBM.gridPointsFromShape(S().shapeType, S().shape, gridM);
    window.AAA_GBM.util.setStatus(`Scoring ${candidates.length} candidate points...`);
    let best = [];
    candidates.forEach((c) => {
      let score = 0;
      places.forEach(({ p, w }) => {
        const loc = { lat: p.location.latitude, lng: p.location.longitude };
        if (haversineM(c, loc) <= scoreR) score += w;
      });
      best.push({ c, score });
    });
    best.sort((a, b) => b.score - a.score);
    best = best.slice(0, 5);
    // Remove old best markers and add new ones.
    S().bestMarkers.forEach((m) => m.setMap(null));
    S().bestMarkers = [];
    best.forEach((b, i) => {
      const m = new google.maps.Marker({
        position: b.c,
        map: S().map,
        title: `Best #${i + 1} (score ${b.score})`,
        icon: { path: google.maps.SymbolPath.BACKWARD_CLOSED_ARROW, scale: 6, fillColor: '#000', fillOpacity: 1, strokeWeight: 1 },
      });
      S().bestMarkers.push(m);
    });
    const list = document.getElementById('aaa-gbm-best-list');
    list.innerHTML = best.map((x, i) => `<div>#${i + 1}: score <strong>${x.score}</strong> @ ${x.c.lat.toFixed(5)}, ${x.c.lng.toFixed(5)}</div>`).join('');
    window.AAA_GBM.util.setStatus('Best spots updated.');
  }
  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('aaa-gbm-score').addEventListener('click', scoreCandidates);
    // Heatmap toggle is handled in heatmap module, but we attach button here.
    document.getElementById('aaa-gbm-toggle-heat').addEventListener('click', () => {
      if (window.AAA_GBM.toggleHeatmap) window.AAA_GBM.toggleHeatmap();
    });
  });
  log('ready');
})();