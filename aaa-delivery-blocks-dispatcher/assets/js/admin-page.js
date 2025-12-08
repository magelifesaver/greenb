/*
 * File: /wp-content/plugins/aaa-delivery-blocks-dispatcher/assets/admin-page.js
 * Version: 0.1.0
 * Purpose: Client logic for the dispatcher page. Loads Google Maps JS API, draws origin + radius
 *          circle, fetches orders/drivers via REST, renders markers and right-side Unassigned/Drivers tree.
 */
(function($){
	let map, boundsCircle, markers = [];
	let treeEl;

	function loadGoogle(callback) {
		if (window.google && window.google.maps) {
			callback(); return;
		}
		const script = document.createElement('script');
		script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(ADBD.clientApiKey)}&v=weekly`;
		script.async = true;
		script.defer = true;
		script.onload = callback;
		document.head.appendChild(script);
	}

	function milesToMeters(mi){ return mi * 1609.344; }

	function initMap() {
		const center = { lat: Number(ADBD.origin.lat), lng: Number(ADBD.origin.lng) };
		map = new google.maps.Map(document.getElementById('adbd-map'), {
			center,
			zoom: 11,
			gestureHandling: 'greedy',
			mapId: 'adbd-dark'
		});

		boundsCircle = new google.maps.Circle({
			strokeColor: '#6B7280', strokeOpacity: 0.8, strokeWeight: 1,
			fillColor: '#6B7280', fillOpacity: 0.15,
			map, center,
			radius: milesToMeters(ADBD.radiusMiles)
		});

		new google.maps.Marker({
			position: center, map, title: 'Origin', icon: { path: google.maps.SymbolPath.CIRCLE, scale: 6 }
		});

		fetchData();
	}

	function clearMarkers(){
		markers.forEach(m => m.setMap(null));
		markers = [];
	}

	function addMarker(item, color) {
		const marker = new google.maps.Marker({
			position: { lat: item.lat, lng: item.lng },
			map,
			title: `#${item.number} • ${item.customer || ''}`,
		});
		markers.push(marker);
	}

	function fetchData(){
		$.ajax({
			url: ADBD.rest.orders,
			method: 'GET',
			headers: { 'X-WP-Nonce': ADBD.nonce }
		}).done(renderData);
	}

function renderData(data) {
	clearMarkers();
	const treeEl = document.getElementById('adbd-tree');
	treeEl.innerHTML = '';

	// Index drivers by id; show all of them in the list
	const driversIndex = {};
	(data.drivers || []).forEach(d => { driversIndex[d.id] = d; });

	// Partition orders into unassigned vs assigned
	const unassigned = [];
	const assignedByDriver = {};

	(data.orders || []).forEach(o => {
		// Add marker only if coords exist
		if (o.has_coords && typeof o.lat === 'number' && typeof o.lng === 'number') {
			addMarker(o);
		}

		if (!o.driver_id) {
			unassigned.push(o);
		} else {
			if (!assignedByDriver[o.driver_id]) assignedByDriver[o.driver_id] = [];
			assignedByDriver[o.driver_id].push(o);
		}
	});

	// Helpers
	const groupNode = (title, count) => {
		const group = document.createElement('div'); group.className = 'adbd-group';
		const h = document.createElement('h3'); h.innerHTML = `${title} <span class="adbd-badge">${count}</span>`;
		group.appendChild(h); return group;
	};
	const orderNode = (o) => {
		const el = document.createElement('div');
		el.className = 'adbd-item'; el.setAttribute('data-type','order');
		const distance = (o.distance_mi != null) ? `${o.distance_mi} mi` : '—';
		const coordBadge = o.has_coords ? '' : ' <span class="adbd-badge">no coords</span>';
		el.setAttribute('data-search', `#${o.number} ${o.customer || ''} ${o.address || ''}`.toLowerCase());
		el.innerHTML = `<strong>#${o.number}</strong> • ${o.customer || 'Customer'}${coordBadge}<br/><small>${o.address || ''}</small><br/><small>${distance}</small>`;
		el.addEventListener('click', () => {
			if (o.has_coords) { map.panTo({lat:o.lat,lng:o.lng}); map.setZoom(13); }
		});
		return el;
	};

	// Unassigned group
	const unassignedGroup = groupNode('Unassigned', unassigned.length);
	unassigned.forEach(o => unassignedGroup.appendChild(orderNode(o)));
	treeEl.appendChild(unassignedGroup);

	// Drivers group — list ALL drivers, with counts (including zero)
	const driversGroup = groupNode('Drivers', (data.drivers || []).length);
	(data.drivers || []).forEach(d => {
		const ordersForDriver = assignedByDriver[d.id] || [];
		const wrap = document.createElement('div');
		const title = document.createElement('div');
		title.className = 'adbd-item';
		title.innerHTML = `<strong>${d.name || ('Driver ' + d.id)}</strong> <span class="adbd-badge">${ordersForDriver.length}</span>`;
		wrap.appendChild(title);
		ordersForDriver.forEach(o => wrap.appendChild(orderNode(o)));
		driversGroup.appendChild(wrap);
	});
	treeEl.appendChild(driversGroup);

	// Search filter
	$('#adbd-search').off('input').on('input', function(){
		const q = this.value.toLowerCase();
		$('.adbd-item[data-type="order"]').each(function(){
			const txt = this.getAttribute('data-search') || '';
			this.style.display = txt.includes(q) ? '' : 'none';
		});
	});
}

	function groupNode(title, count) {
		const group = document.createElement('div');
		group.className = 'adbd-group';
		const h = document.createElement('h3');
		h.innerHTML = `${title} <span class="adbd-badge">${count}</span>`;
		group.appendChild(h);
		return group;
	}

	function orderNode(o) {
		const el = document.createElement('div');
		el.className = 'adbd-item';
		el.setAttribute('data-type','order');
		el.setAttribute('data-search', `#${o.number} ${o.customer || ''} ${o.address || ''}`.toLowerCase());
		el.innerHTML = `<strong>#${o.number}</strong> • ${o.customer || 'Customer'}<br/><small>${o.address || ''}</small><br/><small>${o.distance_mi} mi</small>`;
		el.addEventListener('click', () => {
			map.panTo({lat:o.lat,lng:o.lng});
			map.setZoom(13);
		});
		return el;
	}

	// Kick off
	jQuery(function(){
		loadGoogle(initMap);
	});
})(jQuery);
