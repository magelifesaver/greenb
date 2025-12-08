var geocode;
var map;
var geo_coordinates;
var polygon;
var autocomplete;

async function init() {


	const { Map } = await google.maps.importLibrary("maps");
	const { Geo } = await google.maps.importLibrary("geometry");
	const { Geocode } = await google.maps.importLibrary("geocoding");
	const { Searchbox } = await google.maps.importLibrary("places");

	initialize(init_map, add_zoom_listener);
	geocode = new google.maps.Geocoder();
	codeAddress();

	


}

function codeAddress() {
	var input = document.getElementById('szbdzones_address');
	var searchBox = new google.maps.places.SearchBox(input);
	map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
	map.addListener('bounds_changed', function () {
		searchBox.setBounds(map.getBounds());
	});
	var markers = [];
	searchBox.addListener('places_changed', function () {
		var places = searchBox.getPlaces();
		if (places.length == 0) {
			return;
		}
		markers.forEach(function (marker) {
			marker.setMap(null);
		});
		markers = [];
		var bounds = new google.maps.LatLngBounds();
		places.forEach(function (place) {
			if (!place.geometry) {
				return;
			}
			var icon = {
				url: place.icon,
				size: new google.maps.Size(71, 71),
				origin: new google.maps.Point(0, 0),
				anchor: new google.maps.Point(17, 34),
				scaledSize: new google.maps.Size(25, 25)
			};
			markers.push(new google.maps.Marker({
				map: map,
				icon: icon,
				title: place.name,
				position: place.geometry.location
			}));
			if (place.geometry.viewport) {
				bounds.union(place.geometry.viewport);
			} else {
				bounds.extend(place.geometry.location);
			}
		});
		map.fitBounds(bounds);
	});

	if ((document.getElementById('szbdzones_address') != null)) {
		var shipaddr = document.getElementById('szbdzones_address');
		shipaddr.addEventListener('keydown', function (e) {

			if (e.key == 'Enter') {
				e.preventDefault();
			}
		});
	}
}

