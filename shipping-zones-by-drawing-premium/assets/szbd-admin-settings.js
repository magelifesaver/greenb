jQuery(document).ready(function($) {
	if ($('#szbd_store_address_mode').find('option:selected').attr("value") == 'pick_store_address') {
		init_map();
	} else {
		jQuery('#szbd-pick-content').hide();
		jQuery('#szbd_settings_test-description').hide();
		jQuery('#szbd_settings_test-description').prev('h2').hide();
	}
	
	// Server mode messages
	if (!$('#szbd_server_mode:checkbox').prop('checked')) {
		$('#szbd_servermode_message').parents('tr').hide();
		
		
		
		
	}
	$('#szbd_server_mode:checkbox').on('change',function() {
		if (this.checked) {
			$('#szbd_servermode_message').parents('tr').slideDown('slow');
			
			
		} else {
			$('#szbd_servermode_message').parents('tr').slideUp();
			
			
		}
	});
	
	
	
	
	
	 if ($('#szbd_precise_address').find('option:selected').attr("value") != 'always') {
		
		$('#szbd_auto_marker_saved').parents('tr').hide();
		
	}
	$('#szbd_precise_address').on('change', function() {
		if ($(this).find('option:selected').attr("value") != 'always') {
			$('#szbd_auto_marker_saved').parents('tr').slideUp('slow');
			
		} else {
			$('#szbd_auto_marker_saved').parents('tr').slideDown('slow');
			
		}
	});
	
	if ($('#szbd_precise_address').find('option:selected').attr("value") == 'no') {
		$('#szbd_precise_address_mandatory').parents('tr').hide();
		$('#szbd_precise_address_plus_code').parents('tr').hide();
		$('#szbd_auto_marker').parents('tr').hide();
		$('#szbd_map_type').parents('tr').hide();
		
	}
	
	
	
	$('#szbd_precise_address').on('change', function() {
		if ($(this).find('option:selected').attr("value") == 'no') {
			$('#szbd_precise_address_mandatory').parents('tr').slideUp('slow');
			$('#szbd_precise_address_plus_code').parents('tr').slideUp('slow');
			$('#szbd_auto_marker').parents('tr').slideUp('slow');
			$('#szbd_map_type').parents('tr').slideUp('slow');
		} else {
			$('#szbd_precise_address_mandatory').parents('tr').slideDown('slow');
			$('#szbd_precise_address_plus_code').parents('tr').slideDown('slow');
			$('#szbd_auto_marker').parents('tr').slideDown('slow');
			$('#szbd_map_type').parents('tr').slideDown('slow');
		}
	});
	$('#szbd_store_address_mode').on('change', function() {
		if ($(this).find('option:selected').attr("value") == 'pick_store_address') {
			jQuery('#szbd_settings_test-description').slideDown();
			jQuery('#szbd_settings_test-description').prev('h2').slideDown();
			jQuery('#szbd-pick-content').slideDown(function() {
				if (jQuery('#szbd_map').children().length) {
				} else {
					init_map();
				}
			});
		} else {
			jQuery('#szbd-pick-content').slideUp();
			jQuery('#szbd_settings_test-description').slideUp();
			jQuery('#szbd_settings_test-description').prev('h2').slideUp();
		}
	});
	
	if (!$('#szbd_types_custom:checkbox').prop('checked')) {
		$('#szbd_result_types').parents('tr').hide();
		$('#szbd_no_map_types').parents('tr').hide();
		
		
		
	}
	$('#szbd_types_custom:checkbox').on('change',function() {
		if (this.checked) {
			$('#szbd_result_types').parents('tr').slideDown('slow');
			$('#szbd_no_map_types').parents('tr').slideDown('slow');
			
		} else {
			$('#szbd_result_types').parents('tr').slideUp();
			$('#szbd_no_map_types').parents('tr').slideUp();
			
		}
	});
	var map;
	var marker;

	async function init_map() {

	const { Map } = await google.maps.importLibrary("maps");
	const { Geo } = await google.maps.importLibrary("geometry");
	const { Geocode } = await google.maps.importLibrary("geocoding");
	const { Searchbox } = await google.maps.importLibrary("places");
	const { AdvancedMarkerElement, PinElement } = await google.maps.importLibrary("marker");
	
		test_store_address();
		show_store_map();
		szbd_save_main_options_ajax();
	}
	// Helping methods

	function findCommonElements(arr1, arr2) {
	return arr1.some(function(item){return arr2.includes(item);});
}

	function show_store_map() {
		initMap(szbd_settings.store_location);
		grabAddress();
	}

	function grabAddress() {
		var input = document.getElementById('szbdzones_address');
		var searchBox = new google.maps.places.SearchBox(input);
		map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
		map.addListener('bounds_changed', function() {
			searchBox.setBounds(map.getBounds());
		});
		
		searchBox.addListener('places_changed', function() {
			var places = searchBox.getPlaces();
			if (places.length == 0) {
				return;
			}
			
			var bounds = new google.maps.LatLngBounds();
			places.forEach(function(place) {
				if (!place.geometry) {
					return;
				}
				
				
				placeMarker(place.geometry.location);
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

	function initMap(uluru) {
		var mapOptions;
		var markerOptions;
		if (typeof uluru == 'undefined' || uluru == null) {
			mapOptions = {
				zoom: 1,
				center: {
					lat: 0,
					lng: 0
				},
				mapId: 'SZBD_ADMIN_MAP',
				disableDefaultUI: false,
			};
			map = new google.maps.Map(
				document.getElementById('szbd_map'), mapOptions);
		} else {
			//console.debug(uluru);
			mapOptions = {
				zoom: 18,
				center: uluru,
				mapId: 'SZBD_ADMIN_MAP',
				disableDefaultUI: false,
			};
			map = new google.maps.Map(
				document.getElementById('szbd_map'), mapOptions);
			markerOptions = {
				position: uluru,
				map: map,
				gmpDraggable: true,
			};
			marker = new google.maps.marker.AdvancedMarkerElement(markerOptions);
		}
		jQuery('#szbd_map').height(400);
		google.maps.event.addListener(map, 'click', function(event) {
			placeMarker(event.latLng);
		});
	}

	function placeMarker(location) {
		if (marker) {
			marker.position = location;
		} else {
			marker = new google.maps.marker.AdvancedMarkerElement({
				position: location,
				map: map,
				gmpDraggable: true,
			});
		}
	}

	function test_store_address() {
		$('#szbd-test-address').off('click').on('click', function(event, ui) {
			jQuery('.szbd-admin-map').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
			var data = {
				'action': 'test_store_address',
			};
			$.post(
				szbd_settings.ajax_url,
				data,
				function(response) {
					var store_address = response.store_address;
					var geocode_storeaddress = new google.maps.Geocoder();

					geocode_storeaddress.geocode({
							'address': store_address.store_address + ',' + store_address.store_postcode + ',' + store_address.store_city + ',' + store_address.store_state + ',' + store_address.store_country,

						},
						function(results, status) {
							var ok_types = ["street_address", "subpremise", "premise", "establishment", "route"];

							if (typeof results[0].address_components !== 'undefined') {
								for (var i = 0; i < results[0].address_components.length; i++) {
									var addressType = results[0].address_components[i].types[0];
									if (addressType == 'country' && results[0].address_components[i]['short_name'] == 'IE') {
										ok_types.push('postal_code');
									}
								}
							}


							if (status === 'OK' && findCommonElements(results[0].types, ok_types)) {
								initMap(results[0].geometry.location);
								//szbd_save_main_options_ajax(results[0].geometry.location);
								jQuery('#szbd-test-result').html('<div class=""> <br><span class="szbd-heading">STORE ADDRESS OK!</span> <br>' + (results[0].formatted_address) + '</div>');
							} else {
								jQuery('#szbd-test-result').html('<div class=""> <br><span class="szbd-heading-fail">STORE ADDRESS NOT OK!</span> <br>' + JSON.stringify(results) + '</div>');
							}
						});
				}).always(function() {
				jQuery('.szbd-admin-map').unblock();
			});
		});
	}

	function szbd_save_main_options_ajax() {
		if ($('#szbd_store_location').length) {
			$('.woocommerce-save-button[type=submit]').off("click").on("click", function() {
				var store_location = marker.position;
				if (store_location) {
					document.getElementById('szbd_store_location').value = JSON.stringify(store_location);
				} else {
					return;
				}
			});
		} else {
			return;
		}
	}
});
