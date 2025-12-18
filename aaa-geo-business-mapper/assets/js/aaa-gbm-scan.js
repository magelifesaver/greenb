// File Path: /wp-content/plugins/aaa-geo-business-mapper/assets/js/aaa-gbm-scan.js
(function () {
  const DEBUG_THIS_FILE = true;
  const S = () => window.AAA_GBM.state;

  function log(msg, obj) { if (DEBUG_THIS_FILE) console.log("[AAA_GBM/scan]", msg, obj || ""); }

  function keyForPoint(p) { return `${p.lat.toFixed(6)},${p.lng.toFixed(6)}`; }

  function makeMarker(place, color) {
    const pos = { lat: place.location.latitude, lng: place.location.longitude };
    return new google.maps.Marker({
      position: pos,
      map: S().map,
      title: (place.displayName && place.displayName.text) ? place.displayName.text : "Place",
      icon: { path: google.maps.SymbolPath.CIRCLE, scale: 6, fillColor: color, fillOpacity: 0.9, strokeWeight: 1 },
    });
  }

  async function runOneRequest(L, p, radius, queryOrTypes) {
    if (L.mode === "text") {
      return window.AAA_GBM.ajax("aaa_gbm_search_text", { q: queryOrTypes, lat: p.lat, lng: p.lng, radius });
    }
    return window.AAA_GBM.ajax("aaa_gbm_search_nearby", { types: L.types, lat: p.lat, lng: p.lng, radius });
  }

  function ingestPlaces(L, places) {
    const seen = new Set(L.placeIds);
    let added = 0;

    (places || []).forEach((place) => {
      if (!place.id || !place.location || seen.has(place.id)) return;
      seen.add(place.id);

      L.placeIds.push(place.id);
      L.places.push(place);

      const m = makeMarker(place, L.color);
      L.markers.push(m);
      added++;
    });

    return added;
  }

  function refineChildren(p, spacingM) {
    const dLat = (spacingM / 2) / 111320;
    const dLng = (spacingM / 2) / (111320 * Math.cos((p.lat * Math.PI) / 180));

    const kids = [
      { lat: p.lat + dLat, lng: p.lng },
      { lat: p.lat - dLat, lng: p.lng },
      { lat: p.lat, lng: p.lng + dLng },
      { lat: p.lat, lng: p.lng - dLng },
    ];

    return kids.filter((k) => window.AAA_GBM.pointInShape(k.lat, k.lng));
  }

  async function runScanForLayer(L) {
    if (!S().shape) throw new Error("No shape drawn");
    const spacing = parseFloat(document.getElementById("aaa-gbm-grid-spacing").value || "2500");
    const radius = parseFloat(document.getElementById("aaa-gbm-query-radius").value || "1800");

    const adaptive = document.getElementById("aaa-gbm-adaptive").checked;
    const trigger = parseInt(document.getElementById("aaa-gbm-refine-trigger").value || "18", 10);
    const maxDepth = parseInt(document.getElementById("aaa-gbm-refine-depth").value || "2", 10);

    const initial = window.AAA_GBM.gridPointsFromShape(S().shapeType, S().shape, spacing);
    const q = initial.map((p) => ({ p, depth: 0, spacing }));

    const visited = new Set();

    window.AAA_GBM.util.setStatus(`Scanning ${initial.length} tiles... (Places returns up to 20 per request)`);
    let step = 0;

    while (q.length) {
      const cur = q.shift();
      const k = keyForPoint(cur.p);
      if (visited.has(k)) continue;
      visited.add(k);

      step++;
      window.AAA_GBM.util.setStatus(`Tile ${step} | Places: ${L.markers.length} | Queue: ${q.length}`);

      const requests = (L.mode === "text") ? (L.queries || []) : ["_types_"];
      let maxReturned = 0;

      for (let i = 0; i < requests.length; i++) {
        const r = await runOneRequest(L, cur.p, radius, requests[i]);
        if (!r.success) { log("API error", r); continue; }

        const places = (r.data && r.data.places) ? r.data.places : [];
        maxReturned = Math.max(maxReturned, places.length);
        ingestPlaces(L, places);

        window.AAA_GBM.ui.rebuildCluster();
        await new Promise((res) => setTimeout(res, 200));
      }

      if (adaptive && cur.depth < maxDepth && maxReturned >= trigger) {
        const kids = refineChildren(cur.p, cur.spacing);
        kids.forEach((kp) => q.push({ p: kp, depth: cur.depth + 1, spacing: cur.spacing / 2 }));
      }
    }

    window.AAA_GBM.util.setStatus(`Done. Layer "${L.name}" places: ${L.markers.length}`);
    window.AAA_GBM.ui.renderLayers();
  }

  document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("aaa-gbm-run-scan").addEventListener("click", async () => {
      const L = S().layers.find((x) => x.selected);
      if (!L) return window.AAA_GBM.util.setStatus("Select a layer first.");
      try { await runScanForLayer(L); } catch (e) { window.AAA_GBM.util.setStatus(e.message); }
    });
  });

  log("ready");
})();