function init_map() {
	var mapOptions = {
		center: new google.maps.LatLng(szbd_map.lat, szbd_map.lng),
		zoom: Number(szbd_map.zoom),
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
	map = new google.maps.Map(document.getElementById("szbdzones_id"), mapOptions);
}

function add_zoom_listener() {
	google.maps.event.addListener(map, 'zoom_changed', function (e) {
		jQuery("input#szbdzones_zoom").val(map.getZoom());
	});
}

function initialize(callback_1, callback_2) {
	callback_1();
	callback_2();
	var center = map.getCenter();
	var cLat = center.lat();
	var cLng = center.lng();
	var path = [];
	for (i = 0; i < (szbd_map.array_latlng).length; i++) {
		path.push(new google.maps.LatLng(szbd_map.array_latlng[i][0], szbd_map.array_latlng[i][1]));

	}
	geo_coordinates = path;
	jQuery("input#szbdzones_geo_coordinates").val(geo_coordinates.toString());
	jQuery("input#szbdzones_lat").val(center.lat());
	jQuery("input#szbdzones_lng").val(center.lng());
	jQuery("input#szbdzones_zoom").val(map.getZoom());
	draw_boundary();
	google.maps.event.addListener(map, 'click', function (e) {
		addCoord(e.latLng);
		jQuery("input#szbdzones_geo_coordinates").val(geo_coordinates.toString());
	});
	google.maps.event.addListener(map, 'center_changed', function (e) {
		center = map.getCenter();
		jQuery("input#szbdzones_lat").val(center.lat());
		jQuery("input#szbdzones_lng").val(center.lng());
	});
}

function reset_geo_coordinates(cLat, cLng) {
	polygon.setMap(null);
	geo_coordinates = null;
}

function draw_boundary() {
	var center = map.getCenter();
	var cLat = center.lat();
	var cLng = center.lng();

	polygon = new google.maps.Polygon({
		paths: geo_coordinates,
		strokeColor: '#0c6e9e',
		strokeOpacity: 0.8,
		strokeWeight: 3,
		fillColor: '#97e1ff',
		fillOpacity: 0.55
	});
	polygon.setMap(map);
	addMarkers();
}
var currentMarker;
var markers = [];

function removeMarkers() {
	for (x = 0; x < markers.length; x++) {
		markers[x].setMap(null);
	}
}

function addMarkers() {
	for (var i = 0; i < geo_coordinates.length; i++) {
		addMarker(geo_coordinates[i]);
	}
}

function addMarker(myLatLng) {
	var marker = new google.maps.Marker({
		position: myLatLng,
		map: map,
		draggable: true
	});
	markers.push(marker);
	google.maps.event.addListener(marker, 'dragstart', function () {
		currentMarker = marker.getPosition();
	});
	google.maps.event.addListener(marker, 'dragend', function () {
		for (x = 0; x < geo_coordinates.length; x++) {
			if (geo_coordinates[x] == currentMarker) {
				geo_coordinates[x] = marker.getPosition();
			}
		}
		polygon.setPaths(geo_coordinates);
		jQuery("input#szbdzones_geo_coordinates").val(geo_coordinates.toString());
	});
	google.maps.event.addListener(marker, 'click', function () {
		currentMarker = marker.getPosition();
		for (x = 0; x < geo_coordinates.length; x++) {
			if (geo_coordinates[x] == currentMarker) {
				geo_coordinates.splice(x, 1);
			}
		}
		marker.setMap(null);
		polygon.setPaths(geo_coordinates);
		jQuery("input#szbdzones_geo_coordinates").val(geo_coordinates.toString());

		//Remove center point if no markers on map
		if (polygon.getPath() != undefined) {

			jQuery("input#szbdzones_lat").val('');
			jQuery("input#szbdzones_lng").val('');


		}
	});
}

function addCoord(point) {
	var d1, d2, d, dx, dy;
	var insertAt1, insertAt2;
	for (var i = 0; i < geo_coordinates.length; i++) {
		dx = geo_coordinates[i].lng() - point.lng();
		dy = geo_coordinates[i].lat() - point.lat();
		d = (dx * dx) + (dy * dy);
		d = Math.sqrt(d);
		if (i > 0) {
			if (d < d1) {
				d2 = d1;
				d1 = d;
				insertAt2 = insertAt1;
				insertAt1 = i;
			} else if (d < d2) {
				d2 = d;
				insertAt2 = i;
			}
		} else {
			d1 = d;
		}
	}
	if (insertAt2 < insertAt1)
		insertAt1 = insertAt2;
	geo_coordinates.splice(insertAt1 + 1, 0, point);
	polygon.setPaths(geo_coordinates);
	removeMarkers();
	addMarkers();
}
var hits = [];
var lastBetween;
var sHtml = "";

function isInside(position) {
	sHtml = "";
	var points = geo_coordinates;
	var Lx = position.lng();
	var Ly = position.lat();
	for (var i = 0; i < points.length; i++) {
		var p1 = i;
		var p2 = i + 1;
		if (p2 == points.length)
			p2 = 0;
		bIntersected(points[p1].lng(), points[p1].lat(), points[p2].lng(), points[p2].lat(), Lx, Ly, Lx + 1, Ly + 0.001);
	}
	var iLeft = 0;
	var iRight = 0;
	for (i = 0; i < hits.length; i++) {
		if (hits[i] <= Lx)
			iLeft++;
		else
			iRight++;
	}
	for (i = 0; i <= hits.length + 5; i++) {
		hits.pop();
	}
	sHtml += ("iLeft = " + iLeft + ", iRight = " + iRight + "<br>");
	sHtml += ("mod iLeft = " + iLeft % 2 + ", mod iRight = " + iRight % 2 + "<br>");
	if (iLeft % 2 == 1 && iRight % 2 == 1)
		return true;
	else
		return false;
}

function bIntersected(x1a, y1a, x2a, y2a, x1b, y1b, x2b, y2b) {
	///////////////////  LINE 1  //////////////////////////
	var ma = (y1a - y2a) / (x1a - x2a);
	//y = mx + b (formula for a line)
	//solve for b
	var ba = y1a - (ma * x1a);
	///////////////////  LINE 2  //////////////////////////
	var mb = (y1b - y2b) / (x1b - x2b);
	//y = mx + b
	//solve for b
	var bb = y1b - (mb * x1b);
	///////////////////  Solve for intersection of X  //////////////////////////
	//use the first point to resolve X
	var xi = (bb - ba) / (ma - mb);
	//solve for yi based on one of the line functions and xi
	var yi = (ma * xi) + ba;
	/////////////////// Is the intersection between the end points? ///////////////
	var iBetween = (x1a - xi) * (xi - x2a);
	if (iBetween >= 0 && lastBetween != 0) {
		hits.push(xi);
	}
	lastBetween = iBetween;
}

function processPoints(geometry, callback, thisArg) {
	if (geometry instanceof google.maps.LatLng) {
		callback.call(thisArg, geometry);
	} else if (geometry instanceof google.maps.Data.Point) {
		callback.call(thisArg, geometry.get());
	} else {
		geometry.getArray().forEach(function (g) {
			processPoints(g, callback, thisArg);
		});
	}
}


function szbdfileChanged(e) {

	const element = document.getElementById("szbd_import_fail_message");
	if(element !== null){
		element.remove(); 
	}
	
	let file = e.target.files[0];
	parseDocument(file);
}

function parseDocument(file) {
	try {
		let fileReader = new FileReader();
		fileReader.onload = async (e) => {
			try{
			let result = await extractGoogleCoords(e.target.result);

			
			if (result == undefined || !Array.isArray(result.polygons[0]) || !result.polygons[0].length) {

				throw "Error: Polygon data could not be extracted";


			}
			if (polygon.getPath() != undefined) {

				throw "Error: Polygon already exists";


			}

			var path = [];
			var bounds = new google.maps.LatLngBounds();
			for (i = 0; i < (result.polygons[0]).length; i++) {

				//console.debug(result.polygons[0][i].lat);
				//console.debug(result.polygons[0][i].lng);

				if (test_latlng(result.polygons[0][i].lat, result.polygons[0][i].lng )) {
					let point = new google.maps.LatLng(result.polygons[0][i].lat, result.polygons[0][i].lng);
					path.push(point);
					processPoints(point, bounds.extend, bounds);
				}


			}




			map.fitBounds(bounds);

			let center = map.getCenter()

			geo_coordinates = path;
			jQuery("input#szbdzones_geo_coordinates").val(geo_coordinates.toString());
			jQuery("input#szbdzones_lat").val(center.lat());
			jQuery("input#szbdzones_lng").val(center.lng());
			jQuery("input#szbdzones_zoom").val(map.getZoom());
			draw_boundary();
		}catch(e){
			add_fail_message(e);
		}
		};
		fileReader.readAsText(file);
	} catch (e) {
		add_fail_message(e);
	}
}

function add_fail_message(e){
	console.debug(e);
	const failNode = document.getElementById("szbd_import_fail_message");
if(failNode === null){
	let div = document.createElement("div");
	div.classList.add("inside");
	div.setAttribute("id", "szbd_import_fail_message");
	let para = document.createElement("p");
	let node = document.createTextNode(e);
	para.appendChild(node);
	div.appendChild(para);

	let element = document.getElementById("szbdzones_mapmeta2");
	element.appendChild(div);
}
	
}
async function extractGoogleCoords(plainText) {
	try {
		let parser = new DOMParser();
		let xmlDoc = parser.parseFromString(plainText, "text/xml");
		let googlePolygons = [];
		let googleMarkers = [];

		if (xmlDoc.documentElement.nodeName === "kml") {
			for (const item of xmlDoc.getElementsByTagName('Placemark')) {
				let placeMarkName = item.getElementsByTagName('name')[0].childNodes[0].nodeValue.trim();
				let polygons = item.getElementsByTagName('Polygon');
				let markers = item.getElementsByTagName('Point');

				/** POLYGONS **/
				for (const polygon of polygons) {
					let coords = polygon.getElementsByTagName('coordinates')[0].childNodes[0].nodeValue.trim();
					let points = coords.split(" ");

					let googlePolygonsPaths = [];
					for (const point of points) {
						let coord = point.split(",");
						googlePolygonsPaths.push({ lat: +coord[1], lng: +coord[0] });
					}
					googlePolygons.push(googlePolygonsPaths);
				}

				/** MARKERS **/
				for (const marker of markers) {
					var coords = marker.getElementsByTagName('coordinates')[0].childNodes[0].nodeValue.trim();
					let coord = coords.split(",");
					googleMarkers.push({ lat: +coord[1], lng: +coord[0] });
				}
			}
		} else {
			throw "Error: Not a klm-file";
		}

		return { markers: googleMarkers, polygons: googlePolygons };
	} catch (e) {
		add_fail_message(e);
	}
}
function test_latlng(lat, lng) {
	var reg_lat = /^-?([1-8]?\d(?:\.\d{1,})?|90(?:\.0{1,6})?)$/;
	var reg_lng = /^-?((?:1[0-7]|[1-9])?\d(?:\.\d{1,})?|180(?:\.0{1,})?)$/;
	if (reg_lat.test(lat) && reg_lng.test(lng)) {
		return true;
	}
	return false;
}
jQuery(document).ready(function ($) {

	init();

});
