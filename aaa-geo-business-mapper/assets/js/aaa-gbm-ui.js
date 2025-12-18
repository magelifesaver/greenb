// File Path: /wp-content/plugins/aaa-geo-business-mapper/assets/js/aaa-gbm-ui.js
(function () {
  const DEBUG_THIS_FILE = true;
  const S = () => window.AAA_GBM.state;

  function log(msg, obj) { if (DEBUG_THIS_FILE) console.log("[AAA_GBM/ui]", msg, obj || ""); }

  const PRESETS = {
    fitness_types: {
      name: "Fitness (Types)",
      mode: "nearby",
      types: ["gym", "fitness_center", "sports_club", "sports_complex", "sports_activity_location", "sports_coaching", "swimming_pool", "yoga_studio"],
      queries: [],
      color: "#1e73be",
      weight: 1,
    },
    fitness_text: {
      name: "Fitness (Text)",
      mode: "text",
      types: [],
      queries: [
        "personal trainer",
        "strength training",
        "boxing gym",
        "kickboxing",
        "martial arts",
        "karate",
        "taekwondo",
        "jiu jitsu",
        "crossfit",
        "pilates studio",
        "cycling studio",
        "spin class",
        "orange theory",
      ],
      color: "#2e8540",
      weight: 1,
    },
    recovery_comp: {
      name: "Recovery / Wellness",
      mode: "nearby",
      types: ["wellness_center", "spa", "massage", "sauna", "physiotherapist", "chiropractor"],
      queries: [],
      color: "#8a3ffc",
      weight: 1,
    },
  };

  function parseQueries(text) {
    return (text || "")
      .split(/\r?\n/)
      .map((s) => s.trim())
      .filter(Boolean);
  }

  function rebuildCluster() {
    const all = S().layers.flatMap((L) => (L.visible ? L.markers : []));
    if (S().clusterer) S().clusterer.setMap(null);
    if (window.markerClusterer && window.markerClusterer.MarkerClusterer) {
      S().clusterer = new markerClusterer.MarkerClusterer({ map: S().map, markers: all });
    }
  }

  function toggleLayer(idx) {
    const L = S().layers[idx];
    L.visible = !L.visible;
    L.markers.forEach((m) => m.setMap(L.visible ? S().map : null));
    rebuildCluster();
    renderLayers();
  }

  function selectLayer(idx) {
    S().layers.forEach((L, i) => (L.selected = i === idx));
    window.AAA_GBM.util.setStatus(`Selected layer: ${S().layers[idx].name}`);
    renderLayers();
  }

  function renderLayers() {
    const el = document.getElementById("aaa-gbm-layer-list");
    if (!el) return;
    el.innerHTML = "";

    S().layers.forEach((L, idx) => {
      const div = document.createElement("div");
      div.className = "aaa-gbm-layer-item";
      const sel = L.selected ? " <span class='aaa-gbm-chip'>selected</span>" : "";
      div.innerHTML =
        `<strong>${L.name}</strong>${sel}` +
        `<span class="aaa-gbm-chip" style="background:${L.color};color:#fff">pins</span>` +
        `<div style="margin-top:6px">Markers: ${L.markers.length} | Weight: ${L.weight} | Mode: ${L.mode}</div>` +
        `<button class="button" data-idx="${idx}" data-act="toggle">Toggle</button> ` +
        `<button class="button" data-idx="${idx}" data-act="select">Select</button>`;
      el.appendChild(div);
    });

    el.querySelectorAll("button").forEach((b) => {
      b.addEventListener("click", () => {
        const idx = parseInt(b.dataset.idx, 10);
        const act = b.dataset.act;
        if (act === "toggle") toggleLayer(idx);
        if (act === "select") selectLayer(idx);
      });
    });
  }

  function applyPreset(key) {
    const p = PRESETS[key];
    if (!p) return;

    document.getElementById("aaa-gbm-layer-name").value = p.name;
    document.getElementById("aaa-gbm-layer-mode").value = p.mode;
    document.getElementById("aaa-gbm-layer-types").value = (p.types || []).join(",");
    document.getElementById("aaa-gbm-layer-queries").value = (p.queries || []).join("\n");
    document.getElementById("aaa-gbm-layer-color").value = p.color;
    document.getElementById("aaa-gbm-layer-weight").value = String(p.weight || 1);

    window.AAA_GBM.util.setStatus(`Preset loaded: ${p.name}`);
  }

  function addLayerFromUI() {
    const name = (document.getElementById("aaa-gbm-layer-name").value || "").trim() || "Layer";
    const mode = document.getElementById("aaa-gbm-layer-mode").value;
    const types = (document.getElementById("aaa-gbm-layer-types").value || "")
      .split(",").map((s) => s.trim()).filter(Boolean);

    const queries = parseQueries(document.getElementById("aaa-gbm-layer-queries").value);
    const color = document.getElementById("aaa-gbm-layer-color").value || "#1e73be";
    const weight = parseFloat(document.getElementById("aaa-gbm-layer-weight").value || "1");

    if (mode === "nearby" && types.length === 0) return window.AAA_GBM.util.setStatus("Types required for Nearby mode.");
    if (mode === "text" && queries.length === 0) return window.AAA_GBM.util.setStatus("At least 1 text query required.");

    const L = { name, mode, types, queries, color, weight, visible: true, selected: true, markers: [], places: [], placeIds: [] };
    S().layers.forEach((x) => (x.selected = false));
    S().layers.push(L);
    renderLayers();
    window.AAA_GBM.util.setStatus(`Added layer "${name}".`);
  }

  function resetAll() {
    S().layers.forEach((L) => L.markers.forEach((m) => m.setMap(null)));
    S().layers = [];
    if (S().clusterer) S().clusterer.setMap(null);

    S().bestMarkers.forEach((m) => m.setMap(null));
    S().bestMarkers = [];

    const best = document.getElementById("aaa-gbm-best-list");
    if (best) best.innerHTML = "";

    window.AAA_GBM.util.setStatus("Reset complete.");
    renderLayers();
  }

  document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("aaa-gbm-add-layer").addEventListener("click", addLayerFromUI);
    document.getElementById("aaa-gbm-reset").addEventListener("click", resetAll);

    document.getElementById("aaa-gbm-layer-preset").addEventListener("change", (e) => {
      applyPreset(e.target.value);
    });
  });

  window.AAA_GBM.ui = { renderLayers, rebuildCluster };
  log("ready");
})();
