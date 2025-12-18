// Scanning logic for AAA Geo Business Mapper. Given a layer and the drawn
// shape, this module generates grid points, performs Places API calls
// per point and per query, deduplicates results and populates markers.

(function () {
  const DEBUG_THIS_FILE = true;
  const S = () => window.AAA_GBM.state;
  function log(msg, obj) {
    if (!DEBUG_THIS_FILE) return;
    console.log('[AAA_GBM/scan]', msg, obj || '');
  }
  // Helper to create a marker for a place.
  function makeMarker(place, color) {
    const loc = place.location;
    const pos = { lat: loc.latitude, lng: loc.longitude };
    return new google.maps.Marker({
      position: pos,
      map: S().map,
      title: place.displayName && place.displayName.text ? place.displayName.text : 'Place',
      icon: { path: google.maps.SymbolPath.CIRCLE, scale: 6, fillColor: color, fillOpacity: 0.9, strokeWeight: 1 },
    });
  }
  /**
   * Run a scan for a single layer. Generates grid points inside the shape,
   * then iterates over each point and each query (if applicable), calling
   * the appropriate AJAX endpoint. Refines tiles if results near max.
   *
   * @param {Object} L Layer definition
   */
  async function runScanForLayer(L) {
    if (!S().shape) throw new Error('No area drawn.');
    const spacing = parseFloat(document.getElementById('aaa-gbm-grid-spacing').value || '3000');
    const radius  = parseFloat(document.getElementById('aaa-gbm-query-radius').value || '2500');
    const adaptive = document.getElementById('aaa-gbm-adaptive').checked;
    S().adaptive = adaptive;
    // Generate initial grid points.
    let queue = window.AAA_GBM.gridPointsFromShape(S().shapeType, S().shape, spacing);
    if (queue.length === 0) throw new Error('No grid points. Try expanding the area or decreasing spacing.');
    const seen = new Set(L.placeIds);
    let processed = 0;
    while (queue.length > 0) {
      const point = queue.shift();
      processed++;
      // For each query, perform either text or nearby search.
      const queryList = (L.mode === 'text' || L.mode === 'multi-text') ? L.queries : ['__nearby__'];
      for (let qi = 0; qi < queryList.length; qi++) {
        const q = queryList[qi];
        let res;
        if (L.mode === 'nearby') {
          res = await window.AAA_GBM.ajax('aaa_gbm_search_nearby', { types: L.types, lat: point.lat, lng: point.lng, radius: radius });
        } else {
          res = await window.AAA_GBM.ajax('aaa_gbm_search_text', { q: q, lat: point.lat, lng: point.lng, radius: radius });
        }
        if (!res.success) {
          log('API error', res);
          window.AAA_GBM.util.setStatus('AJAX error: ' + (res.data && res.data.message ? res.data.message : 'Unknown')); 
          continue;
        }
        const places = res.data.places || [];
        // Deduplicate and add markers.
        places.forEach((place) => {
          if (!place.id || seen.has(place.id) || !place.location) return;
          seen.add(place.id);
          L.placeIds.push(place.id);
          L.places.push(place);
          const m = makeMarker(place, L.color);
          L.markers.push(m);
        });
        // Adaptive refine: if results hit near cap (>=18) and spacing large enough, subdivide.
        if (adaptive && places.length >= 18 && spacing > 400) {
          const newPts = window.AAA_GBM.refineTile(point, spacing);
          queue.push(...newPts);
        }
        // Update status after each query for transparency.
        window.AAA_GBM.util.setStatus(`Tile ${processed} | Query ${qi + 1}/${queryList.length} | Results: ${places.length} | Queue: ${queue.length} | Total markers: ${L.markers.length}`);
        // Short pause to avoid hammering server
        await new Promise((r) => setTimeout(r, 200));
      }
      // Rebuild clusterer occasionally to avoid memory bloat.
      if (processed % 10 === 0) {
        window.AAA_GBM.rebuildCluster();
      }
    }
    // Final cluster rebuild.
    window.AAA_GBM.rebuildCluster();
    // If no markers, inform user.
    if (L.markers.length === 0) {
      window.AAA_GBM.util.setStatus('Scan finished. No places found.');
    } else {
      window.AAA_GBM.util.setStatus(`Scan finished. Layer "${L.name}" found ${L.markers.length} places.`);
    }
    window.AAA_GBM.renderLayers();
  }
  // Expose to global for other modules to call.
  window.AAA_GBM.runScanForLayer = runScanForLayer;
  log('ready');
})();