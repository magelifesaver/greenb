/*
 * File: /wp-content/plugins/aaa-delivery-blocks-dispatcher/assets/admin-page.js
 * Version: 0.1.3
 * Purpose: Dispatcher map + hierarchical tree with per-status pin colors.
 */
(function($){
  let map, boundsCircle, markers = [];

  function loadGoogle(callback) {
    if (window.google && window.google.maps) { callback(); return; }
    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(ADBD.clientApiKey)}&v=weekly`;
    script.async = true; script.defer = true; script.onload = callback;
    document.head.appendChild(script);
  }

  function milesToMeters(mi){ return mi * 1609.344; }

  function initMap() {
    const center = { lat: Number(ADBD.origin.lat), lng: Number(ADBD.origin.lng) };
    map = new google.maps.Map(document.getElementById('adbd-map'), {
      center, zoom: 11, gestureHandling: 'greedy', mapTypeId: 'roadmap'
    });

    boundsCircle = new google.maps.Circle({
      strokeColor: '#6B7280', strokeOpacity: 0.8, strokeWeight: 1,
      fillColor: '#6B7280', fillOpacity: 0.15,
      map, center, radius: milesToMeters(ADBD.radiusMiles)
    });

    new google.maps.Marker({
      position: center, map, title: 'Origin',
      icon: { path: google.maps.SymbolPath.CIRCLE, scale: 6 }
    });

    fetchData();
  }

  function clearMarkers(){ markers.forEach(m => m.setMap(null)); markers = []; }

  function svgPin(color) {
    const c = color || '#3b82f6';
    const svg = `
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="${c}">
        <path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/>
      </svg>`;
    return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
  }

  function addMarker(item) {
    const status = (item.status || '').replace(/^wc-/, '');
    const colMap = ADBD.statusColors || {};
    const color  = colMap[status] || colMap[item.status] || '#3b82f6';

    const marker = new google.maps.Marker({
      position: { lat: item.lat, lng: item.lng },
      map,
      title: `#${item.number} • ${item.customer || ''}`,
      icon: { url: svgPin(color), scaledSize: new google.maps.Size(32,32) }
    });
    markers.push(marker);
  }

  function fetchData(){
    $.ajax({
      url: ADBD.rest.orders,
      method: 'GET',
      headers: { 'X-WP-Nonce': ADBD.nonce }
    })
    .done(renderData)
    .fail((xhr) => {
      console.error('ADBD REST failed', xhr?.status, xhr?.responseText);
      const treeEl = document.getElementById('adbd-tree');
      if (treeEl) {
        treeEl.innerHTML = `<div class="adbd-item">Failed to load data (${xhr?.status || ''}). Check console/logs.</div>`;
      }
    });
  }

  function secondsToHMS(s){
    if (s == null) return '—';
    const h = Math.floor(s/3600), m = Math.round((s%3600)/60);
    return h ? `${h}h ${m}m` : `${m}m`;
  }
  function metersToMiles(m){
    if (m == null) return '—';
    return (m / 1609.344).toFixed(1) + ' mi';
  }

  function renderData(data) {
    clearMarkers();
    const treeEl = document.getElementById('adbd-tree');
    treeEl.innerHTML = '';

    // Index drivers
    const driversIndex = {};
    (data.drivers || []).forEach(d => { driversIndex[String(d.id)] = d; });

    // Partition orders (NUMERIC driver id)
    const unassigned = [];
    const byDriver = {};
    (data.orders || []).forEach(o => {
      if (o.has_coords && typeof o.lat === 'number' && typeof o.lng === 'number') addMarker(o);

      const didNum = Number(o.driver_id || 0);
      if (didNum > 0) {
        const key = String(didNum);
        if (!byDriver[key]) byDriver[key] = [];
        byDriver[key].push(o);
      } else {
        unassigned.push(o);
      }
    });

    // Sort
    Object.keys(byDriver).forEach(did => {
      byDriver[did].sort((a,b)=> (a.id > b.id ? 1 : -1));
    });
    unassigned.sort((a,b)=> (a.id > b.id ? 1 : -1));

    // Helpers (collapsible groups)
    const group = (title, count) => {
      const g = document.createElement('div'); g.className = 'adbd-group';
      const h = document.createElement('h3'); h.className = 'adbd-h3';
      h.innerHTML = `<span class="adbd-caret">▸</span> ${title} <span class="adbd-badge">${count}</span>`;
      h.addEventListener('click', () => { g.classList.toggle('adbd-open'); });
      g.appendChild(h); return g;
    };

    const driverHeader = (d, estRoute, estReturn) => {
      const el = document.createElement('div'); el.className = 'adbd-item adbd-driver';
      const status = d?.availability || '—';
      const name   = d?.name || (`Driver ${d?.id || ''}`);
      el.innerHTML =
        `<div class="ad-line"><strong>${name}</strong> <span class="ad-status">${status}</span></div>
         <div class="ad-sub">${estRoute || 'Route: —'} • ${estReturn || 'Return: —'}</div>`;
      return el;
    };

    const orderRow = (o, travelLabel, isCumulative) => {
      const el = document.createElement('div'); el.className = 'adbd-item adbd-order';
      const addr1 = o.address?.line1 || '';
      const addr2 = o.address?.line2 || '';
      const cityZip = [o.address?.city, o.address?.zip].filter(Boolean).join(' ');
      const travel = travelLabel || '—';
      const pay = o.payment ? o.payment : '—';
      const pm  = o.payment_method ? ` • ${o.payment_method}` : '';
      const dtr = o.dtr || '—';
      const follow = isCumulative ? ' (cum.)' : '';

      el.innerHTML =
        `<div class="ad-line"><strong>#${o.number}</strong> • ${o.customer || 'Customer'} • ${o.status} • ${pay}${pm}</div>
         <div class="ad-sub">${addr1} ${addr2 ? ' ' + addr2 : ''}</div>
         <div class="ad-sub">${cityZip}</div>
         <div class="ad-sub">DTR: ${dtr} • Travel: ${travel}${follow}</div>`;
      el.addEventListener('click', () => { if (o.has_coords) { map.panTo({lat:o.lat,lng:o.lng}); map.setZoom(13); } });
      return el;
    };

    // UNASSIGNED
    const gUn = group('Unassigned', unassigned.length);
    const bodyUn = document.createElement('div'); bodyUn.className = 'adbd-children';
    unassigned.forEach(o => {
      const label = (o.order_distance_m != null || o.order_travel_s != null)
        ? `${metersToMiles(o.order_distance_m)} • ${secondsToHMS(o.order_travel_s)}`
        : '—';
      bodyUn.appendChild(orderRow(o, label, false));
    });
    gUn.appendChild(bodyUn); treeEl.appendChild(gUn);

    // DRIVERS: union driver list with order driver_ids so all heads render
    const driverIds = new Set([
      ...((data.drivers || []).map(d => String(d.id))),
      ...Object.keys(byDriver)
    ]);
    const gDr = group('Drivers', driverIds.size);
    const bodyDr = document.createElement('div'); bodyDr.className = 'adbd-children';

    [...driverIds].forEach(id => {
      const d = driversIndex[id] || { id, name: `Driver ${id}`, availability: '—' };
      const orders = byDriver[id] || [];

      // naive route summary for M1
      let routeSeconds = 0, routeMeters = 0;
      orders.forEach((o, idx) => {
        const s = Number(o.order_travel_s || 0), m = Number(o.order_distance_m || 0);
        if (idx === 0) { routeSeconds = s; routeMeters = m; }
        else { routeSeconds += s; routeMeters += m; }
      });
      const estRoute  = orders.length ? `Route: ${metersToMiles(routeMeters)} • ${secondsToHMS(routeSeconds)}` : 'Route: —';
      const estReturn = 'Return: —';

      const wrap = document.createElement('div'); wrap.className = 'adbd-branch';
      const head = driverHeader(d, estRoute, estReturn);
      const kids = document.createElement('div'); kids.className = 'adbd-children';

      orders.forEach((o, idx) => {
        let label;
        if (idx === 0) {
          label = (o.order_distance_m != null || o.order_travel_s != null)
            ? `${metersToMiles(o.order_distance_m)} • ${secondsToHMS(o.order_travel_s)}`
            : '—';
        } else {
          const cumMeters = orders.slice(0, idx+1).reduce((acc, x)=> acc + Number(x.order_distance_m || 0), 0);
          const cumSeconds= orders.slice(0, idx+1).reduce((acc, x)=> acc + Number(x.order_travel_s || 0), 0);
          label = `${metersToMiles(cumMeters)} • ${secondsToHMS(cumSeconds)}`;
        }
        kids.appendChild(orderRow(o, label, idx>0));
      });

      wrap.appendChild(head);
      wrap.appendChild(kids);
      bodyDr.appendChild(wrap);
    });
    gDr.appendChild(bodyDr); treeEl.appendChild(gDr);

    // Open both groups by default
    gUn.classList.add('adbd-open');
    gDr.classList.add('adbd-open');

    // Search filter (hides orders; drivers remain)
    $('#adbd-search').off('input').on('input', function(){
      const q = this.value.toLowerCase();
      $('.adbd-order').each(function(){
        const txt = this.textContent.toLowerCase();
        this.style.display = txt.includes(q) ? '' : 'none';
      });
    });
  }

  // Kick off
  jQuery(function(){ loadGoogle(initMap); });
})(jQuery);
