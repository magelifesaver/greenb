jQuery(function ($) {
	
	if (typeof szbd === 'undefined') {
		return false;
	}

	   function init(){
		
		
		
		
		
	var szbd_checkout = {
		from_saved_point:false,
		has_address:true,
		the_response: [],
		run_geo: false,
		geo_base: [],
		ok_types:
			szbd.is_custom_types == 1
				? _.toArray(szbd.result_types)
				: [
					'street_address',
					'subpremise',
					'premise',
					'establishment',
					'plus_code',
				],
		
		init: function(loc,has_address) {
			jQuery(document).off('szbd_map_loaded_blocks').on('szbd_map_loaded_blocks', function (e, loc,has_address) {
				
				szbd_checkout.init(loc,has_address);
				
				
			});
			
			
			
			if (szbd.is_checkout == 1) {
				//Unset map and marker
				szbd_map.map = null;
				if (szbd_map.marker) {
					szbd_map.marker.setMap(null);
					szbd_map.marker = null;
				}
				var set_marker = false;
				if (szbd.precise_address === 'always') {
					var location ;
					var saved_location = szbd.customer_stored_location;
					if(szbd_isJsonString(saved_location)){
						location = JSON.parse(saved_location);
						szbd_checkout.from_saved_point = true;
						set_marker = 'auto_marker';
					}else{
						location = loc;
					}

					if (
						szbd.auto_marker == 1 &&
						_.isObject(location) &&
						_.has(location, 'lat')
					) {
						set_marker = 'auto_marker';
					}
					//alert('set map');
					szbd_checkout.get_map_from_server_data(
						location,
						set_marker
					);

				} else if (szbd.precise_address === 'at_fail') {
					

					
					
						szbd_checkout.has_address = has_address;
						szbd_checkout.get_map_from_server_data(loc, set_marker);
					
					

				}
				szbd_map.init_delivery_map();

				// Listen to event to update map with address data from server
				jQuery(document)
					.off('szbd_map_update_blocks')
					.on('szbd_map_update_blocks', function (e, latlng,has_address,loc) {


						
						


						if (szbd_checkout.from_saved_point && loc == false) {
							
							
								return;
							
		
						}

						szbd_checkout.from_saved_point = false;
						szbd_checkout.has_address = has_address;

						
						szbd_checkout.get_map_from_server_data(latlng, false);

					});
			}
		},
		get_map_from_server_data: function (latlng, from_marker) {
			
			let loc = !_.isNull(latlng)
				? ( szbd_checkout.from_saved_point ? latlng : latlng.extensions['szbd-shipping-map'].shipping_point )
				: null;
				
			
			var types = [];
			var is_fail =  loc == false ? true : false;
			if (
				!_.isNull(latlng) &&
				(!_.isObject(loc) || !_.has(loc, 'lat') || is_fail)
			) {
				loc = latlng.shippingAddress;
				loc['formatted_address'] =
					latlng.extensions[
						'szbd-shipping-map'
					].formatted_address;
				types = ['street_address'];
			}

			let is_precise_address = true;

			szbd_map.initMap(
				loc,
				is_fail,
				is_precise_address,
				types,
				from_marker
			);
		},

		
		
		
		is_address_empty: function (control_address_string) {
			try {
				control_address_string = control_address_string.replace(
					/\s+/g,
					''
				);
				if (
					_.isEmpty(control_address_string) ||
					!control_address_string.trim()
				) {
					return true;
				} else {
					return false;
				}
			} catch (err) {
				console.debug(err);
			}
		},
		
	

		try_geocode: function (
			from_marker,
			ok_types,
			geo_base,
			s_address,
			comp,
			has_address,
			isPlusCode
		) {
			try {
				$.when(szbd_google_geocode.geocode(s_address, comp)).then(
					function (response) {
						var results = response.results;
						var status = response.status;
						szbd_checkout.run_geo = false;
						if (szbd.debug == 1) {
							
							 const date = new Date();

								
							let time = date.toLocaleTimeString()+'\n';
							let debug = 'PLUSCODE REQUEST: Pluscode string: \n',
							debug2 = 	s_address +'\n',
							debug3 = 	' Component restriction: \n',
							debug4 = 	JSON.stringify(comp) +'\n',
							debug5 =	'TYPES:' +'\n',
							debug6= 	ok_types +'\n',
							debug7 = 	'STATUS:' +'\n',
							debug8 =	status +'\n',
							debug9 =	'GEOCODE: \n',
							debug10 =	JSON.stringify(results) ,
							debug11 = 'Time:';
								
								console.debug(debug,debug2,debug11,time,debug3,debug4,debug5,debug6,debug7,debug8,debug9,debug10);
						}
						if (
							status ===
							'OK' /*&& szbd_checkout.findCommonElements(results[0].types, ok_types)*/
						) {
							if (isPlusCode) {
								szbd_map.updatingplusCode = false;
								szbd_map.placeMarker(
									results[0].geometry.location,
									true,
									false
								);
								let zoom = szbd_map.get_zoom_level(
									results[0].types
								);
								szbd_map.map.setZoom(zoom);
							}else{
								szbd_map.insert_and_trigger('szbd-plus-code', 'updateserver');
							}
						} else {
							//szbd_map.remove_marker();
							szbd_map.insert_and_trigger('szbd-plus-code', 'updateserver');
						}
					}
				);
			} catch (err) {
				console.debug(err);
			}
		},
		test_latlng(lat, lng) {
			try {
				var reg_lat = /^-?([1-8]?\d(?:\.\d{1,})?|90(?:\.0{1,6})?)$/;
				var reg_lng =
					/^-?((?:1[0-7]|[1-9])?\d(?:\.\d{1,})?|180(?:\.0{1,})?)$/;
				if (reg_lat.test(lat) && reg_lng.test(lng)) {
					return true;
				}
				return false;
			} catch (err) {
				console.debug(err);
			}
		},
		findCommonElements: function (arr1, arr2) {
			return arr1.some(function (item) {
				return arr2.includes(item);
			});
		},
		ignore_szbd: function (auth_error) {
			console.debug(auth_error);
			jQuery('#szbd_checkout_field').remove();
		},
	};
	
	var szbd_map = {
		iw:null,
		map: null,
		marker: null,
		mapOptions: {
			zoom: 1,
			center: {
				lat: 0,
				lng: 0,
			},
			disableDefaultUI: true,
			zoomControl: true,
			mapTypeId: szbd.maptype,
			mapId: szbd.mapid,
		},
		updatingplusCode: false,
		init_delivery_map: function () {
			try {
				
				
				szbd_map.remove_marker_on_change();

				// Plus Code Init
				jQuery('body')
					.off('change.szbdplus')
					.on(
						'change.szbdplus',
						'#szbd-plus-code',
						szbd_map.geocode_plus_code
					);
				jQuery('body').on(
					'keypress keydown keyup',
					'#szbd-plus-code',
					function (e) {
						if (e.key == 'Enter') {
							// We now update location when pressing Enter on pluscode form
							//e.preventDefault();
						}
					}
				);
				// Compatibility with [CheckMyAddress] - have to be revised for full compatibility
				jQuery('#szbd_map').on(
					'cma_placing_marker',
					function (event, location) {
						
						if (szbd.auto_marker == 0) {
							return;
						}
						szbd_map.remove_marker();
						szbd_map.update_store_map(
							location,
							false,
							true,
							null,
							false
						);
					}
				);
			// Listen to event when new address is set fom CMA - send dummy point to trigger blocks checkout refresh
				jQuery(window).on(
					'cma_customer_address_is_set',
					function (event, location) {
						
						szbd_map.insert_and_trigger('szbd-plus-code', 'empty');
						//szbd_map.insert_and_trigger('szbd-picked', '{"lat":null}');
                        szbd_map.insert_and_trigger('shipping-szbd-shipping_point', null);
						
					}
				);



			
			} catch (err) {
				console.debug(err);
			}
		},

		initMap: function (
			uluru,
			is_fail,
			is_precise_address,
			types,
			from_marker
		) {
			try {
				
				if (from_marker == true ||  !szbd_checkout.has_address) {
					
					return;
				}
				if (szbd.precise_address === 'at_fail') {
					if (is_fail ) {
						
							jQuery('#szbd_checkout_field').fadeIn();
						
						
					} else {
						if (!from_marker) {
							if (szbd_map.marker) {
								szbd_map.marker.setMap(null);
								szbd_map.marker = null;
							}
							jQuery('#szbd_checkout_field').slideUp();
							szbd_map.insert_and_trigger('szbd-plus-code', 'empty');
							//szbd_map.insert_and_trigger('szbd-picked', 'empty');
							szbd_map.insert_and_trigger('shipping-szbd-shipping_point', '');
							
							return;
						}
					}
				}
				if (
					(szbd_map.updatingplusCode || !from_marker) &&
					(typeof uluru == 'undefined' ||
						uluru == null ||
						_.isArray(uluru) ||
						_.has(uluru, 'country'))
				) {
					if (_.isArray(uluru) || _.has(uluru, 'country')) {
						this.geocodeByArea(
							uluru,
							szbd_map.mapOptions,
							is_precise_address,
							from_marker
						);
					} else {
						this.set_map(
							szbd_map.mapOptions,
							is_precise_address,
							null,
							from_marker
						);
					}
				} else {
					let mapOptions = szbd_map.mapOptions;
					mapOptions.zoom = this.get_zoom_level(types);
					mapOptions.center = uluru;
					if (
						szbd.auto_marker == 1 &&
						szbd.precise_address === 'always' &&
						!from_marker
					) {
						if (_.isObject(uluru) && _.has(uluru, 'lat')) {
							from_marker = 'auto_marker';
						}
					}
					
					this.set_map(
						mapOptions,
						is_precise_address,
						uluru,
						szbd_map.updatingplusCode ? true : from_marker
					);
					
				}
			} catch (err) { 
				console.debug(err);
			}
		},
		geocodeByArea: function (
			uluru,
			mapOptions,
			is_precise_address,
			from_marker
		) {
			try {
				var address = uluru.formatted_address;
				address = address.replace(/,+/g, ',');
				var comp = {
					country: uluru.country,
					administrativeArea: uluru.city,
				};
				if (comp.administrativeArea === '') {
					delete comp.administrativeArea;
				}
				if (szbd_isEmptyOrBlank(comp.country)) {
					delete comp.country;
				}
				var get_mapOptions = szbd_map.try_second_geocode(
					address,
					comp,
					mapOptions,
					uluru
				);
				$.when(get_mapOptions).then(function (mapOptions) {
					szbd_map.set_map(
						mapOptions,
						is_precise_address,
						null,
						from_marker
					);
				});
			} catch (err) {
				console.debug(err);
			}
		},
		update_store_map: function (
			loc,
			is_fail,
			is_precise_address,
			types,
			from_marker
		) {
			szbd_map.initMap(
				loc,
				is_fail,
				is_precise_address,
				types,
				from_marker
			);
		},
		geocode_plus_code_reverse: function (location) {
			try {
				$.when(
					szbd_google_geocode.geocode(location, null, true)
				).then(function (response) {
					if (response.status !== google.maps.GeocoderStatus.OK) {
						return;
					}
					var results = response.results;
					var status = response.status;
					var ok_types = ['plus_code'];
					if (szbd.debug == 1) {
						const date = new Date();

								
						let time = date.toLocaleTimeString()+'\n';
						let debug = 'REVERSE PLUS CODE REQUEST: '+'\n'+'\n'+' Address string: '+'\n' ,
						debug2 =	JSON.stringify(location) +'\n'+'\n',
						debug3 =	'TYPES:' +'\n',
						debug4 =	ok_types +'\n'+'\n',
						debug5 = 	'STATUS:' +'\n',
						debug6 =	status +'\n'+'\n',
						debug7 =	'GEOCODE:' +'\n',
						debug8 =	JSON.stringify(results);
							
							console.debug(debug, debug2,'Time:',time,debug3,debug4,debug5,debug6,debug7,debug8);
					}
					var found_pluscode = _.find(results, function (element) {
						return szbd_checkout.findCommonElements(
							element.types,
							ok_types
						);
					});

					if (found_pluscode != undefined) {
						
						szbd_map.insert_and_trigger(
							'szbd-plus-code',
							found_pluscode.plus_code.compound_code
						);
					} else {
						szbd_map.insert_and_trigger(
							'szbd-plus-code',
							'empty'
						);
					}
				});
			} catch (err) {
				console.debug(err);
			}
		},

		geocode_plus_code: function () {
			try {
				
				szbd_map.updatingplusCode = true;
				
				szbd_map.remove_marker(true);
				var plus_code = jQuery('#szbd-plus-code').val();
				if (_.isEmpty(plus_code)) {
					szbd_map.remove_marker();
					szbd_map.updatingplusCode = false;
					szbd_map.is_generic = false;

					
					szbd_map.insert_and_trigger('szbd-plus-code', 'updateserver');
				} else {
					var comp = {
						country: szbd_checkout.geo_base[0],
					};
					if (szbd_isEmptyOrBlank(comp.country)) {
						delete comp.country;
					}
					szbd_checkout.try_geocode(
						true,
						['plus_code'],
						szbd_checkout.geo_base,
						plus_code,
						comp,
						false,
						true
					);
				}
			} catch (err) {
				console.debug(err);
			}
		},
		set_map: async function (
			mapOptions,
			is_precise_address,
			uluru,
			from_marker
		) {
			const { Geo } = await google.maps.importLibrary("maps");
			const { AdvancedMarkerElement, PinElement } = await google.maps.importLibrary("marker");
			
		
			try {
				if (szbd_map.map) {
					
					szbd_map.map.setOptions(mapOptions);
				} else {
					let el = document.getElementById('szbd_map');
					if(_.isNull(el)){
						return;
					}
					szbd_map.map = new google.maps.Map(
						el,
						mapOptions,
						
					);
					google.maps.event.addListener(
						szbd_map.map,
						'click',
						function (event) 
							{
	
							szbd_map.placeMarker(event.latLng);
	
							szbd_map.insert_and_trigger(
								'szbd-plus-code',
								'empty'
							);
						}
					);
				}
				if (from_marker == 'auto_marker') {
					szbd_map.placeMarker(uluru, false, true);
				} else if (from_marker) {
					let markerOptions = {
						position: uluru,
						map: szbd_map.map,
						gmpDraggable: true,
					};
					if (szbd_map.marker) {
						szbd_map.marker.setOptions(markerOptions);
					} else {
						szbd_map.marker = new google.maps.marker.AdvancedMarkerElement(
							markerOptions
						);
						google.maps.event.clearListeners(
							szbd_map.marker,
							'dragend'
						);
						szbd_map.marker.addListener('dragend', function (event) {
							szbd_map.placeMarker(event.latLng);
							szbd_map.insert_and_trigger(
								'szbd-plus-code',
								'empty'
							);
						});
						szbd_map.remove_marker_on_change();
					}
				}
				jQuery('#szbd_map').height(400);
				

				// Insert saved shipping point from saved user meta on load
				if (szbd_checkout.from_saved_point) {
					szbd_map.placeMarker(uluru, false, false);
					
				}
			} catch (err) {
				
				console.debug(err);
			}
		},
		get_zoom_level: function (types) {
			try {
				var zoom = 17;
				var precise_types = [
					'street_address',
					'subpremise',
					'premise',
					'establishment',
					'precise_address',
					'intersection',
					'neighborhood',
					'plus_code',
					'park',
					'airport',
					'point_of_interest',
					'point_of_interest',
					'landmark',
				];
				var locality_types = [
					'locality',
					'administrative_area_level_1',
					'administrative_area_level_2',
					'administrative_area_level_3',
					'administrative_area_level_4',
					'administrative_area_level_5',
					'sublocality',
					'political',
					'colloquial_area',
					'postal_town',
					'natural_feature',
				];
				var mid_range_types = ['archipelago'];
				if (_.intersection(types, mid_range_types).length) {
					zoom = 8;
				} else if (_.intersection(types, locality_types).length) {
					zoom = 10;
				} else if (_.intersection(types, precise_types).length) {
					zoom = 18;
				} else if (_.contains(types, 'route')) {
					zoom = 15;
				}
				if (_.contains(types, 'postal_code')) {
					zoom = 13;
				}
				if (_.contains(types, 'country')) {
					zoom = 6;
				}
				return zoom;
			} catch (err) {
				console.debug(err);
			}
		},
		placeMarker: async function (
			location,
			from_pluscode = false,
			auto_marker = false
		) {
			const { Geo } = await google.maps.importLibrary("maps");
			const { AdvancedMarkerElement, PinElement } = await google.maps.importLibrary("marker");
		
			try {
				if(!_.isNull(szbd_map.iw)){

					szbd_map.iw.close();
				}
				if (szbd_map.marker) {
					szbd_map.marker.position = location;
				} else {
					this.marker = new google.maps.marker.AdvancedMarkerElement({
						position: location,
						map: szbd_map.map,
						gmpDraggable: true,
					});

					google.maps.event.clearListeners(
						szbd_map.marker,
						'dragend'
					);
					szbd_map.marker.addListener('dragend', function (event) {
						szbd_map.placeMarker(event.latLng);
						szbd_map.insert_and_trigger(
							'szbd-plus-code',
							'empty'
						);
					});
				}

				var store_location = szbd_map.marker.position;
				if (store_location) {
					szbd_map.remove_marker_on_change();
					if (!auto_marker) {
						let y = JSON.stringify(store_location);

						//this.insert_and_trigger('szbd-picked', y);
						this.insert_and_trigger('shipping-szbd-shipping_point', y);

						if(szbd_checkout.from_saved_point ){
							szbd_checkout.from_saved_point = false;
							szbd_map.iw =  new google.maps.InfoWindow({
								ariaLabel: szbd.iw_areaLabel,
								content: szbd.iw_content,
							  });
							  szbd_map.iw.open(szbd_map.map,szbd_map.marker);
							  szbd_checkout.from_saved_point = false;
						}
						//alert(szbd_checkout.from_saved_point);
						
					} else {
						let y = JSON.stringify(store_location);
						//this.insert_and_trigger('szbd-picked', y, true);
						this.insert_and_trigger('shipping-szbd-shipping_point', y, true);
					}
					if (jQuery('#szbd-plus-code').length) {
						
						if (from_pluscode) {
							szbd_map.map.setCenter(store_location);
						}
						
						this.geocode_plus_code_reverse(store_location);
					}
				}
			} catch (err) {
				console.debug(err);
			}
		},
		insert_and_trigger: function (
			el_id,
			value = false,
			notToServer = false
		) {
			try {
				value =
					value == false
						? document.getElementById(el_id).value
						: value;
				let input = document.getElementById(el_id);
				if(_.isNull(input)){
					return;
				}
				//input.value = value;
				var nativeInputValueSetter = Object.getOwnPropertyDescriptor(
					window.HTMLInputElement.prototype,
					'value'
				).set;

				nativeInputValueSetter.call(input, value);
				

				var ev2 = new Event("input", {
					bubbles: true,
					cancelable: true,
					//composed:true
				});
				
				

				ev2.data = { notToServer: notToServer };
				

				input.dispatchEvent(ev2);

				
			
			} catch (err) {
				console.debug(err);
			}
		},
		remove_marker_on_change: function () {
			try {
				 
					
					jQuery(document)
						.off('input.szbd2')
						.on(
							'input.szbd2',
							'#shipping-address_1,#shipping-city,input#shipping-state',
							szbd_map.remove_marker
						);
					jQuery(document)
						.off('change.szbd3')
						.on(
							'change.szbd3',
							'#shipping-country,select#shipping-state',
							szbd_map.remove_marker
						);
				
			} catch (err) {
				console.debug(err);
			}
		},
		remove_marker: function (keep_plus_code) {
			szbd_map.do_the_remove(keep_plus_code);
		},
		do_the_remove(keep_plus_code) {
			try {
				
				if (keep_plus_code !== true) {
					szbd_map.insert_and_trigger('szbd-plus-code', 'empty');
				}
				if (szbd_map.marker) {
					szbd_map.marker.setMap(null);
					szbd_map.marker = null;
				}
				//szbd_map.insert_and_trigger('szbd-picked', 'empty');
				szbd_map.insert_and_trigger('shipping-szbd-shipping_point', null);
			} catch (err) {
				console.debug(err);
			}
		},
		get_country_coords: function (mapOptions, uluru) {
			try {
				if (szbd.countries && szbd.countries[uluru['country']]) {
					let country = szbd.countries[uluru['country']];
					mapOptions.center = {
						lat: country.lat,
						lng: country.lng,
					};
					mapOptions.zoom = szbd_map.get_zoom_level(['country']);
				}
				return mapOptions;
			} catch (err) {
				console.debug(err);
			}
		},
		get_area_ok_types: function (comp) {
			try {
				var ok_types = [
					'archipelago',
					'country',
					'administrative_area_level_1',
					'administrative_area_level_2',
					'administrative_area_level_3',
					'administrative_area_level_4',
					'administrative_area_level_5',
				];
				if (comp.country == 'ES') {
					ok_types.push('locality');
				}
				return ok_types;
			} catch (err) {
				console.debug(err);
			}
		},
		try_second_geocode: function (address, comp, mapOptions, uluru) {
			
		
			try {
				var outcome = $.Deferred();
				if (szbd_checkout.only_country) {
					mapOptions = this.get_country_coords(mapOptions, uluru);
					outcome.resolve(mapOptions);
				} else {
					$.when(szbd_google_geocode.geocode(address, comp)).then(
						function (response) {
							const results = response.results;
							const status = response.status;
							var ok_types = szbd_map.get_area_ok_types(comp);
							if (szbd.debug == 1) {
								const date = new Date();
								let debug = '2nd GEOCODE REQUEST:Address string: \n',
								time = date.toLocaleTimeString(),
								debug2 = 	address +'\n',
								debug3 = 	' Component restriction: \n',
								debug4 = 	JSON.stringify(comp) +'\n',
								debug5 =	'TYPES:' +'\n',
								debug6= 	ok_types +'\n',
								debug7 = 	'STATUS:' +'\n',
								debug8 =	status +'\n',
								debug9 =	'GEOCODE: \n',
								debug10 =	JSON.stringify(results) ;
									
									console.debug(time,debug,debug2,debug3,debug4,debug5,debug6,debug7,debug8,debug9,debug10);
							}
							if (google.maps.GeocoderStatus.OK == status) {
								mapOptions.center =
									results[0].geometry.location;
								mapOptions.zoom = szbd_map.get_zoom_level(
									results[0].types
								);
							} else {
								mapOptions = szbd_map.get_country_coords(
									mapOptions,
									uluru
								);
							}
							outcome.resolve(mapOptions);
						}
					);
				}
				return outcome.promise();
			} catch (err) {
				console.debug(err);
			}
		},
	};
	var szbd_google_geocode = {
		
		
		geocode: async function (address, comp, isPlusCode) {
			const { Geocode } = await google.maps.importLibrary("geocoding");
			try {
				var outcome = $.Deferred();
				comp = _.isObject(comp) ? comp : {};
				var args = {
					address: address,
					componentRestrictions: comp,
				};
				if (isPlusCode !== undefined && isPlusCode) {
					args = {
						location: address,
					};
				}
				var geocoder = new google.maps.Geocoder();
				geocoder.geocode(args, function (results, status) {
					outcome.resolve({
						results: results,
						status: status,
					});
				});
				return outcome.promise();
			} catch (err) {
				console.debug(err);
			}
		},
	};

	
		
	jQuery(document).off('szbd_map_loaded_blocks').on('szbd_map_loaded_blocks', function (e, loc,has_address) {
				
		szbd_checkout.init(loc,has_address);
		
		
	});
	
	


}
init();



	// Google Maps Failure
	window.gm_authFailure = function (err) {
		szbd_checkout.ignore_szbd(err);
	};
	//	Polyfill trim()
	if (!String.prototype.trim) {
		String.prototype.trim = function () {
			return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
		};
	}

	function szbd_isEmptyOrBlank(string) {
		return _.isEmpty(string) || !string.trim();
	}
	function szbd_isJsonString(str) {
		if (_.isNull(str)) {
			return false;
		}
		try {
			JSON.parse(str);
		} catch (e) {
			return false;
		}
		return true;
	}
});
